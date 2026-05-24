<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ConfirmPaymentAction;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyUsedException;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 5: Session single-use enforcement
 *
 * **Validates: Requirements 2.2, 11.4**
 *
 * For any payment session that has already been consumed (status is `paid` or `failed`),
 * attempting to confirm that session must return/throw with error code `PAYMENT_SESSION_ALREADY_USED`.
 */
class SessionSingleUseEnforcementPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ iterations with Faker-generated data.
     * Each iteration creates a consumed (failed) session scenario.
     *
     * @return array<int, array{0: string, 1: string, 2: string}>
     */
    public static function consumedSessionProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 100;
        $cases = [];

        $billingCycles = ['monthly', 'yearly'];

        for ($i = 0; $i < $iterations; $i++) {
            $billingCycle = $faker->randomElement($billingCycles);
            $amount = $faker->randomFloat(2, 49, 4990);
            $failureReason = $faker->sentence();

            $cases["iteration_{$i}_{$billingCycle}"] = [
                $billingCycle,
                (string) $amount,
                $failureReason,
            ];
        }

        return $cases;
    }

    /**
     * @dataProvider consumedSessionProvider
     */
    public function test_confirming_a_failed_session_throws_payment_session_already_used_exception(
        string $billingCycle,
        string $amount,
        string $failureReason
    ): void {
        // Arrange
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => BillingCycle::from($billingCycle),
            'amount' => $amount,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::FAILED,
            'expires_at' => now()->addMinutes(30),
            'failed_at' => now()->subMinutes($this->faker()->numberBetween(1, 60)),
            'failure_reason' => $failureReason,
        ]);

        $action = app(ConfirmPaymentAction::class);

        // Act & Assert
        $this->expectException(PaymentSessionAlreadyUsedException::class);

        $action->execute($store, $session->id);
    }

    /**
     * Helper to get a Faker instance for use in test methods.
     */
    private function faker(): \Faker\Generator
    {
        return \Faker\Factory::create();
    }
}
