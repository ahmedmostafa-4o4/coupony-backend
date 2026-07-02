# Subscription Cancel And Plan Change Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add store-owner cancel-at-period-end support and make paid plan changes work for active subscriptions.

**Architecture:** Keep the existing payment entry points and state machine. Add a focused store-owner cancel action, route it through `SubscriptionController`, and adjust successful payment handlers so active subscriptions update in place instead of attempting an invalid `active -> active` transition. Extend the active-period expiry command to archive canceled subscriptions at period end while preserving grace behavior for uncanceled subscriptions.

**Tech Stack:** Laravel, PHP, PHPUnit/Pest-style PHPUnit tests, Sanctum authentication, Eloquent models.

---

### Task 1: Store-Owner Cancel Endpoint

**Files:**
- Create: `app/Domain/Subscription/Actions/CancelSubscriptionAtPeriodEndAction.php`
- Modify: `app/Application/Http/Controllers/API/V1/SubscriptionController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/SubscriptionCancellationTest.php`
- Modify: `tests/Property/StoreOwnershipAuthorizationPropertyTest.php`

- [ ] **Step 1: Write failing feature tests**

Create `tests/Feature/SubscriptionCancellationTest.php` with tests that:

```php
public function test_store_owner_can_cancel_active_subscription_at_period_end(): void
{
    $owner = User::factory()->create();
    $store = Store::factory()->create(['owner_user_id' => $owner->id]);
    $plan = SubscriptionPlan::factory()->create();
    $periodEnd = now()->addDays(10);

    $subscription = Subscription::create([
        'store_id' => $store->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->subDays(20),
        'current_period_end' => $periodEnd,
    ]);

    $response = $this->actingAs($owner, 'sanctum')
        ->postJson("/api/v1/stores/{$store->id}/subscription/cancel");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'active');

    $subscription->refresh();
    $this->assertNotNull($subscription->cancelled_at);
    $this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
    $this->assertTrue($periodEnd->equalTo($subscription->current_period_end));
}
```

Also add data-driven rejection tests for `suspended`, `archived`, and already-canceled subscriptions that assert HTTP 422 and no status change.

- [ ] **Step 2: Verify tests fail**

Run:

```bash
php artisan test tests/Feature/SubscriptionCancellationTest.php
```

Expected: route not found or method missing for `/subscription/cancel`.

- [ ] **Step 3: Implement minimal cancel action and route**

Create `CancelSubscriptionAtPeriodEndAction` with an `execute(Subscription $subscription): Subscription` method. Allow only `trial`, `active`, `grace`, and `degraded`, reject subscriptions with `cancelled_at`, set `cancelled_at` to `now()`, and return `fresh()`.

Add `SubscriptionController::cancel(Request $request, Store $store): JsonResponse` that authorizes `manageSubscription`, finds the subscription by store, returns 422 for missing/invalid subscriptions, calls the action, loads `plan`, and returns `SubscriptionOverviewResource`.

Register `POST /cancel` in both store subscription route groups in `routes/api.php`.

- [ ] **Step 4: Add cancel route to authorization property coverage**

Append `['POST', '/cancel']` to `StoreOwnershipAuthorizationPropertyTest::SUBSCRIPTION_ENDPOINTS` and ensure the default empty body is enough.

- [ ] **Step 5: Verify cancel endpoint**

Run:

```bash
php artisan test tests/Feature/SubscriptionCancellationTest.php tests/Property/StoreOwnershipAuthorizationPropertyTest.php
```

Expected: all tests pass.

### Task 2: Active Subscription Plan Changes

**Files:**
- Modify: `app/Domain/Subscription/Actions/ConfirmPaymentAction.php`
- Modify: `app/Domain/Subscription/Actions/ProcessWebhookAction.php`
- Test: `tests/Unit/ConfirmPaymentActionTest.php`
- Test: `tests/Property/SuccessfulPaymentActivationPropertyTest.php`

- [ ] **Step 1: Write failing confirm-payment test**

Add a test to `ConfirmPaymentActionTest` that creates an active subscription on an old plan with `cancelled_at`, creates a paid session for a new plan, executes the action, and asserts:

```php
$this->assertEquals(SubscriptionStatus::ACTIVE, $subscription->status);
$this->assertEquals($newPlan->id, $subscription->plan_id);
$this->assertEquals('yearly', $subscription->billing_cycle->value);
$this->assertNull($subscription->cancelled_at);
```

- [ ] **Step 2: Verify confirm-payment test fails**

