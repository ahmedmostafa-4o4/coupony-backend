<?php

namespace Tests\Feature;

use App\Domain\Notification\Events\NotificationSent;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Tests\TestCase;

class NotificationRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
        ]);

        Broadcast::forgetDrivers();
        require base_path('routes/channels.php');
    }

    public function test_notification_sent_broadcasts_on_private_user_channel(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $channels = (new NotificationSent($notification, $user))->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame("private-users.{$user->id}", (string) $channels[0]);
    }

    public function test_notification_sent_broadcast_name_is_notification_sent(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertSame(
            'notification.sent',
            (new NotificationSent($notification, $user))->broadcastAs()
        );
    }

    public function test_notification_sent_broadcast_payload_includes_notification_and_unread_count(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'test_notification',
            'title' => 'Test title',
            'message' => 'Test message',
            'data' => ['claim_id' => 'claim-1'],
            'channel' => 'in_app',
            'status' => 'sent',
            'read_at' => null,
        ]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $payload = (new NotificationSent($notification, $user))->broadcastWith();

        $this->assertSame($notification->id, $payload['notification']['id']);
        $this->assertSame('test_notification', $payload['notification']['type']);
        $this->assertSame('Test title', $payload['notification']['title']);
        $this->assertSame('Test message', $payload['notification']['message']);
        $this->assertSame(['claim_id' => 'claim-1'], $payload['notification']['data']);
        $this->assertSame(1, $payload['unread_count']);
    }

    public function test_user_can_authorize_own_private_notification_channel(): void
    {
        $user = User::factory()->create();

        $response = Broadcast::auth($this->broadcastAuthRequest($user, "private-users.{$user->id}"));

        $this->assertArrayHasKey('auth', $response);
    }

    public function test_user_cannot_authorize_another_users_private_notification_channel(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->expectException(AccessDeniedHttpException::class);

        Broadcast::auth($this->broadcastAuthRequest($user, "private-users.{$other->id}"));
    }

    private function broadcastAuthRequest(User $user, string $channel): Request
    {
        $request = Request::create('/broadcasting/auth', 'POST', [
            'socket_id' => '123.456',
            'channel_name' => $channel,
        ]);

        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
