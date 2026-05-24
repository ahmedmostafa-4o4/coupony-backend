<?php

namespace Tests\Property;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\InitiatePaymentAction;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Repositories\PaymentSessionRepository;
use App\Domain\Subscription\Services\PaymobService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Feature: subscription-system, Property 3: Session TTL invariant
 *
 * **Validates: Requirements 1.4**
 *
 * For any newly created payment session, the `expires_at` timestamp must equal
 * the creation time plus the configured TTL (in minutes), and any session where
 * `now > expires_at` must be treated as expired.
 */
class SessionTtlInvariantPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Data provider that generates 100+ random TTL values (5-120 minutes).
     *
     * @return array<int, array{0: int}>
     */
    public static function randomTtlProvider(): array
    {
        $faker = \Faker\Factory::create();
        $iterations = 100;
        $cases = [];

        for ($i = 0; $i < $iterations; $i++) {
            $ttlMinutes = $faker->numberBetween(5, 120);
            $cases["iteration_{$i}_ttl_{$ttlMinutes}m"] = [$ttlMinutes];
        }

        return $cases;
    }

    /**
     * @dataProvider randomTtlProvider
     */
    public function test_session_expires_at_equals_creation_time_plus_configured_ttl(int $ttlMinutes): void
    {
        // Arrange: Set the TTL config value
        config(['subscription.payment_session_ttl_minutes' => $ttlMinutes]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Mock PaymobService to avoid real HTTP calls
        $paymobServiceMock = Mockery::mock(PaymobService::class);
        $paymobServiceMock->shouldReceive('authenticate')
            ->andReturn('fake-auth-token');
        $paymobServiceMock->shouldReceive('createOrder')
            ->andReturn(['id' => rand(10000, 99999), 'created_at' => now()->toISOString()]);
        $paymobServiceMock->shouldReceive('generatePaymentKey')
            ->andReturn('fake-payment-key');
        $paymobServiceMock->shouldReceive('getPaymentUrl')
            ->andReturn('https://accept.paymob.com/acceptance/iframes/12345?payment_token=fake-payment-key');

        $this->app->instance(PaymobService::class, $paymobServiceMock);

        // Freeze time at a clean second boundary to avoid microsecond precision issues with DB storage
        $creationTime = Carbon::now()->startOfSecond();
        Carbon::setTestNow($creationTime);

        // Act: Create a payment session via InitiatePaymentAction
        $action = $this->app->make(InitiatePaymentAction::class);
        $session = $action->execute($store, $plan, 'monthly');

        // Assert: expires_at must equal creation_time + TTL
        $expectedExpiresAt = $creationTime->copy()->addMinutes($ttlMinutes);

        $this->assertNotNull($session->expires_at, 'Payment session must have an expires_at timestamp.');
        $this->assertTrue(
            $session->expires_at->equalTo($expectedExpiresAt),
            "expires_at ({$session->expires_at->toISOString()}) must equal creation_time + TTL ({$expectedExpiresAt->toISOString()}) for TTL={$ttlMinutes} minutes."
        );

        // Cleanup frozen time
        Carbon::setTestNow();
    }

    /**
     * @dataProvider randomTtlProvider
     */
    public function test_session_past_expires_at_is_treated_as_expired(int $ttlMinutes): void
    {
        // Arrange: Create a session with a specific TTL that has already passed
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $createdAt = Carbon::now()->subMinutes($ttlMinutes + 1);
        $expiresAt = $createdAt->copy()->addMinutes($ttlMinutes);

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => $plan->price_monthly,
            'currency' => 'EGP',
            'status' => \App\Domain\Subscription\Enums\PaymentSessionStatus::PENDING,
            'paymob_order_id' => 'test_order_' . rand(10000, 99999),
            'payment_url' => 'https://example.com/pay',
            'expires_at' => $expiresAt,
        ]);

        // Assert: Session where now > expires_at must be treated as expired
        $this->assertTrue(
            $session->isExpired(),
            "Session with expires_at ({$expiresAt->toISOString()}) in the past must be treated as expired (isExpired() must return true)."
        );

        // Also verify isPending() returns false for expired sessions
        $this->assertFalse(
            $session->isPending(),
            'An expired session must not be considered pending.'
        );
    }

    /**
     * @dataProvider randomTtlProvider
     */
    public function test_session_before_expires_at_is_not_expired(int $ttlMinutes): void
    {
        // Arrange: Create a session with a TTL that has NOT yet passed
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $creationTime = Carbon::now();
        Carbon::setTestNow($creationTime);

        $expiresAt = $creationTime->copy()->addMinutes($ttlMinutes);

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => $plan->price_monthly,
            'currency' => 'EGP',
            'status' => \App\Domain\Subscription\Enums\PaymentSessionStatus::PENDING,
            'paymob_order_id' => 'test_order_' . rand(10000, 99999),
            'payment_url' => 'https://example.com/pay',
            'expires_at' => $expiresAt,
        ]);

        // Assert: Session where now < expires_at must NOT be treated as expired
        $this->assertFalse(
            $session->isExpired(),
            "Session with expires_at ({$expiresAt->toISOString()}) in the future must not be treated as expired."
        );

        // Also verify isPending() returns true for non-expired pending sessions
        $this->assertTrue(
            $session->isPending(),
            'A non-expired pending session must be considered pending.'
        );

        // Cleanup frozen time
        Carbon::setTestNow();
    }
}
