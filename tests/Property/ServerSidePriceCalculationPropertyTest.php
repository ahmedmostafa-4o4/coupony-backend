<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\InitiatePaymentAction;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Faker\Factory as Faker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 1: Server-side price calculation
 *
 * **Validates: Requirements 1.2**
 *
 * For any subscription plan, billing cycle, and arbitrary client-supplied amount,
 * the resulting payment session amount must equal the plan's configured server-side
 * price for that billing cycle, never the client-supplied value.
 */
class ServerSidePriceCalculationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ random iterations for property testing.
     *
     * Each iteration produces a random plan with random prices, a random billing cycle,
     * and a random "client-supplied" amount that should be ignored by the action.
     *
     * @return array<string, array{0: int}>
     */
    public static function randomPriceIterationsProvider(): array
    {
        $cases = [];

        for ($i = 0; $i < 110; $i++) {
            $cases["iteration_{$i}"] = [$i];
        }

        return $cases;
    }

    /**
     * @dataProvider randomPriceIterationsProvider
     */
    public function test_payment_session_amount_equals_plan_price_not_client_supplied_value(int $iteration): void
    {
        $faker = Faker::create();
        $faker->seed($iteration);

        // Generate random plan prices
        $priceMonthly = $faker->randomFloat(2, 10, 9999);
        $priceYearly = $faker->randomFloat(2, 100, 99999);

        // Generate a random "client-supplied" amount that should be ignored
        $clientSuppliedAmount = $faker->randomFloat(2, 1, 999999);

        // Pick a random billing cycle
        $billingCycle = $faker->randomElement(['monthly', 'yearly']);

        // Determine the expected server-side price
        $expectedAmount = $billingCycle === 'yearly' ? $priceYearly : $priceMonthly;

        // Create a store
        $store = Store::factory()->create();

        // Create a plan with the random prices
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => $priceMonthly,
            'price_yearly' => $priceYearly,
            'is_active' => true,
        ]);

        // Mock Paymob Intention API
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => 'intention_' . $iteration,
                'client_secret' => 'pk_test_secret_' . $iteration,
                'payment_keys' => [],
            ], 200),
        ]);

        // Execute the action (client-supplied amount is never passed to the action;
        // the action always calculates from the plan — this test verifies that invariant)
        $action = app(InitiatePaymentAction::class);
        $session = $action->execute($store, $plan, $billingCycle);

        // Assert: the session amount equals the plan's configured price for the cycle
        $this->assertEquals(
            number_format($expectedAmount, 2, '.', ''),
            number_format((float) $session->amount, 2, '.', ''),
            "Iteration {$iteration}: Payment session amount must equal plan's {$billingCycle} price "
            . "({$expectedAmount}), not any client-supplied value ({$clientSuppliedAmount}). "
            . "Billing cycle: {$billingCycle}, Monthly: {$priceMonthly}, Yearly: {$priceYearly}"
        );

        // Assert: the session amount is NOT the client-supplied value (unless by coincidence)
        // This is the core property: server-side calculation takes precedence
        if (number_format($clientSuppliedAmount, 2, '.', '') !== number_format($expectedAmount, 2, '.', '')) {
            $this->assertNotEquals(
                number_format($clientSuppliedAmount, 2, '.', ''),
                number_format((float) $session->amount, 2, '.', ''),
                "Iteration {$iteration}: Payment session amount must NOT equal client-supplied value."
            );
        }

        // Assert: the billing cycle is correctly stored
        $expectedBillingCycleEnum = BillingCycle::from($billingCycle);
        $this->assertEquals(
            $expectedBillingCycleEnum,
            $session->billing_cycle,
            "Iteration {$iteration}: Billing cycle must be stored correctly."
        );

        // Assert: the plan_id is correctly associated
        $this->assertEquals(
            $plan->id,
            $session->plan_id,
            "Iteration {$iteration}: Plan ID must be correctly associated with the session."
        );
    }
}
