<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ProcessWebhookAction;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionAuditLog;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\PaymobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 10: Webhook idempotency
 *
 * **Validates: Requirements 3.5**
 *
 * For any webhook payload, processing it N times (N ≥ 1) must produce the same
 * final state as processing it exactly once — same session status, same subscription
 * status, and no duplicate audit log entries for the same event.
 */
class WebhookIdempotencyPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ random webhook scenarios with Faker.
     *
     * Each iteration generates:
     * - A random success/failure outcome
     * - A random number of times (2-5) to process the webhook
     * - Random Faker-generated order/transaction IDs
     *
     * @return array<int, array{0: bool, 1: int, 2: string, 3: string}>
     */
    public static function randomWebhookScenariosProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 100;
        $cases = [];

        for ($i = 0; $i < $iterations; $i++) {
            $isSuccess = $faker->boolean();
            $repeatCount = $faker->numberBetween(2, 5);
            $orderId = (string) $faker->unique()->numberBetween(100000, 999999);
            $transactionId = (string) $faker->unique()->numberBetween(1000000, 9999999);

            $cases["iteration_{$i}_success_{$isSuccess}_repeat_{$repeatCount}"] = [
                $isSuccess,
                $repeatCount,
                $orderId,
                $transactionId,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider randomWebhookScenariosProvider
     */
    public function test_webhook_processing_is_idempotent(
        bool $isSuccess,
        int $repeatCount,
        string $orderId,
        string $transactionId,
    ): void {
        // Arrange: Mock PaymobService::validateHmac to always return true
        $paymobMock = Mockery::mock(PaymobService::class);
        $paymobMock->shouldReceive('validateHmac')->andReturn(true);
        $this->app->instance(PaymobService::class, $paymobMock);

        // Create test data
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $billingCycle = BillingCycle::MONTHLY;

        // Create a payment session with the paymob_order_id
        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $plan->price_monthly,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'paymob_order_id' => $orderId,
            'expires_at' => now()->addMinutes(30),
        ]);

        // Create a subscription in 'none' status for the store
        $subscription = Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::NONE,
            'billing_cycle' => $billingCycle,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Build a valid webhook payload
        $payload = $this->buildWebhookPayload($orderId, $transactionId, $isSuccess);
        $hmacSignature = 'valid-signature';

        /** @var ProcessWebhookAction $action */
        $action = app(ProcessWebhookAction::class);

        // Act: Process the webhook the first time
        $action->execute($payload, $hmacSignature);

        // Capture state after first processing
        $sessionAfterFirst = PaymentSession::find($session->id);
        $subscriptionAfterFirst = Subscription::where('store_id', $store->id)->first();
        $auditLogCountAfterFirst = SubscriptionAuditLog::where('store_id', $store->id)->count();

        $sessionStatusAfterFirst = $sessionAfterFirst->status;
        $subscriptionStatusAfterFirst = $subscriptionAfterFirst->status;

        // Act: Process the webhook N-1 more times (total N times)
        for ($i = 1; $i < $repeatCount; $i++) {
            $action->execute($payload, $hmacSignature);
        }

        // Assert: Final state is the same as after first processing
        $sessionFinal = PaymentSession::find($session->id);
        $subscriptionFinal = Subscription::where('store_id', $store->id)->first();
        $auditLogCountFinal = SubscriptionAuditLog::where('store_id', $store->id)->count();

        // Session status must be the same after all calls
        $this->assertEquals(
            $sessionStatusAfterFirst,
            $sessionFinal->status,
            "Session status must remain '{$sessionStatusAfterFirst->value}' after processing webhook {$repeatCount} times."
        );

        // Subscription status must be the same after all calls
        $this->assertEquals(
            $subscriptionStatusAfterFirst,
            $subscriptionFinal->status,
            "Subscription status must remain '{$subscriptionStatusAfterFirst->value}' after processing webhook {$repeatCount} times."
        );

        // Audit log count must not increase after first call
        $this->assertEquals(
            $auditLogCountAfterFirst,
            $auditLogCountFinal,
            "Audit log count must not increase after first webhook processing. Expected {$auditLogCountAfterFirst}, got {$auditLogCountFinal} after {$repeatCount} total calls."
        );

        // Additional verification: session status matches expected outcome
        if ($isSuccess) {
            $this->assertEquals(
                PaymentSessionStatus::PAID,
                $sessionFinal->status,
                'Successful webhook must result in PAID session status.'
            );
            $this->assertEquals(
                SubscriptionStatus::ACTIVE,
                $subscriptionFinal->status,
                'Successful webhook must result in ACTIVE subscription status.'
            );
        } else {
            $this->assertEquals(
                PaymentSessionStatus::FAILED,
                $sessionFinal->status,
                'Failed webhook must result in FAILED session status.'
            );
        }
    }

    /**
     * Build a webhook payload that mimics Paymob's transaction callback format.
     */
    private function buildWebhookPayload(string $orderId, string $transactionId, bool $isSuccess): array
    {
        return [
            'id' => $transactionId,
            'success' => $isSuccess,
            'order' => [
                'id' => $orderId,
            ],
            'amount_cents' => 10000,
            'currency' => 'EGP',
            'created_at' => now()->toIso8601String(),
            'error_occured' => ! $isSuccess,
            'has_parent_transaction' => false,
            'integration_id' => 12345,
            'is_3d_secure' => true,
            'is_auth' => false,
            'is_capture' => false,
            'is_refunded' => false,
            'is_standalone_payment' => true,
            'is_voided' => false,
            'owner' => 1,
            'pending' => false,
            'source_data' => [
                'pan' => '2346',
                'sub_type' => 'MasterCard',
                'type' => 'card',
            ],
            'data' => [
                'message' => $isSuccess ? null : 'Payment declined by issuer',
            ],
        ];
    }
}
