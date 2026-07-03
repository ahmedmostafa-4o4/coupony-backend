# AI Daily Message Limits Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enforce a production-only 15-request customer daily AI quota and subscription-plan seller daily quotas of 15/30/60.

**Architecture:** A durable `ai_message_usages` table stores one atomic daily counter per customer or store. `AiMessageQuotaService` owns limit resolution, transactional reservation, release, and quota snapshots; Pony AI controllers call it around strategy execution, while subscription resources read store usage through the same service.

**Tech Stack:** PHP 8, Laravel, Eloquent, database transactions and row locks, PHPUnit.

---

### Task 1: Add Durable Daily Quota Accounting

**Files:**
- Create: `database/migrations/2026_07_02_000001_create_ai_message_usages_table.php`
- Create: `app/Domain/PonyAI/Models/AiMessageUsage.php`
- Create: `app/Domain/PonyAI/DTOs/AiQuotaReservation.php`
- Create: `app/Domain/PonyAI/Exceptions/AiDailyLimitReachedException.php`
- Create: `app/Domain/PonyAI/Services/AiMessageQuotaService.php`
- Test: `tests/Unit/AiMessageQuotaServiceTest.php`

- [ ] **Step 1: Write failing service tests**

Create tests using `RefreshDatabase` and `Carbon::setTestNow()` for customer reservation, shared text/image-style reservations, exhaustion, release, per-user isolation, store isolation, store-user sharing, midnight reset in `config('app.timezone')`, and unlimited non-production behavior. The core assertions should follow this shape:

```php
public function test_production_customer_cannot_reserve_more_than_daily_limit(): void
{
    $this->app->detectEnvironment(fn () => 'production');
    config()->set('pony.quotas.customer_daily_limit', 15);
    $user = User::factory()->create();
    $service = app(AiMessageQuotaService::class);

    foreach (range(1, 15) as $used) {
        $reservation = $service->reserveCustomer($user);
        $this->assertSame($used, $reservation->quota['used']);
    }

    $this->expectException(AiDailyLimitReachedException::class);
    $service->reserveCustomer($user);
}

public function test_release_restores_one_available_request(): void
{
    $this->app->detectEnvironment(fn () => 'production');
    config()->set('pony.quotas.customer_daily_limit', 1);
    $service = app(AiMessageQuotaService::class);
    $user = User::factory()->create();
    $reservation = $service->reserveCustomer($user);

    $service->release($reservation);

    $this->assertSame(0, $service->reserveCustomer($user)->quota['remaining']);
}
```

