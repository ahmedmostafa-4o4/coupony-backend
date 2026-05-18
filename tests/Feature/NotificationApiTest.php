<?php

namespace Tests\Feature;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['user_id' => $user->id]);
        Notification::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_authenticated_user_can_list_unread_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/notifications/unread')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_authenticated_user_can_get_unread_count(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => null]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 2);
    }

    public function test_user_cannot_access_another_users_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/me/notifications/{$notification->id}")
            ->assertForbidden();
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/me/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_notification_as_unread(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->read()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/me/notifications/{$notification->id}/unread")
            ->assertOk()
            ->assertJsonPath('data.is_read', false);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['user_id' => $user->id, 'read_at' => null]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/me/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.updated_count', 2)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_user_can_delete_read_notifications(): void
    {
        $user = User::factory()->create();
        Notification::factory()->read()->count(2)->create(['user_id' => $user->id]);
        Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/me/notifications/read')
            ->assertOk()
            ->assertJsonPath('data.deleted_count', 2);

        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_user_can_delete_single_own_notification(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/notifications/{$notification->id}")
            ->assertOk();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }
}
