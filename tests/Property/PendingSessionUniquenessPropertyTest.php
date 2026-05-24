<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\InitiatePaymentAction;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyExistsException;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 2: Pending session uniqueness
 *
 * **Validates: Requirements 1.3, 11.5**
 *
 * For any store that already has a pending (non-expired) payment session,
 * attempting to initiate a new payment session must always be rejected
 * with PaymentSessionAlreadyExistsException.
 */
class PendingSessionUniquenessPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ iterations with randomized stores,
     * plans, and billing cycles using Faker.
     *
     * @return array<int, array{0: string, 1: int}>
     */
    public static function randomPendingSessionScenariosProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 105;
        $cases = [];

        $billingCycles = ['monthly', 'yearly'];

        for ($i = 0; $i < $iterations; $i++) {
            $billingCycle = $faker->randomElement($billingCycles);
            // TTL in minutes for the existing session (between 5 and 120 minutes in the future)
            $ttlMinutes = $faker->numberBetween(5, 120);

            $cases["iteration_{$i}_{$billingCycle}_ttl_{$ttlMinutes}m"] = [
                $billingCycle,
                $ttlMinutes,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider randomPendingSessionScenariosProvider
     */
    public function test_initiating_payment_with_existing_pending_session_throws_exception(
        string $billingCycle,
        int $ttlMinutes
    ): void {
        // Arrange: Create a store and plan
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Create an existing pending (non-expired) payment session for this store
        PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'amount' => $plan->getPriceForCycle($billingCycle),
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'paymob_order_id' => 'order_' . uniqid(),
            'payment_url' => 'https://accept.paymob.com/test',
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        // Act & Assert: Attempting to initiate a new session must throw
        $this->expectException(PaymentSessionAlreadyExistsException::class);

        $action = app(InitiatePaymentAction::class);
        $newPlan = SubscriptionPlan::factory()->create();
        $action->execute($store, $newPlan, $billingCycle);
    }
}