Use separate tests to verify an unlimited reservation has `reserved === false`, nullable limit/remaining/reset, and creates no row.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/AiMessageQuotaServiceTest.php`

Expected: FAIL because quota classes and table do not exist.

- [ ] **Step 3: Add the usage migration and model**

The migration must define:

```php
Schema::create('ai_message_usages', function (Blueprint $table): void {
    $table->id();
    $table->date('usage_date');
    $table->string('subject_type', 20);
    $table->char('subject_id', 36);
    $table->unsignedInteger('used')->default(0);
    $table->timestamps();
    $table->unique(['usage_date', 'subject_type', 'subject_id'], 'ai_usage_subject_day_unique');
});
```

`AiMessageUsage` should cast `usage_date` to `date` and `used` to `integer`, and fill those four business columns.

- [ ] **Step 4: Add reservation and exception types**

Define an immutable reservation carrying release identity and the public quota payload:

```php
final class AiQuotaReservation
{
    public function __construct(
        public readonly string $subjectType,
        public readonly string $subjectId,
        public readonly CarbonImmutable $usageDate,
        public readonly bool $reserved,
        /** @var array{limit: ?int, used: int, remaining: ?int, resets_at: ?string} */
        public readonly array $quota,
    ) {}
}
```

`AiDailyLimitReachedException` extends `PonyAIException` and exposes the same quota array through a readonly property.

- [ ] **Step 5: Implement atomic reserve, release, and snapshots**

Implement these public methods:

```php
public function reserveCustomer(User $user): AiQuotaReservation;
public function reserveStore(Store $store): AiQuotaReservation;
public function release(AiQuotaReservation $reservation): void;
public function storeQuota(Store $store): array;
```

Customer resolution uses `app()->environment('production')` and `config('pony.quotas.customer_daily_limit', 15)`. Store resolution loads the current subscription plan through `SubscriptionRepository` and reads `max_ai_messages_per_day`.

For finite quotas, run one transaction that first calls `insertOrIgnore()` for today's composite key, then selects that row with `lockForUpdate()`. Throw `AiDailyLimitReachedException` when `used >= limit`; otherwise increment and return the post-increment snapshot. Release locks the same row and decrements with `max(0, $used - 1)`. Build `resets_at` from the next application-timezone midnight and serialize with `toIso8601String()`.

- [ ] **Step 6: Run service tests and commit**

Run: `php artisan test tests/Unit/AiMessageQuotaServiceTest.php`

Expected: PASS.

```bash
git add database/migrations/2026_07_02_000001_create_ai_message_usages_table.php app/Domain/PonyAI tests/Unit/AiMessageQuotaServiceTest.php
git commit -m "feat(ai): add durable daily quota accounting"
```

### Task 2: Add Seller Limits To Subscription Plans

**Files:**
- Create: `database/migrations/2026_07_02_000002_add_ai_message_limit_to_subscription_plans.php`
- Modify: `app/Domain/Subscription/Models/SubscriptionPlan.php`
- Modify: `database/factories/SubscriptionPlanFactory.php`
- Modify: `database/seeders/SubscriptionPlanSeeder.php`
- Modify: `app/Application/Http/Resources/SubscriptionPlanResource.php`
- Modify: `app/Application/Http/Requests/Admin/SubscriptionPlanStoreRequest.php`
- Modify: `app/Application/Http/Requests/Admin/SubscriptionPlanUpdateRequest.php`
- Modify: `app/Application/Http/Controllers/API/V1/Admin/SubscriptionPlanManagementController.php`
- Test: `tests/Feature/SubscriptionPlanAiLimitTest.php`

- [ ] **Step 1: Write failing plan limit tests**

Test that the seeder persists `basic=15`, `premium=30`, and `enterprise=60`, that `/api/v1/subscription/plans` exposes the value under `entitlements.max_ai_messages_per_day`, and that admin create/update validation accepts non-negative limits while rejecting negative values.

```php
$this->seed(SubscriptionPlanSeeder::class);