Run:

```bash
php artisan test tests/Unit/ConfirmPaymentActionTest.php --filter=active_subscription
```

Expected: `plan_id` remains the old plan.

- [ ] **Step 3: Implement confirm-payment active update**

In `ConfirmPaymentAction`, for paid sessions:

```php
if ($subscription->status !== SubscriptionStatus::ACTIVE) {
    $subscription = $this->stateMachine->transition(...);
}

$subscription->update([
    'plan_id' => $session->plan_id,
    'billing_cycle' => $session->billing_cycle,
    'current_period_start' => now(),
    'current_period_end' => $this->calculatePeriodEnd($session->billing_cycle),
    'cancelled_at' => null,
]);

$subscription = $subscription->fresh();
```

- [ ] **Step 4: Write failing webhook active update test**

Add a test to `SuccessfulPaymentActivationPropertyTest` or a focused unit test that creates an already-active subscription on an old plan, then processes a successful webhook for a pending session on a new plan. Assert no exception, paid session, active subscription, new `plan_id`, new `billing_cycle`, and `cancelled_at` cleared.

- [ ] **Step 5: Verify webhook test fails**

Run:

```bash
php artisan test tests/Property/SuccessfulPaymentActivationPropertyTest.php --filter=active
```

Expected: invalid `active -> active` transition or old plan remains.

- [ ] **Step 6: Implement webhook active update**

In `ProcessWebhookAction::handleSuccess`, only call the state machine when the current subscription is not already active. Then update plan, billing cycle, period dates, and `cancelled_at` for both active and newly-active subscriptions.

- [ ] **Step 7: Verify plan-change tests**

Run:

```bash
php artisan test tests/Unit/ConfirmPaymentActionTest.php tests/Property/SuccessfulPaymentActivationPropertyTest.php
```

Expected: all tests pass.

### Task 3: Canceled Subscription Lifecycle

**Files:**
- Modify: `app/Domain/Subscription/Jobs/TransitionToGraceJob.php`
- Test: `tests/Unit/TransitionToGraceJobTest.php`

- [ ] **Step 1: Write failing lifecycle tests**

Create `tests/Unit/TransitionToGraceJobTest.php` with:

```php
public function test_expired_cancelled_active_subscription_is_archived(): void
{
    $plan = SubscriptionPlan::factory()->create();
    $subscription = Subscription::create([
        'store_id' => Store::factory()->create()->id,
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::ACTIVE,
        'billing_cycle' => 'monthly',
        'current_period_start' => now()->subMonths(2),
        'current_period_end' => now()->subMinute(),
        'cancelled_at' => now()->subDays(5),
    ]);

    $this->artisan('subscription:transition-to-grace')->assertExitCode(0);

    $subscription->refresh();
    $this->assertEquals(SubscriptionStatus::ARCHIVED, $subscription->status);
    $this->assertNull($subscription->grace_period_end);
}
```

Also add a test proving an expired uncanceled active subscription still transitions to `grace`.

- [ ] **Step 2: Verify lifecycle tests fail**

Run:

```bash
php artisan test tests/Unit/TransitionToGraceJobTest.php
```

Expected: canceled subscription transitions to `grace` instead of `archived`.

- [ ] **Step 3: Implement canceled-at branch**

In `TransitionToGraceJob`, after refreshing each active expired subscription, if `cancelled_at` is not null, call `TransitionSubscriptionAction` with `SubscriptionStatus::ARCHIVED` and reason `Subscription cancelled at period end`, increment transitioned, and continue. Only set `grace_period_end` for uncanceled subscriptions.

- [ ] **Step 4: Verify lifecycle tests**

Run:

```bash
php artisan test tests/Unit/TransitionToGraceJobTest.php
```

Expected: all tests pass.

### Task 4: Final Verification

**Files:**
- All files touched above.

- [ ] **Step 1: Run targeted suite**

Run:

```bash
php artisan test tests/Feature/SubscriptionCancellationTest.php tests/Unit/ConfirmPaymentActionTest.php tests/Property/SuccessfulPaymentActivationPropertyTest.php tests/Unit/TransitionToGraceJobTest.php tests/Property/StoreOwnershipAuthorizationPropertyTest.php
```

Expected: all tests pass.

- [ ] **Step 2: Inspect diff**

Run:

```bash
git diff -- app routes tests docs/superpowers/plans/2026-07-02-subscription-cancel-plan-change.md
```

Expected: diff only includes subscription cancel/plan-change work and the plan file.
