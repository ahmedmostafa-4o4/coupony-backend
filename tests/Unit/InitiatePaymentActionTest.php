<?php

namespace Tests\Unit;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\InitiatePaymentAction;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyExistsException;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\SubscriptionPlan;
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
            'subscription.paymob.secret_key' => 'test_secret_key',
            'subscription.paymob.public_key' => 'egy_pk_test_abc123',
            'subscription.paymob.api_key' => 'test_api_key',
            'subscription.paymob.integration_id' => '12345',
            'subscription.paymob.hmac_secret' => 'test_secret',
            'subscription.paymob.base_url' => 'https://accept.paymob.com',
        ]);

        $this->action = $this->app->make(InitiatePaymentAction::class);
    }

    private function fakePaymobIntention(string $clientSecret = 'pk_test_client_secret_xyz', string $intentionId = 'intention_123'): void
    {
        Http::fake([
            'accept.paymob.com/v1/intention/' => Http::response([
                'id' => $intentionId,
                'client_secret' => $clientSecret,
                'payment_keys' => [
                    ['key' => 'key_1', 'integration' => 12345],
                ],
            ], 200),
        ]);
    }

    public function test_creates_payment_session_successfully(): void
    {
        $this->fakePaymobIntention('pk_test_secret_abc123', 'intention_99001');

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
        $this->assertNotNull($session->payment_url); // stores client_secret
        $this->assertEquals('pk_test_secret_abc123', $session->payment_url);
        $this->assertNotNull($session->expires_at);
        $this->assertEquals('intention_99001', $session->paymob_order_id);
    }

    public function test_sends_correct_amount_cents_to_paymob(): void
    {
        $this->fakePaymobIntention();

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 99.99,
            'currency' => 'EGP',
        ]);

        $this->action->execute($store, $plan, 'monthly');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://accept.paymob.com/v1/intention/'
                && $request['amount'] === 9999 // 99.99 * 100
                && $request['currency'] === 'EGP';
        });
    }

    public function test_sends_extras_with_store_and_plan_info(): void
    {
        $this->fakePaymobIntention();

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $this->action->execute($store, $plan, 'yearly');

        Http::assertSent(function ($request) use ($store, $plan) {
            return $request['extras']['store_id'] === $store->id
                && $request['extras']['plan_id'] === $plan->id
                && $request['extras']['billing_cycle'] === 'yearly';
        });
    }

    public function test_calculates_amount_from_plan_for_monthly_cycle(): void
    {
        $this->fakePaymobIntention();

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
        $this->fakePaymobIntention();

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
        $this->fakePaymobIntention();

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

        $this->fakePaymobIntention();

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $this->travelTo(now(), function () use ($store, $plan) {
            $session = $this->action->execute($store, $plan, 'monthly');

            // The difference between expires_at and now should be 45 minutes (within a second tolerance)
            $diffInSeconds = (int) now()->diffInSeconds($session->expires_at);
            $this->assertEqualsWithDelta(45 * 60, $diffInSeconds, 2);
        });
    }

    public function test_client_secret_is_stored_in_payment_url_field(): void
    {
        $this->fakePaymobIntention('pk_test_my_secret_value');

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = $this->action->execute($store, $plan, 'monthly');

        $this->assertEquals('pk_test_my_secret_value', $session->payment_url);
    }
}
