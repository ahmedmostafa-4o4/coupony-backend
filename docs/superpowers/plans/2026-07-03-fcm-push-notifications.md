# FCM Push Notifications Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Firebase Cloud Messaging native push delivery for every existing `in_app` and `email` notification while preserving current REST, Reverb, and email behavior.

**Architecture:** Store Flutter FCM registration tokens per authenticated user, then fan out native push from `NotificationService` after successful `in_app` or `email` delivery. Use a queued job plus a focused FCM delivery service so notification creation never fails because Firebase is unavailable.

**Tech Stack:** Laravel 12, Sanctum, queues, PHPUnit, `kreait/laravel-firebase`, `Kreait\Firebase\Contract\Messaging`.

---

## File Structure

- Create `database/migrations/2026_07_03_000001_create_user_device_tokens_table.php`: stores FCM tokens and revocation state.
- Create `app/Domain/User/Models/UserDeviceToken.php`: Eloquent model for active/revoked device tokens.
- Create `database/factories/UserDeviceTokenFactory.php`: focused test data for active/revoked FCM tokens.
- Modify `app/Domain/User/Models/User.php`: add `deviceTokens()` relation.
- Create `app/Application/Http/Controllers/API/V1/DeviceTokenController.php`: authenticated register/unregister API.
- Modify `routes/api.php`: add `/api/v1/me/device-tokens` routes inside the existing authenticated v1 group.
- Create `tests/Feature/DeviceTokenApiTest.php`: token registration/unregistration behavior.
- Create `app/Domain/Notification/Services/FcmPushNotificationService.php`: builds payload, sends multicast, revokes invalid tokens.
- Create `app/Domain/Notification/Jobs/SendFcmPushNotificationJob.php`: queued wrapper around FCM delivery.
- Modify `app/Domain/Notification/Services/NotificationService.php`: dispatch FCM job after successful `in_app` or `email` notification sends when preferences allow it.
- Create `tests/Unit/FcmPushNotificationServiceTest.php`: payload and token cleanup behavior.
- Update `tests/Unit/NotificationServiceTest.php`: job dispatch and preference behavior.
- Update `FLUTTER_NOTIFICATIONS.md`: document native push token API and clarify that Reverb is still used for foreground/realtime sync.

---

### Task 1: Device Token Persistence

**Files:**
- Create: `database/migrations/2026_07_03_000001_create_user_device_tokens_table.php`
- Create: `app/Domain/User/Models/UserDeviceToken.php`
- Create: `database/factories/UserDeviceTokenFactory.php`
- Modify: `app/Domain/User/Models/User.php`
- Test: `tests/Feature/DeviceTokenApiTest.php`

- [ ] **Step 1: Write the failing model test**

Create `tests/Feature/DeviceTokenApiTest.php` with the relation and active-scope assertions first:

```php
<?php

namespace Tests\Feature;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_active_device_tokens_relation(): void
    {
        $user = User::factory()->create();
        UserDeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'active-token',
            'revoked_at' => null,
        ]);
        UserDeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'revoked-token',
            'revoked_at' => now(),
        ]);

        $this->assertSame(
            ['active-token'],
            $user->deviceTokens()->active()->pluck('token')->all()
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run:

```bash
php artisan test tests/Feature/DeviceTokenApiTest.php --filter=user_has_active_device_tokens_relation
```

Expected: FAIL because `UserDeviceToken` does not exist.

- [ ] **Step 3: Add migration**

Create `database/migrations/2026_07_03_000001_create_user_device_tokens_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->text('token');
            $table->string('platform', 20)->default('unknown');
            $table->string('device_id')->nullable();
            $table->string('app_version', 50)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique('token');
            $table->index(['user_id', 'revoked_at'], 'idx_user_device_tokens_active');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_device_tokens');
    }
};
```

- [ ] **Step 4: Add model and factory**

Create `app/Domain/User/Models/UserDeviceToken.php`:

```php
<?php

