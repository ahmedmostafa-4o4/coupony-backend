<?php

namespace Tests\Unit;

use App\Domain\Store\Models\Store;
use App\Domain\Subscription\Actions\ConfirmPaymentAction;
use App\Domain\Subscription\Enums\HistoryStatus;
use App\Domain\Subscription\Enums\PaymentSessionStatus;
use App\Domain\Subscription\Enums\SubscriptionStatus;
use App\Domain\Subscription\Events\SubscriptionPaymentApproved;
use App\Domain\Subscription\Exceptions\PaymentSessionAlreadyUsedException;
use App\Domain\Subscription\Exceptions\PaymentSessionExpiredException;
use App\Domain\Subscription\Exceptions\PaymentSessionNotFoundException;
use App\Domain\Subscription\Models\PaymentSession;
use App\Domain\Subscription\Models\Subscription;
use App\Domain\Subscription\Models\SubscriptionHistory;
use App\Domain\Subscription\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ConfirmPaymentActionTest extends TestCase
{
    use RefreshDatabase;

    private ConfirmPaymentAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(ConfirmPaymentAction::class);
    }

    public function test_throws_not_found_when_session_does_not_exist(): void
    {
        $store = Store::factory()->create();

        $this->expectException(PaymentSessionNotFoundException::class);

        $this->action->execute($store, 'non-existent-session-id');
    }

    public function test_throws_not_found_when_session_belongs_to_different_store(): void
    {
        $store = Store::factory()->create();
        $otherStore = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $otherStore->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PAID,
            'expires_at' => now()->addMinutes(30),
            'paid_at' => now(),
        ]);

        $this->expectException(PaymentSessionNotFoundException::class);

        $this->action->execute($store, $session->id);
    }

    public function test_throws_already_used_when_session_status_is_failed(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::FAILED,
            'expires_at' => now()->addMinutes(30),
            'failed_at' => now(),
            'failure_reason' => 'Card declined',
        ]);

        $this->expectException(PaymentSessionAlreadyUsedException::class);

        $this->action->execute($store, $session->id);
    }

    public function test_throws_expired_when_session_is_expired(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->expectException(PaymentSessionExpiredException::class);

        $this->action->execute($store, $session->id);
    }

    public function test_activates_subscription_when_session_is_paid(): void
    {
        Event::fake([SubscriptionPaymentApproved::class]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 99.99,
            'grace_period_days' => 7,
            'degraded_period_days' => 14,
        ]);

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PAID,
            'expires_at' => now()->addMinutes(30),
            'paid_at' => now(),
        ]);

        $subscription = $this->action->execute($store, $session->id);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
        $this->assertEquals($store->id, $subscription->store_id);
        $this->assertEquals($plan->id, $subscription->plan_id);
    }

    public function test_creates_subscription_history_entry_on_activation(): void
    {
        Event::fake([SubscriptionPaymentApproved::class]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_monthly' => 150.00,
        ]);

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 150.00,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PAID,
            'expires_at' => now()->addMinutes(30),
            'paid_at' => now(),
        ]);

        $this->action->execute($store, $session->id);

        $history = SubscriptionHistory::where('store_id', $store->id)->first();

        $this->assertNotNull($history);
        $this->assertEquals($plan->id, $history->plan_id);
        $this->assertEquals(HistoryStatus::ACTIVE, $history->status);
        $this->assertEquals(150.00, (float) $history->amount);
        $this->assertEquals($session->id, $history->payment_session_id);
    }

    public function test_dispatches_subscription_payment_approved_event(): void
    {
        Event::fake([SubscriptionPaymentApproved::class]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PAID,
            'expires_at' => now()->addMinutes(30),
            'paid_at' => now(),
        ]);

        $this->action->execute($store, $session->id);

        Event::assertDispatched(SubscriptionPaymentApproved::class, function ($event) use ($store, $session) {
            return $event->subscription->store_id === $store->id
                && $event->session->id === $session->id;
        });
    }

    public function test_returns_existing_subscription_when_pending_session(): void
    {
        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create();

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'amount' => 99.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PENDING,
            'expires_at' => now()->addMinutes(30),
        ]);

        $subscription = $this->action->execute($store, $session->id);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals(SubscriptionStatus::NONE, $subscription->status);
    }

    public function test_activates_subscription_with_yearly_billing_cycle(): void
    {
        Event::fake([SubscriptionPaymentApproved::class]);

        $store = Store::factory()->create();
        $plan = SubscriptionPlan::factory()->create([
            'price_yearly' => 999.99,
        ]);

        $session = PaymentSession::create([
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'billing_cycle' => 'yearly',
            'amount' => 999.99,
            'currency' => 'EGP',
            'status' => PaymentSessionStatus::PAID,
            'expires_at' => now()->addMinutes(30),
            'paid_at' => now(),
        ]);

        $this->travelTo(now(), function () use ($store, $session) {
            $subscription = $this->action->execute($store, $session->id);

            $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
            // Yearly billing cycle should set period end ~1 year from now
            $diffInDays = (int) now()->diffInDays($subscription->current_period_end);
            $this->assertGreaterThanOrEqual(364, $diffInDays);
            $this->assertLessThanOrEqual(366, $diffInDays);
        });
    }
}