$this->assertDatabaseHas('subscription_plans', ['slug' => 'basic', 'max_ai_messages_per_day' => 15]);
$this->assertDatabaseHas('subscription_plans', ['slug' => 'premium', 'max_ai_messages_per_day' => 30]);
$this->assertDatabaseHas('subscription_plans', ['slug' => 'enterprise', 'max_ai_messages_per_day' => 60]);
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/SubscriptionPlanAiLimitTest.php`

Expected: FAIL because the plan column does not exist.

- [ ] **Step 3: Add and expose the plan field**

Add an unsigned integer column with default `15`. Add `max_ai_messages_per_day` to model fillable/casts, set a sensible factory value, seed 15/30/60 for existing Basic/Premium/Enterprise slugs, and add this key to `SubscriptionPlanResource`:

```php
'max_ai_messages_per_day' => $this->max_ai_messages_per_day,
```

Add `max_ai_messages_per_day => ['required', 'integer', 'min:0']` to the store request and `['sometimes', 'required', 'integer', 'min:0']` to the update request. Include the field in the plan version-copy list in `SubscriptionPlanManagementController::update()` so an omitted value is inherited from the previous version.

- [ ] **Step 4: Run tests and commit**

Run: `php artisan test tests/Feature/SubscriptionPlanAiLimitTest.php tests/Unit/AiMessageQuotaServiceTest.php`

Expected: PASS.

```bash
git add database/migrations/2026_07_02_000002_add_ai_message_limit_to_subscription_plans.php app/Domain/Subscription/Models/SubscriptionPlan.php database/factories/SubscriptionPlanFactory.php database/seeders/SubscriptionPlanSeeder.php app/Application/Http/Resources/SubscriptionPlanResource.php app/Application/Http/Requests/Admin/SubscriptionPlanStoreRequest.php app/Application/Http/Requests/Admin/SubscriptionPlanUpdateRequest.php app/Application/Http/Controllers/API/V1/Admin/SubscriptionPlanManagementController.php tests/Feature/SubscriptionPlanAiLimitTest.php
git commit -m "feat(subscription): configure daily AI message limits"
```

### Task 3: Enforce Quotas On Pony AI Endpoints

**Files:**
- Modify: `config/pony.php`
- Modify: `app/Application/Http/Controllers/API/V1/PonyAI/CustomerChatController.php`
- Modify: `app/Application/Http/Controllers/API/V1/PonyAI/SellerChatController.php`
- Modify: `lang/en/api.php`
- Modify: `lang/ar/api.php`
- Create: `tests/Feature/PonyAI/DailyMessageQuotaTest.php`

- [ ] **Step 1: Write failing endpoint tests**

Use `GeminiFakeClient`, authenticated users, and low configured/plan limits to avoid 15-60 request loops. Cover combined customer text/image consumption, HTTP 429 payload, non-production bypass, shared seller-store usage, store isolation, validation/conversation failures not consuming quota, AI exceptions releasing quota, and successful response quota data.

```php
config()->set('pony.quotas.customer_daily_limit', 1);
$this->app->detectEnvironment(fn () => 'production');

$this->actingAs($user, 'sanctum')
    ->postJson('/api/v1/pony/customer/chat', ['message' => 'first'])
    ->assertOk()
    ->assertJsonPath('data.quota.remaining', 0);

$this->actingAs($user, 'sanctum')
    ->postJson('/api/v1/pony/customer/image-search', ['image' => $image])
    ->assertStatus(429)
    ->assertJsonPath('error_code', 'AI_DAILY_LIMIT_REACHED')
    ->assertJsonPath('quota.remaining', 0);
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/PonyAI/DailyMessageQuotaTest.php`

Expected: FAIL because controllers do not reserve quota or return quota payloads.

- [ ] **Step 3: Add quota configuration and translations**

Add:

```php
'quotas' => [
    'customer_daily_limit' => (int) env('PONY_CUSTOMER_DAILY_LIMIT', 15),
],
```

Add English and Arabic `api.pony.daily_limit_reached` translations. Do not add a separate production flag.

- [ ] **Step 4: Integrate customer quota enforcement**

Inject `AiMessageQuotaService`. In both `store()` and `imageSearch()`, reserve after conversation validation. Catch `AiDailyLimitReachedException` and return:

```php
return $this->localizedJson([
    'success' => false,
    'message' => __('api.pony.daily_limit_reached'),
    'error_code' => 'AI_DAILY_LIMIT_REACHED',
    'quota' => $exception->quota,
], 429);
```

On `PonyAIException` or `Throwable`, release the non-null reservation before returning the existing response. On success, merge `quota` beside the reply fields in `data` without changing the reply resource's existing keys.

- [ ] **Step 5: Integrate seller quota enforcement**

Apply the same pattern to `SellerChatController::store()`, calling `reserveStore($store)` after authorization and conversation validation. Read/list/delete endpoints must never consume quota.

- [ ] **Step 6: Run endpoint and regression tests, then commit**

Run: `php artisan test tests/Feature/PonyAI/DailyMessageQuotaTest.php tests/Feature/PonyAI/CustomerChatTest.php tests/Feature/PonyAI/CustomerImageSearchTest.php tests/Feature/PonyAI/SellerChatAuthorizationTest.php tests/Feature/PonyAI/RateLimitTest.php tests/Integration/SubscriptionEnforcementTest.php`

Expected: PASS; existing minute-based 429 tests remain distinguishable from `AI_DAILY_LIMIT_REACHED`.

```bash
git add config/pony.php app/Application/Http/Controllers/API/V1/PonyAI lang/en/api.php lang/ar/api.php tests/Feature/PonyAI/DailyMessageQuotaTest.php
git commit -m "feat(ai): enforce customer and seller daily quotas"
```

### Task 4: Report Seller AI Usage In Entitlements

**Files:**
- Modify: `app/Domain/Subscription/Services/EntitlementService.php`
- Modify: `tests/Unit/EntitlementServiceTest.php`
- Modify: `tests/Property/EntitlementArithmeticPropertyTest.php`

- [ ] **Step 1: Write failing entitlement tests**

Inject/reserve store quota usage and assert:

```php
$entitlements = app(EntitlementService::class)->getEntitlements($store);