namespace App\Domain\User\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_id',
        'app_version',
        'last_used_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\UserDeviceTokenFactory::new();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function revoke(): bool
    {
        return $this->update(['revoked_at' => now()]);
    }
}
```

Create `database/factories/UserDeviceTokenFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Domain\User\Models\User;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDeviceTokenFactory extends Factory
{
    protected $model = UserDeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => fake()->sha256(),
            'platform' => fake()->randomElement(['ios', 'android', 'web', 'unknown']),
            'device_id' => fake()->optional()->uuid(),
            'app_version' => fake()->optional()->semver(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}
```

- [ ] **Step 5: Add user relation**

Modify `app/Domain/User/Models/User.php`:

```php
public function deviceTokens()
{
    return $this->hasMany(UserDeviceToken::class);
}
```

Place it near `sessions()` and other user-owned relations. Because `UserDeviceToken` is in the same namespace, no import is required.

- [ ] **Step 6: Run test to verify it passes**

Run:

```bash
php artisan test tests/Feature/DeviceTokenApiTest.php --filter=user_has_active_device_tokens_relation
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_03_000001_create_user_device_tokens_table.php app/Domain/User/Models/UserDeviceToken.php database/factories/UserDeviceTokenFactory.php app/Domain/User/Models/User.php tests/Feature/DeviceTokenApiTest.php
git commit -m "feat: add user device token model"
```

---

### Task 2: Device Token API

**Files:**
- Create: `app/Application/Http/Controllers/API/V1/DeviceTokenController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/DeviceTokenApiTest.php`

- [ ] **Step 1: Add failing API tests**

Append these tests to `tests/Feature/DeviceTokenApiTest.php`:

```php
public function test_authenticated_user_can_register_device_token(): void
{
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/me/device-tokens', [
            'token' => 'fcm-token-1',
            'platform' => 'android',
            'device_id' => 'pixel-8',
            'app_version' => '1.2.3',
        ])
        ->assertOk()
        ->assertJsonPath('data.token', 'fcm-token-1')
        ->assertJsonPath('data.platform', 'android')
        ->assertJsonPath('data.device_id', 'pixel-8')
        ->assertJsonPath('data.app_version', '1.2.3');

    $this->assertDatabaseHas('user_device_tokens', [
        'user_id' => $user->id,
        'token' => 'fcm-token-1',
        'platform' => 'android',
        'device_id' => 'pixel-8',
        'app_version' => '1.2.3',
        'revoked_at' => null,
    ]);
}

public function test_registering_existing_token_reassigns_and_reactivates_it(): void
{
    $oldUser = User::factory()->create();
    $newUser = User::factory()->create();
    UserDeviceToken::factory()->create([
        'user_id' => $oldUser->id,
        'token' => 'shared-token',
        'platform' => 'ios',
        'revoked_at' => now(),
    ]);

    $this->actingAs($newUser, 'sanctum')
        ->postJson('/api/v1/me/device-tokens', [
            'token' => 'shared-token',
            'platform' => 'android',
        ])
        ->assertOk();

    $this->assertDatabaseHas('user_device_tokens', [
        'user_id' => $newUser->id,
        'token' => 'shared-token',
        'platform' => 'android',
        'revoked_at' => null,
    ]);
}

