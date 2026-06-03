<?php

namespace Tests\Feature\Admin;

use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $this->admin->assignRole($adminRole);
    }

    public function test_updating_user_generates_audit_log()
    {
        $this->actingAs($this->admin, 'sanctum');

        $user = User::factory()->create([
            'phone_number' => '111111111',
            'status' => 'active'
        ]);

        // Update the user
        $user->update([
            'phone_number' => '222222222',
            'status' => 'suspended'
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_type' => User::class,
            'causer_id' => $this->admin->id,
            'event' => 'updated'
        ]);

        $log = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->first();

        $this->assertEquals('222222222', $log->properties['attributes']['phone_number']);
        $this->assertEquals('111111111', $log->properties['old']['phone_number']);
    }

    public function test_admin_can_retrieve_audit_logs()
    {
        $this->actingAs($this->admin, 'sanctum');

        // Trigger a log
        $user = User::factory()->create();
        $user->update(['phone_number' => '999999999']);

        $response = $this->getJson('/api/v1/admin/audits');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_type',
                            'event',
                            'subject_id',
                            'causer_type',
                            'causer_id',
                            'properties'
                        ]
                    ]
                ]
            ]);
    }
}