$this->assertSame(30, $entitlements->limits['ai_messages']['limit']);
$this->assertSame(2, $entitlements->limits['ai_messages']['usage']);
$this->assertSame(28, $entitlements->limits['ai_messages']['remaining']);
```

Also assert no-subscription entitlements return zeros and extend the arithmetic property provider/assertions to include `ai_messages`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/EntitlementServiceTest.php tests/Property/EntitlementArithmeticPropertyTest.php`

Expected: FAIL because `ai_messages` is absent.

- [ ] **Step 3: Add AI usage to entitlement calculation**

Inject `AiMessageQuotaService` into `EntitlementService`. Add zeroed `ai_messages` to the no-subscription response. For subscribed stores, call `storeQuota($store)` and append its integer `limit`, `used` mapped to `usage`, and `remaining` values.

- [ ] **Step 4: Run tests and commit**

Run: `php artisan test tests/Unit/EntitlementServiceTest.php tests/Property/EntitlementArithmeticPropertyTest.php tests/Feature/PonyAI/DailyMessageQuotaTest.php`

Expected: PASS.

```bash
git add app/Domain/Subscription/Services/EntitlementService.php tests/Unit/EntitlementServiceTest.php tests/Property/EntitlementArithmeticPropertyTest.php
git commit -m "feat(subscription): report daily AI quota usage"
```

### Task 5: Final Quality And Verification

**Files:**
- Review all files changed in Tasks 1-4.

- [ ] **Step 1: Run formatting**

Run: `vendor/bin/pint app/Domain/PonyAI app/Domain/Subscription app/Application/Http/Controllers/API/V1/PonyAI database/migrations database/factories/SubscriptionPlanFactory.php database/seeders/SubscriptionPlanSeeder.php tests/Unit/AiMessageQuotaServiceTest.php tests/Feature/PonyAI/DailyMessageQuotaTest.php tests/Feature/SubscriptionPlanAiLimitTest.php`

Expected: exit code 0.

- [ ] **Step 2: Run focused verification**

Run: `php artisan test tests/Unit/AiMessageQuotaServiceTest.php tests/Feature/PonyAI/DailyMessageQuotaTest.php tests/Feature/SubscriptionPlanAiLimitTest.php tests/Unit/EntitlementServiceTest.php tests/Property/EntitlementArithmeticPropertyTest.php tests/Feature/PonyAI/RateLimitTest.php tests/Integration/SubscriptionEnforcementTest.php`

Expected: all tests pass with no warnings.

- [ ] **Step 3: Run full test suite**

Run: `php artisan test`

Expected: all tests pass. Record any unrelated pre-existing failures rather than changing unrelated code.

- [ ] **Step 4: Verify migrations and diff hygiene**

Run: `php artisan migrate:fresh --env=testing`

Expected: migrations complete successfully.

Run: `git diff --check && git status --short`

Expected: no whitespace errors; status contains only intentional feature files plus any pre-existing unrelated changes.

- [ ] **Step 5: Commit formatting-only changes if present**

```bash
git add app config/pony.php database lang tests
git commit -m "style: format AI quota implementation"
```

Skip this commit when Pint produced no changes. Do not stage pre-existing unrelated files.