public function test_authenticated_user_can_unregister_own_device_token(): void
{
    $user = User::factory()->create();
    UserDeviceToken::factory()->create([
        'user_id' => $user->id,
        'token' => 'logout-token',
        'revoked_at' => null,
    ]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/me/device-tokens', ['token' => 'logout-token'])
        ->assertNoContent();

    $this->assertNotNull(
        UserDeviceToken::query()->where('token', 'logout-token')->firstOrFail()->revoked_at
    );
}

public function test_user_cannot_unregister_another_users_device_token(): void
{
    $owner = User::factory()->create();
    $other = User::factory()->create();
    UserDeviceToken::factory()->create([
        'user_id' => $owner->id,
        'token' => 'owned-token',
        'revoked_at' => null,
    ]);

    $this->actingAs($other, 'sanctum')
        ->deleteJson('/api/v1/me/device-tokens', ['token' => 'owned-token'])
        ->assertNoContent();

    $this->assertNull(
        UserDeviceToken::query()->where('token', 'owned-token')->firstOrFail()->revoked_at
    );
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test tests/Feature/DeviceTokenApiTest.php
```

Expected: FAIL with 404 for `/api/v1/me/device-tokens`.

- [ ] **Step 3: Add controller**

Create `app/Application/Http/Controllers/API/V1/DeviceTokenController.php`:

```php
<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'platform' => ['nullable', 'string', 'in:ios,android,web,unknown'],
            'device_id' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ]);

        $deviceToken = UserDeviceToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'] ?? 'unknown',
                'device_id' => $validated['device_id'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'last_used_at' => now(),
                'revoked_at' => null,
            ]
        );

        return $this->localizedJson([
            'data' => [
                'token' => $deviceToken->token,
                'platform' => $deviceToken->platform,
                'device_id' => $deviceToken->device_id,
                'app_version' => $deviceToken->app_version,
                'last_used_at' => $deviceToken->last_used_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);

        UserDeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return response()->json(null, 204);
    }
}
```

- [ ] **Step 4: Add routes**

Modify `routes/api.php` imports:

```php
use App\Application\Http\Controllers\API\V1\DeviceTokenController;
```

Inside the existing authenticated group that already contains `/me/notifications`, add:

```php
Route::post('/me/device-tokens', [DeviceTokenController::class, 'store'])->name('me.device-tokens.store');
Route::delete('/me/device-tokens', [DeviceTokenController::class, 'destroy'])->name('me.device-tokens.destroy');
```

- [ ] **Step 5: Run tests to verify they pass**

Run:

```bash
php artisan test tests/Feature/DeviceTokenApiTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Application/Http/Controllers/API/V1/DeviceTokenController.php routes/api.php tests/Feature/DeviceTokenApiTest.php
git commit -m "feat: add device token API"
```

---

### Task 3: FCM Delivery Service

**Files:**
- Create: `app/Domain/Notification/Services/FcmPushNotificationService.php`
- Test: `tests/Unit/FcmPushNotificationServiceTest.php`

- [ ] **Step 1: Write failing service tests**

Create `tests/Unit/FcmPushNotificationServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\FcmPushNotificationService;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\SendReport;
use Mockery;
use Tests\TestCase;

class FcmPushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_notification_to_active_tokens_with_navigation_payload(): void
    {
        $user = User::factory()->create();
        UserDeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'active-token']);
        UserDeviceToken::factory()->revoked()->create(['user_id' => $user->id, 'token' => 'revoked-token']);
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'offer_redeemed',
            'title' => 'Offer redeemed',
            'message' => 'Your offer was redeemed.',
            'channel' => 'in_app',
            'data' => ['claim_id' => 'claim-1'],
            'reference_type' => 'offer_claim',
            'reference_id' => 'claim-1',
        ]);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(function (array $message, array $tokens) {
                $this->assertSame(['active-token'], $tokens);
                $this->assertSame('Offer redeemed', $message['notification']['title']);
                $this->assertSame('Your offer was redeemed.', $message['notification']['body']);
                $this->assertSame('offer_redeemed', $message['data']['type']);
                $this->assertSame('claim-1', $message['data']['reference_id']);
                $this->assertSame('{"claim_id":"claim-1"}', $message['data']['data']);

                return true;
            })
            ->andReturn(MulticastSendReport::withItems([
                SendReport::success(MessageTarget::with(MessageTarget::TOKEN, 'active-token'), []),
            ]));

        app(FcmPushNotificationService::class, ['messaging' => $messaging])->send($notification);
    }

    public function test_it_revokes_invalid_and_unknown_tokens(): void
    {
        $user = User::factory()->create();
        UserDeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'invalid-token']);
        UserDeviceToken::factory()->create(['user_id' => $user->id, 'token' => 'unknown-token']);
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->andReturn(MulticastSendReport::withItems([
                SendReport::failure(
                    MessageTarget::with(MessageTarget::TOKEN, 'invalid-token'),
                    new \RuntimeException('The registration token is not a valid FCM registration token')
                ),
                SendReport::failure(
                    MessageTarget::with(MessageTarget::TOKEN, 'unknown-token'),
                    new \Kreait\Firebase\Exception\Messaging\NotFound('Token not found')
                ),
            ]));

        app(FcmPushNotificationService::class, ['messaging' => $messaging])->send($notification);

        $this->assertNotNull(UserDeviceToken::query()->where('token', 'invalid-token')->firstOrFail()->revoked_at);
        $this->assertNotNull(UserDeviceToken::query()->where('token', 'unknown-token')->firstOrFail()->revoked_at);
    }

    public function test_it_does_not_call_firebase_when_user_has_no_active_tokens(): void
    {
        $notification = Notification::factory()->create();
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldNotReceive('sendMulticast');

        app(FcmPushNotificationService::class, ['messaging' => $messaging])->send($notification);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test tests/Unit/FcmPushNotificationServiceTest.php
```

Expected: FAIL because `FcmPushNotificationService` does not exist.

- [ ] **Step 3: Add FCM service**

Create `app/Domain/Notification/Services/FcmPushNotificationService.php`:

```php
<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Throwable;

class FcmPushNotificationService
{
    public function __construct(private readonly Messaging $messaging) {}

    public function send(Notification $notification): void
    {
        $notification->loadMissing('user.deviceTokens');

        $tokens = $notification->user?->deviceTokens()
            ->active()
            ->pluck('token')
            ->all() ?? [];

        if ($tokens === []) {
            return;
        }

        try {
            $report = $this->messaging->sendMulticast($this->payload($notification), $tokens);
        } catch (Throwable $e) {
            Log::error('FCM push notification failed', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'token_count' => count($tokens),
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $invalidTokens = array_values(array_unique([
            ...$report->invalidTokens(),
            ...$report->unknownTokens(),
        ]));

        if ($invalidTokens !== []) {
            UserDeviceToken::query()
                ->whereIn('token', $invalidTokens)
                ->update(['revoked_at' => now()]);
        }

        if ($report->hasFailures()) {
            Log::warning('FCM push notification completed with failures', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
                'token_count' => count($tokens),
                'invalid_token_count' => count($invalidTokens),
            ]);
        }
    }

    public function payload(Notification $notification): array
    {
        return [
            'notification' => [
                'title' => $notification->title,
                'body' => $notification->message,
            ],
            'data' => [
                'notification_id' => (string) $notification->id,
                'type' => (string) $notification->type,
                'reference_type' => (string) ($notification->reference_type ?? ''),
                'reference_id' => (string) ($notification->reference_id ?? ''),
                'channel' => (string) $notification->channel,
                'data' => json_encode($notification->data ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
            ],
        ];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run:

```bash
php artisan test tests/Unit/FcmPushNotificationServiceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Domain/Notification/Services/FcmPushNotificationService.php tests/Unit/FcmPushNotificationServiceTest.php
git commit -m "feat: add FCM push delivery service"
```

---

### Task 4: Queued Push Fan-Out

**Files:**
- Create: `app/Domain/Notification/Jobs/SendFcmPushNotificationJob.php`
- Modify: `app/Domain/Notification/Services/NotificationService.php`
- Test: `tests/Unit/NotificationServiceTest.php`

- [ ] **Step 1: Add failing fan-out tests**

Append these tests to `tests/Unit/NotificationServiceTest.php`:

```php
public function test_send_dispatches_fcm_job_for_in_app_notifications_when_push_allowed(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $user = User::factory()->create();

    $notification = $this->notificationService->send(
        user: $user,
        type: 'test_notification',
        title: 'Test Title',
        message: 'Test Message',
        channel: 'in_app'
    );

    \Illuminate\Support\Facades\Queue::assertPushed(
        \App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class,
        fn ($job) => $job->notificationId === $notification->id
    );
}

public function test_send_dispatches_fcm_job_for_email_notifications_when_push_allowed(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    \Illuminate\Support\Facades\Mail::fake();
    $user = User::factory()->create(['email' => 'seller@example.com']);

    $notification = $this->notificationService->send(
        user: $user,
        type: 'store_approved',
        title: 'Store approved',
        message: 'Your store was approved.',
        channel: 'email'
    );

    \Illuminate\Support\Facades\Queue::assertPushed(
        \App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class,
        fn ($job) => $job->notificationId === $notification->id
    );
}

public function test_send_does_not_dispatch_fcm_job_when_push_preference_is_disabled(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $user = User::factory()->create();
    \App\Domain\User\Models\UserPreference::factory()->create([
        'user_id' => $user->id,
        'push_notifications' => false,
    ]);

    $this->notificationService->send(
        user: $user,
        type: 'test_notification',
        title: 'Test Title',
        message: 'Test Message',
        channel: 'in_app'
    );

    \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class);
}

public function test_send_does_not_dispatch_fcm_job_for_sms_or_push_channels(): void
{
    \Illuminate\Support\Facades\Queue::fake();
    $user = User::factory()->create();

    $this->notificationService->send(
        user: $user,
        type: 'sms_test',
        title: 'SMS',
        message: 'SMS',
        channel: 'sms'
    );

    $this->notificationService->send(
        user: $user,
        type: 'push_test',
        title: 'Push',
        message: 'Push',
        channel: 'push'
    );

    \Illuminate\Support\Facades\Queue::assertNotPushed(\App\Domain\Notification\Jobs\SendFcmPushNotificationJob::class);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run:

```bash
php artisan test tests/Unit/NotificationServiceTest.php --filter=fcm
```

Expected: FAIL because `SendFcmPushNotificationJob` does not exist.

- [ ] **Step 3: Add queued job**

Create `app/Domain/Notification/Jobs/SendFcmPushNotificationJob.php`:

```php
<?php

namespace App\Domain\Notification\Jobs;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\FcmPushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFcmPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $notificationId) {}

    public function handle(FcmPushNotificationService $pushNotifications): void
    {
        $notification = Notification::query()->find($this->notificationId);

        if (! $notification) {
            return;
        }

        $pushNotifications->send($notification);
    }
}
```

- [ ] **Step 4: Modify NotificationService**

In `app/Domain/Notification/Services/NotificationService.php`, import the job:

```php
use App\Domain\Notification\Jobs\SendFcmPushNotificationJob;
```

After `event(new NotificationSent($notification, $user));` in `send()`, add:

```php
$this->dispatchFcmPushIfNeeded($notification, $user, $channel);
```

Add these private methods near `canSendToChannel()`:

```php
private function dispatchFcmPushIfNeeded(Notification $notification, User $user, string $channel): void
{
    if (! in_array($channel, ['in_app', 'email'], true)) {
        return;
    }

    if (! $this->canSendToChannel($user, 'push')) {
        return;
    }

    SendFcmPushNotificationJob::dispatch($notification->id);
}
```

- [ ] **Step 5: Run targeted tests**

Run:

```bash
php artisan test tests/Unit/NotificationServiceTest.php --filter=fcm
```

Expected: PASS.

- [ ] **Step 6: Run full notification service tests**

Run:

```bash
php artisan test tests/Unit/NotificationServiceTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Domain/Notification/Jobs/SendFcmPushNotificationJob.php app/Domain/Notification/Services/NotificationService.php tests/Unit/NotificationServiceTest.php
git commit -m "feat: queue FCM push for notifications"
```

---

### Task 5: Flutter Integration Documentation

**Files:**
- Modify: `FLUTTER_NOTIFICATIONS.md`

- [ ] **Step 1: Update documentation**

In `FLUTTER_NOTIFICATIONS.md`, replace the note that says native push requires a separate integration with a short native push section:

```markdown
## Native Push Notifications

The backend also sends Firebase Cloud Messaging push notifications for existing `in_app` and `email` notification rows. Reverb remains the realtime foreground channel; FCM is for OS-level notifications while the app is backgrounded, closed, or outside the foreground experience.

### Register FCM token

```http
POST /api/v1/me/device-tokens
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
```

```json
{
  "token": "<FCM_TOKEN>",
  "platform": "android",
  "device_id": "optional-device-id",
  "app_version": "1.2.3"
}
```

### Unregister FCM token

```http
DELETE /api/v1/me/device-tokens
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
```

```json
{
  "token": "<FCM_TOKEN>"
}
```

Flutter should register after login, register again when Firebase rotates the token, and unregister during logout.
```

- [ ] **Step 2: Verify docs references**

Run:

```bash
rg -n "Native mobile push notifications require|/me/device-tokens|native push|FCM" FLUTTER_NOTIFICATIONS.md
```

Expected: The old “requires a separate FCM/APNs integration” wording is gone or updated, and `/me/device-tokens` is documented.

- [ ] **Step 3: Commit**

```bash
git add FLUTTER_NOTIFICATIONS.md
git commit -m "docs: document Flutter FCM token API"
```

---

### Task 6: Final Verification

**Files:**
- All changed implementation and documentation files.

- [ ] **Step 1: Run targeted test suite**

Run:

```bash
php artisan test tests/Feature/DeviceTokenApiTest.php tests/Unit/FcmPushNotificationServiceTest.php tests/Unit/NotificationServiceTest.php
```

Expected: PASS.

- [ ] **Step 2: Run broader notification tests**

Run:

```bash
php artisan test tests/Feature/NotificationApiTest.php tests/Feature/NotificationDomainTriggerTest.php tests/Feature/NotificationRealtimeTest.php tests/Unit/NotificationServiceTest.php
```

Expected: PASS.

- [ ] **Step 3: Run formatter**

Run:

```bash
vendor/bin/pint app/Domain/User/Models/UserDeviceToken.php app/Application/Http/Controllers/API/V1/DeviceTokenController.php app/Domain/Notification/Services/FcmPushNotificationService.php app/Domain/Notification/Jobs/SendFcmPushNotificationJob.php app/Domain/Notification/Services/NotificationService.php tests/Feature/DeviceTokenApiTest.php tests/Unit/FcmPushNotificationServiceTest.php tests/Unit/NotificationServiceTest.php
```

Expected: Files are formatted with no errors.

- [ ] **Step 4: Run full test suite if time allows**

Run:

```bash
php artisan test
```

Expected: PASS. If unrelated existing failures appear, capture the failing test names and commands in the final handoff.

- [ ] **Step 5: Final commit if formatting changed files**

```bash
git add app database tests routes FLUTTER_NOTIFICATIONS.md
git commit -m "chore: format FCM push notification changes"
```

Skip this commit if there are no changes after formatting and tests.

---

## Self-Review

- Spec coverage: token persistence, register/unregister API, queued fan-out for `in_app` and `email`, FCM payload, push preference opt-out, invalid token revocation, Flutter docs, and verification are all mapped to tasks.
- Placeholder scan: no `TBD`, `TODO`, or vague implementation-only steps remain.
- Type consistency: `UserDeviceToken`, `SendFcmPushNotificationJob`, `FcmPushNotificationService`, route paths, and test names are consistent across tasks.
