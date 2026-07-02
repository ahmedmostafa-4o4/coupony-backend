<?php

namespace App\Domain\Subscription\Actions;

use App\Domain\Subscription\Enums\HistoryStatus;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Events\SubscriptionPaymentApproved;
use App\Domain\Subscription\Events\SubscriptionPaymentFailed;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionHistory;
use App\Domain\Subscription\Repositories\PaymentSessionRepository;
use App\Domain\Subscription\Repositories\SubscriptionRepository;
use App\Domain\Subscription\Services\PaymobService;
use App\Domain\Subscription\Services\SubscriptionStateMachine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProcessWebhookAction
{
    public function __construct(
        private readonly PaymobService $paymobService,
        private readonly PaymentSessionRepository $paymentSessionRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly SubscriptionStateMachine $stateMachine,
    ) {}

    /**
     * Process a Paymob webhook payload.
     *
     * @throws HttpException When HMAC validation fails (401)
     */
    public function execute(array $payload, string $hmacSignature): void
    {
        // 1. Validate HMAC signature
        if (! $this->paymobService->validateHmac($payload, $hmacSignature)) {
            Log::warning('Webhook HMAC validation failed', [
                'payload_keys' => array_keys($payload),
                'ip' => request()->ip(),
            ]);

            throw new HttpException(401, 'Invalid HMAC signature');
        }

        // 2. Extract order ID from payload
        $orderId = $this->extractOrderId($payload);

        if ($orderId === null) {
            Log::warning('Webhook payload missing order ID', [
                'payload_keys' => array_keys($payload),
            ]);

            return;
        }

        // 3. Find PaymentSession by paymob_order_id
        $session = PaymentSession::where('paymob_order_id', (string) $orderId)->first();

        if ($session === null) {
            Log::warning('Webhook received for unknown order', [
                'paymob_order_id' => $orderId,
            ]);

            return;
        }

        // 4. Idempotency: if session already processed, return without changes
        if (in_array($session->status, [PaymentSessionStatus::PAID, PaymentSessionStatus::FAILED], true)) {
            return;
        }

        // 5. Extract transaction ID for reference
        $transactionId = $this->extractTransactionId($payload);

        // 6. Check payment success/failure
        $isSuccess = $this->extractSuccess($payload);

        if ($isSuccess) {
            $this->handleSuccess($session, $transactionId);
        } else {
            $failureReason = $this->extractFailureReason($payload);
            $this->handleFailure($session, $failureReason);
        }
    }

    /**
     * Handle a successful payment webhook.
     */
    private function handleSuccess(PaymentSession $session, ?string $transactionId): void
    {
        DB::transaction(function () use ($session, $transactionId) {
            // Mark session as paid
            $session = $this->paymentSessionRepository->markAsPaid($session, $transactionId);

            // Find or create subscription for the store
            $subscription = $this->subscriptionRepository->findByStore($session->store_id);

            if ($subscription === null) {
                $subscription = Subscription::create([
                    'store_id' => $session->store_id,
                    'plan_id' => $session->plan_id,
                    'status' => SubscriptionStatus::NONE,
                    'billing_cycle' => $session->billing_cycle,
                    'current_period_start' => now(),
                    'current_period_end' => $this->calculatePeriodEnd($session->billing_cycle->value),
                ]);
            }

            // Transition subscription to active via StateMachine
            if ($subscription->status !== SubscriptionStatus::ACTIVE) {
                $subscription = $this->stateMachine->transition(
                    $subscription,
                    SubscriptionStatus::ACTIVE,
                    'Payment confirmed via webhook'
                );
            }

            // Update plan and billing cycle if changed
            $subscription->update([
                'plan_id' => $session->plan_id,
                'billing_cycle' => $session->billing_cycle,
                'current_period_start' => now(),
                'current_period_end' => $this->calculatePeriodEnd($session->billing_cycle->value),
                'cancelled_at' => null,
            ]);

            // Create subscription history entry with active status
            SubscriptionHistory::create([
                'store_id' => $session->store_id,
                'plan_id' => $session->plan_id,
                'billing_cycle' => $session->billing_cycle,
                'amount' => $session->amount,
                'status' => HistoryStatus::ACTIVE,
                'period_start' => $subscription->current_period_start,
                'period_end' => $subscription->current_period_end,
                'payment_session_id' => $session->id,
            ]);

            // Dispatch event
            SubscriptionPaymentApproved::dispatch($subscription, $session);
        });
    }

    /**
     * Handle a failed payment webhook.
     */
    private function handleFailure(PaymentSession $session, ?string $reason): void
    {
        DB::transaction(function () use ($session, $reason) {
            // Mark session as failed
            $session = $this->paymentSessionRepository->markAsFailed($session, $reason);

            // Create subscription history entry with failed status
            SubscriptionHistory::create([
                'store_id' => $session->store_id,
                'plan_id' => $session->plan_id,
                'billing_cycle' => $session->billing_cycle,
                'amount' => $session->amount,
                'status' => HistoryStatus::FAILED,
                'period_start' => now(),
                'period_end' => now(),
                'payment_session_id' => $session->id,
            ]);

            // Dispatch event
            SubscriptionPaymentFailed::dispatch($session, $reason);
        });
    }

    /**
     * Extract the order ID from the webhook payload.
     * Paymob sends it as payload['order']['id'] or payload['obj']['order']['id'].
     */
    private function extractOrderId(array $payload): ?string
    {
        // Try payload['order']['id'] first (transaction callback format)
        if (isset($payload['order']['id'])) {
            return (string) $payload['order']['id'];
        }

        // Try payload['obj']['order']['id'] (notification format)
        if (isset($payload['obj']['order']['id'])) {
            return (string) $payload['obj']['order']['id'];
        }

        return null;
    }

    /**
     * Extract the transaction ID from the webhook payload.
     */
    private function extractTransactionId(array $payload): ?string
    {
        if (isset($payload['id'])) {
            return (string) $payload['id'];
        }

        if (isset($payload['obj']['id'])) {
            return (string) $payload['obj']['id'];
        }

        return null;
    }

    /**
     * Extract the success flag from the webhook payload.
     */
    private function extractSuccess(array $payload): bool
    {
        if (isset($payload['success'])) {
            return (bool) $payload['success'];
        }

        if (isset($payload['obj']['success'])) {
            return (bool) $payload['obj']['success'];
        }

        return false;
    }

    /**
     * Extract the failure reason from the webhook payload.
     */
    private function extractFailureReason(array $payload): ?string
    {
        // Check for data.message (Paymob error detail)
        if (isset($payload['data']['message'])) {
            return (string) $payload['data']['message'];
        }

        if (isset($payload['obj']['data']['message'])) {
            return (string) $payload['obj']['data']['message'];
        }

        return 'Payment declined';
    }

    /**
     * Calculate the period end date based on billing cycle.
     */
    private function calculatePeriodEnd(string $billingCycle): \DateTimeInterface
    {
        return match ($billingCycle) {
            'yearly' => now()->addYear(),
            default => now()->addMonth(),
        };
    }
}
