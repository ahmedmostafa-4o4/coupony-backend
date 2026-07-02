<?php

namespace App\Domain\Subscription\Actions;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\HistoryStatus;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Events\SubscriptionPaymentApproved;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyUsedException;
use App\Domain\Subscription\Exceptions\PaymentSessionExpiredException;
use App\Domain\Subscription\Exceptions\PaymentSessionNotFoundException;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionHistory;
use App\Domain\Subscription\Repositories\PaymentSessionRepository;
use App\Domain\Subscription\Repositories\SubscriptionRepository;
use App\Domain\Subscription\Services\SubscriptionStateMachine;
use Illuminate\Support\Facades\DB;

class ConfirmPaymentAction
{
    public function __construct(
        private PaymentSessionRepository $paymentSessionRepository,
        private SubscriptionRepository $subscriptionRepository,
        private SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Confirm a payment session and activate the subscription if the webhook already confirmed payment.
     *
     * @throws PaymentSessionNotFoundException
     * @throws PaymentSessionAlreadyUsedException
     * @throws PaymentSessionExpiredException
     */
    public function execute(Store $store, string $sessionId): Subscription
    {
        $session = $this->paymentSessionRepository->findBySessionId($sessionId);

        // Session not found or does not belong to this store → 404
        if ($session === null || $session->store_id !== $store->id) {
            throw PaymentSessionNotFoundException::make($sessionId);
        }

        // Session already consumed (failed status) → 409
        if ($session->status === PaymentSessionStatus::FAILED) {
            throw PaymentSessionAlreadyUsedException::make($sessionId);
        }

        // Session expired → 410
        if ($session->isExpired() && $session->status === PaymentSessionStatus::PENDING) {
            throw PaymentSessionExpiredException::make($sessionId);
        }

        // If webhook already confirmed (session status = paid), activate subscription
        if ($session->status === PaymentSessionStatus::PAID) {
            return DB::transaction(function () use ($store, $session) {
                // Find or create subscription for the store
                $subscription = $this->subscriptionRepository->findByStore($store->id);

                if ($subscription === null) {
                    $subscription = Subscription::create([
                        'store_id' => $store->id,
                        'plan_id' => $session->plan_id,
                        'status' => SubscriptionStatus::NONE,
                        'billing_cycle' => $session->billing_cycle,
                        'current_period_start' => now(),
                        'current_period_end' => $this->calculatePeriodEnd($session->billing_cycle),
                    ]);
                }

                // Transition to active if not already active
                if ($subscription->status !== SubscriptionStatus::ACTIVE) {
                    $subscription = $this->stateMachine->transition(
                        $subscription,
                        SubscriptionStatus::ACTIVE,
                        'Payment confirmed via confirm-payment endpoint'
                    );
                }

                // Update period dates and plan
                $subscription->update([
                    'plan_id' => $session->plan_id,
                    'billing_cycle' => $session->billing_cycle,
                    'current_period_start' => now(),
                    'current_period_end' => $this->calculatePeriodEnd($session->billing_cycle),
                    'cancelled_at' => null,
                ]);

                $subscription = $subscription->fresh();

                // Create SubscriptionHistory entry with active status
                SubscriptionHistory::create([
                    'store_id' => $store->id,
                    'plan_id' => $session->plan_id,
                    'billing_cycle' => $session->billing_cycle,
                    'amount' => $session->amount,
                    'payment_method' => 'card',
                    'status' => HistoryStatus::ACTIVE,
                    'period_start' => $subscription->current_period_start,
                    'period_end' => $subscription->current_period_end,
                    'payment_session_id' => $session->id,
                ]);

                // Dispatch SubscriptionPaymentApproved event
                SubscriptionPaymentApproved::dispatch($subscription, $session);

                return $subscription;
            });
        }

        // Session is still pending (webhook hasn't confirmed yet) — return current subscription state
        $subscription = $this->subscriptionRepository->findByStore($store->id);

        if ($subscription === null) {
            $subscription = Subscription::create([
                'store_id' => $store->id,
                'plan_id' => $session->plan_id,
                'status' => SubscriptionStatus::NONE,
                'billing_cycle' => $session->billing_cycle,
                'current_period_start' => null,
                'current_period_end' => null,
            ]);
        }

        return $subscription;
    }

    /**
     * Calculate the period end date based on billing cycle.
     */
    private function calculatePeriodEnd(BillingCycle|string $billingCycle): \Carbon\Carbon
    {
        $cycle = $billingCycle instanceof BillingCycle ? $billingCycle->value : $billingCycle;

        return match ($cycle) {
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }
}
