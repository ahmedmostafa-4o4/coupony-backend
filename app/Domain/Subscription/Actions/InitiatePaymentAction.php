<?php

namespace App\Domain\Subscription\Actions;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyExistsException;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Repositories\PaymentSessionRepository;
use App\Domain\Subscription\Services\PaymobService;
use Illuminate\Support\Facades\DB;

class InitiatePaymentAction
{
    public function __construct(
        private PaymentSessionRepository $paymentSessionRepository,
        private PaymobService $paymobService,
    ) {}

    /**
     * Initiate a payment session for a store subscribing to a plan.
     *
     * Uses Paymob's Intention API to create a payment intention and returns
     * the client_secret for the Flutter SDK.
     *
     * @throws PaymentSessionAlreadyExistsException
     * @throws \App\Domain\Subscription\Exceptions\PaymobApiException
     */
    public function execute(Store $store, SubscriptionPlan $plan, string $billingCycle): PaymentSession
    {
        // Check for existing pending session — reject with 409 if found
        $existingSession = $this->paymentSessionRepository->findPendingByStore($store->id);

        if ($existingSession !== null) {
            throw PaymentSessionAlreadyExistsException::forStore($store->id);
        }

        // Calculate amount server-side from plan's configured price
        $amount = $plan->getPriceForCycle($billingCycle);
        $amountCents = (int) round($amount * 100);
        $currency = $plan->currency ?? 'EGP';

        // Billing data for Paymob Intention API
        $billingData = [
            'first_name' => 'Store',
            'last_name' => 'Owner',
            'email' => 'payment@coupony.app',
            'phone_number' => '+201000000000',
        ];

        // Extras to identify this payment on webhook
        $extras = [
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
        ];

        // Create Payment Intention via Paymob API
        $intentionResponse = $this->paymobService->createIntention(
            $amountCents,
            $currency,
            $billingData,
            $extras
        );

        $clientSecret = $intentionResponse['client_secret'];
        $intentionId = $intentionResponse['id'] ?? null;

        // Calculate TTL expiry
        $ttlMinutes = (int) config('subscription.payment_session_ttl_minutes', 30);
        $expiresAt = now()->addMinutes($ttlMinutes);

        // Create PaymentSession record
        $paymentSession = DB::transaction(fn () => PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'currency' => $currency,
            'status' => PaymentSessionStatus::PENDING,
            'paymob_order_id' => (string) $intentionId,
            'payment_url' => $clientSecret,
            'expires_at' => $expiresAt,
        ]));

        return $paymentSession;
    }
}
