<?php

namespace Tests\Feature\Admin;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_retrieve_notifications()
    {
        for ($i = 0; $i < 3; $i++) {
            Notification::create([
                'user_id' => $this->admin->id,
                'status' => 'pending',
                'channel' => 'system',
                'type' => 'App\Notifications\Admin\NewStoreRegistrationNotification',
                'title' => 'Test Notification',
                'message' => 'Test Message',
                'data' => []
            ]);
        }

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'title', 'message', 'data', 'read_at', 'created_at']
                ],
                'meta' => ['current_page', 'last_page', 'total', 'unread_count']
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_admin_can_mark_notifications_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->admin->id,
            'status' => 'pending',
            'channel' => 'system',
            'type' => 'App\Notifications\Admin\NewStoreRegistrationNotification',
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/admin/notifications/mark-as-read', [
                'notification_ids' => [$notification->id]
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
