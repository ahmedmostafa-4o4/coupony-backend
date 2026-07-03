<?php

namespace Tests\Unit;

use App\Domain\Notification\Models\Notification;
use App\Domain\Notification\Services\FcmPushNotificationService;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
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
                    NotFound::becauseTokenNotFound('unknown-token')
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

        $this->assertSame(0, UserDeviceToken::query()->active()->count());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
