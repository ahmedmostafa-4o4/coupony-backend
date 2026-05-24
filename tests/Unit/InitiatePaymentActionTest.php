<?php

namespace Tests\Unit;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\InitiatePaymentAction;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyExistsException;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionPlan;
use App\Domain\Subscription\Repositories\PaymentSessionRepository;
use App\Domain\Subscription\Services\PaymobService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InitiatePaymentActionTest extends TestCase
{
    use RefreshDatabase;

    private InitiatePaymentAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'subscription.payment_session_ttl_minutes' => 30,
            'subscription.paymob.api_key' => 'test_api_key',
            'subscription.paymob.integration_id' => '12345',
            'subscription.paymob.iframe_id' => '67890',
            'subscription.paymob.hmac_secret' => 'test_secret',
            'subscription.paymob.base_url' => 'https://accept.paymob.com/api',
        ]);

        $this->action = $this->app->make(InitiatePaymentAction::class);
    }

    public function test_creates_payment_session_successfully(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth_token_123'], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response(['id' => 99001, 'created_at' => now()->toISOString()], 200),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(['token' => 'payment_key_xyz'], 200),
        ]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 99.99,
            'price_yearly' => 999.99,
            'currency' => 'EGP',
        ]);

        $session = $this->action->execute($store, $plan, 'monthly');

        $this->assertInstanceOf(PaymentSession::class, $session);
        $this->assertEquals($store->id, $session->store_id);
        $this->assertEquals($plan->id, $session->plan_id);
        $this->assertEquals('monthly', $session->billing_cycle->value);
        $this->assertEquals(99.99, (float) $session->amount);
        $this->assertEquals('EGP', $session->currency);
        $this->assertEquals(PaymentSessionStatus::PENDING, $session->status);
        $this->assertNotNull($session->payment_url);
        $this->assertNotNull($session->expires_at);
        $this->assertEquals('99001', $session->paymob_order_id);
    }

    public function test_calculates_amount_from_plan_for_monthly_cycle(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth_token_123'], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response(['id' => 99001, 'created_at' => now()->toISOString()], 200),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(['token' => 'payment_key_xyz'], 200),
        ]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 150.00,
            'price_yearly' => 1500.00,
        ]);

        $session = $this->action->execute($store, $plan, 'monthly');

        $this->assertEquals(150.00, (float) $session->amount);
    }

    public function test_calculates_amount_from_plan_for_yearly_cycle(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth_token_123'], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response(['id' => 99001, 'created_at' => now()->toISOString()], 200),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(['token' => 'payment_key_xyz'], 200),
        ]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 150.00,
            'price_yearly' => 1500.00,
        ]);

        $session = $this->action->execute($store, $plan, 'yearly');

        $this->assertEquals(1500.00, (float) $session->amount);
    }

    public function test_rejects_when_pending_session_exists(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        // Create an existing pending session
        PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);

        $this->expectException(PaymentSessionAlreadyExistsException::class);

        $this->action->execute($store, $plan, 'monthly');
    }

    public function test_allows_new_session_when_existing_session_is_expired(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth_token_123'], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response(['id' => 99001, 'created_at' => now()->toISOString()], 200),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(['token' => 'payment_key_xyz'], 200),
        ]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 99.99,
        ]);

        // Create an expired session (should not block new session)
        PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'expires_at' => now()->subMinutes(5),
        ]);

        $session = $this->action->execute($store, $plan, 'monthly');

        $this->assertInstanceOf(PaymentSession::class, $session);
        $this->assertEquals(PaymentSessionStatus::PENDING, $session->status);
    }

    public function test_sets_expires_at_based_on_configured_ttl(): void
    {
        config(['subscription.payment_session_ttl_minutes' => 45]);

        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth_token_123'], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response(['id' => 99001, 'created_at' => now()->toISOString()], 200),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(['token' => 'payment_key_xyz'], 200),
        ]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $this->travelTo(now(), function () use ($store, $plan) {
            $session = $this->action->execute($store, $plan, 'monthly');

            // The difference between expires_at and now should be 45 minutes (within a second tolerance)
            $diffInSeconds = (int) now()->diffInSeconds($session->expires_at);
            $this->assertEqualsWithDelta(45 * 60, $diffInSeconds, 2);
        });
    }

    public function test_payment_url_is_stored_in_session(): void
    {
        Http::fake([
            'accept.paymob.com/api/auth/tokens' => Http::response(['token' => 'auth_token_123'], 200),
            'accept.paymob.com/api/ecommerce/orders' => Http::response(['id' => 99001, 'created_at' => now()->toISOString()], 200),
            'accept.paymob.com/api/acceptance/payment_keys' => Http::response(['token' => 'payment_key_xyz'], 200),
        ]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = $this->action->execute($store, $plan, 'monthly');

        $this->assertStringContainsString('payment_token=payment_key_xyz', $session->payment_url);
        $this->assertStringContainsString('iframes/67890', $session->payment_url);
    }
}
