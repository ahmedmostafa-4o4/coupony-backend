<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ProcessWebhookAction;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\HistoryStatus;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Events\SubscriptionPaymentFailed;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionHistory;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\PaymobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 9: Failed payment records failure
 *
 * **Validates: Requirements 3.4**
 *
 * For any webhook payload indicating failed payment, the associated payment session must
 * transition to `failed` status, a subscription history entry with `failed` status must be
 * created, and a `SubscriptionPaymentFailed` event must be dispatched.
 */
class FailedPaymentRecordingPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ random failed webhook scenarios using Faker.
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function randomFailedPaymentProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 100;
        $cases = [];

        $billingCycles = ['monthly', 'yearly'];
        $failureReasons = [
            'Insufficient funds',
            'Card declined',
            'Transaction timeout',
            'Invalid card number',
            'Expired card',
            'Authentication failed',
            'Bank refused transaction',
            'Fraud suspected',
            'Limit exceeded',
            'Network error',
        ];

        for ($i = 0; $i < $iterations; $i++) {
            $billingCycle = $faker->randomElement($billingCycles);
            $orderId = (string) $faker->unique()->numberBetween(100000, 999999);
            $transactionId = (string) $faker->unique()->numberBetween(1000000, 9999999);
            $failureReason = $faker->randomElement($failureReasons);

            $cases["iteration_{$i}_cycle_{$billingCycle}_order_{$orderId}"] = [
                $billingCycle,
                $orderId,
                $transactionId,
                $failureReason,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider randomFailedPaymentProvider
     */
    public function test_failed_payment_webhook_records_failure(
        string $billingCycle,
        string $orderId,
        string $transactionId,
        string $failureReason
    ): void {
        // Arrange: Fake events to capture dispatched events
        Event::fake([SubscriptionPaymentFailed::class]);

        // Create a store, plan, and pending payment session
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => BillingCycle::from($billingCycle),
            'amount' => $billingCycle === 'monthly' ? $plan->price_monthly : $plan->price_yearly,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'paymob_order_id' => $orderId,
            'expires_at' => now()->addMinutes(30),
        ]);

        // Build a webhook payload indicating failed payment (success=false)
        $payload = [
            'id' => $transactionId,
            'success' => false,
            'order' => [
                'id' => $orderId,
            ],
            'data' => [
                'message' => $failureReason,
            ],
        ];

        $hmacSignature = 'valid_signature';

        // Mock PaymobService::validateHmac to return true
        $mockPaymobService = Mockery::mock(PaymobService::class);
        $mockPaymobService->shouldReceive('validateHmac')
            ->with($payload, $hmacSignature)
            ->andReturn(true);

        $this->app->instance(PaymobService::class, $mockPaymobService);

        // Act: Process the webhook
        $action = $this->app->make(ProcessWebhookAction::class);
        $action->execute($payload, $hmacSignature);

        // Assert 1: Payment session must transition to 'failed' status
        $session->refresh();
        $this->assertEquals(
            PaymentSessionStatus::FAILED,
            $session->status,
            "Payment session must transition to 'failed' status after failed webhook. Got: {$session->status->value}"
        );

        // Assert 2: Subscription history entry with 'failed' status must exist
        $historyEntry = SubscriptionHistory::where('store_id', $store->id)
            ->where('payment_session_id', $session->id)
            ->first();

        $this->assertNotNull(
            $historyEntry,
            'A subscription history entry must be created for a failed payment.'
        );
        $this->assertEquals(
            HistoryStatus::FAILED,
            $historyEntry->status,
            "Subscription history entry must have 'failed' status. Got: {$historyEntry->status->value}"
        );

        // Assert 3: SubscriptionPaymentFailed event must be dispatched
        Event::assertDispatched(SubscriptionPaymentFailed::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }
}
