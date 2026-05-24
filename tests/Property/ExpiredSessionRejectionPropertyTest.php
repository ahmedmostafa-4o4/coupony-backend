<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ConfirmPaymentAction;
use App\Domain\Subscription\Enums\BillingCycle;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Exceptions\PaymentSessionExpiredException;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 6: Expired session rejection
 *
 * **Validates: Requirements 2.3**
 *
 * For any payment session where the current time exceeds `expires_at`,
 * attempting to confirm that session must throw PaymentSessionExpiredException.
 */
class ExpiredSessionRejectionPropertyTest extends TestCase
{
    use RefreshDatabase;

    private ConfirmPaymentAction $confirmPaymentAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->confirmPaymentAction = app(ConfirmPaymentAction::class);
    }

    /**
     * Data provider that generates 100+ iterations of expired sessions
     * with random expiration times between 1 and 1440 minutes ago.
     *
     * @return array<int, array{0: int}>
     */
    public static function expiredSessionProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 100;
        $cases = [];

        for ($i = 0; $i < $iterations; $i++) {
            $minutesAgo = $faker->numberBetween(1, 1440);
            $cases["iteration_{$i}_expired_{$minutesAgo}_minutes_ago"] = [$minutesAgo];
        }

        return $cases;
    }

    /**
     * @dataProvider expiredSessionProvider
     */
    public function test_expired_session_throws_payment_session_expired_exception(
        int $minutesAgo
    ): void {
        // Arrange
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => BillingCycle::MONTHLY,
            'amount' => $plan->price_monthly,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'expires_at' => now()->subMinutes($minutesAgo),
            'payment_url' => 'https://accept.paymob.com/api/acceptance/iframes/test',
            'paymob_order_id' => 'order_' . uniqid(),
        ]);

        // Act & Assert
        $this->expectException(PaymentSessionExpiredException::class);

        $this->confirmPaymentAction->execute($store, $session->id);
    }
}
