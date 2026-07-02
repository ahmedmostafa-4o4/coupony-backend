<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ProcessWebhookAction;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Services\PaymobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 8: Successful payment activates subscription
 *
 * **Validates: Requirements 2.1, 3.3**
 *
 * For any webhook payload indicating successful payment (or confirmed session marked as paid),
 * the associated payment session must transition to `paid` status and the store's subscription
 * must transition to `active` status.
 */
class SuccessfulPaymentActivationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ random successful webhook scenarios using Faker.
     *
     * @return array<int, array{0: string, 1: string, 2: int}>
     */
    public static function randomSuccessfulPaymentProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 100;
        $cases = [];

        $billingCycles = ['monthly', 'yearly'];

        for ($i = 0; $i < $iterations; $i++) {
            $billingCycle = $faker->randomElement($billingCycles);
            $orderId = (string) $faker->unique()->numberBetween(100000, 999999);
            $transactionId = (string) $faker->unique()->numberBetween(1000000, 9999999);

            $cases["iteration_{$i}_cycle_{$billingCycle}_order_{$orderId}"] = [
                $billingCycle,
                $orderId,
                $transactionId,
            ];
        }

        return $cases;
    }

    #[DataProvider('randomSuccessfulPaymentProvider')]
    public function test_successful_payment_webhook_activates_subscription(
        string $billingCycle,
        string $orderId,
        string $transactionId
    ): void {
        // Arrange: Create a store, plan, and pending payment session
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

        // Build a webhook payload indicating successful payment
        $payload = [
            'id' => $transactionId,
            'success' => true,
            'order' => [
                'id' => $orderId,
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

        // Assert: Payment session must transition to 'paid' status
        $session->refresh();
        $this->assertEquals(
            PaymentSessionStatus::PAID,
            $session->status,
            "Payment session must transition to 'paid' status after successful webhook. Got: {$session->status->value}"
        );

        // Assert: Store's subscription must transition to 'active' status
        $subscription = Subscription::where('store_id', $store->id)->first();
        $this->assertNotNull(
            $subscription,
            'A subscription must exist for the store after successful payment.'
        );
        $this->assertEquals(
            SubscriptionStatus::ACTIVE,
            $subscription->status,
            "Subscription must transition to 'active' status after successful payment. Got: {$subscription->status->value}"
        );
    }

    public function test_successful_payment_webhook_updates_already_active_subscription_plan_and_clears_cancellation(): void
    {
        $store = Store::factory()->create();
        $oldPlan = SubscriptionPlan::factory()->create();
        $newPlan = SubscriptionPlan::factory()->create([
            'price_yearly' => 999.99,
        ]);
        $orderId = '987654';
        $transactionId = '7654321';

        Subscription::create([
            'store_id' => $store->id,
            'plan_id' => $oldPlan->id,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => BillingCycle::MONTHLY,
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addDay(),
            'cancelled_at' => now()->subDay(),
        ]);

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $newPlan->id,
            'billing_cycle' => BillingCycle::YEARLY,
            'amount' => $newPlan->price_yearly,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'paymob_order_id' => $orderId,
            'expires_at' => now()->addMinutes(30),
        ]);

        $payload = [
            'id' => $transactionId,
            'success' => true,
            'order' => [
                'id' => $orderId,
            ],
        ];

        $hmacSignature = 'valid_signature';

        $mockPaymobService = Mockery::mock(PaymobService::class);
        $mockPaymobService->shouldReceive('validateHmac')
            ->with($payload, $hmacSignature)
            ->andReturn(true);

        $this->app->instance(PaymobService::class, $mockPaymobService);

        $action = $this->app->make(ProcessWebhookAction::class);
        $action->execute($payload, $hmacSignature);

        $session->refresh();
        $subscription = Subscription::where('store_id', $store->id)->firstOrFail();

        $this->assertEquals(PaymentSessionStatus::PAID, $session->status);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals($newPlan->id, $subscription->plan_id);
        $this->assertEquals('yearly', $subscription->billing_cycle->value);
        $this->assertNull($subscription->cancelled_at);
    }
}
