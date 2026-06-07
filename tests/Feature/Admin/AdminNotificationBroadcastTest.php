<?php

namespace Tests\Feature\Admin;

use App\Domain\Notification\Jobs\ProcessNotificationBroadcastJob;
use App\Domain\Notification\Models\NotificationBroadcast;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminNotificationBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'sanctum']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    public function test_admin_can_queue_broadcast_notification()
    {
        Queue::fake();

        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/notifications/broadcast', [
            'title' => 'Important Update',
            'message' => 'This is a test broadcast.',
            'channels' => ['in_app'],
            'target_roles' => ['all'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Broadcast notification queued successfully.');

        $this->assertDatabaseHas('notification_broadcasts', [
            'title' => 'Important Update',
            'status' => 'pending',
            'admin_id' => $this->admin->id,
        ]);

        Queue::assertPushed(ProcessNotificationBroadcastJob::class);
    }

    public function test_non_admin_cannot_queue_broadcast()
    {
        Queue::fake();

        $response = $this->actingAs($this->customer)->postJson('/api/v1/admin/notifications/broadcast', [
            'title' => 'Important Update',
            'message' => 'This is a test broadcast.',
            'channels' => ['in_app'],
            'target_roles' => ['all'],
        ]);

        $response->assertStatus(403);
        Queue::assertNothingPushed();
    }

    public function test_admin_can_view_past_broadcasts()
    {
        NotificationBroadcast::create([
            'admin_id' => $this->admin->id,
            'title' => 'Test 1',
            'message' => 'Msg 1',
            'channels' => ['in_app'],
            'target_roles' => ['customer'],
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->getJson('/api/v1/admin/notifications/broadcasts');

        $response->assertStatus(200)
            ->assertJsonStructure(['current_page', 'data', 'total'])
            ->assertJsonPath('data.0.title', 'Test 1');
    }

    public function test_validation_requires_target_roles_or_users()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/notifications/broadcast', [
            'title' => 'Important Update',
            'message' => 'This is a test broadcast.',
            'channels' => ['in_app'],
            // Missing target_roles and target_user_ids
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target']);
    }
}
