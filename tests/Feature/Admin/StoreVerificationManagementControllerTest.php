<?php

namespace Tests\Feature\Admin;

use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreVerificationManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $merchant;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->merchant = User::factory()->create();
    }

    public function test_admin_can_list_verifications()
    {
        $store = Store::factory()->create(['owner_user_id' => $this->merchant->id]);
        StoreVerification::factory()->count(1)->create([
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/v1/admin/store-verifications');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'store_id',
                        'status',
                        'store_details' => ['id', 'name']
                    ]
                ]
            ]);
    }

    public function test_admin_can_approve_verification()
    {
        Event::fake([
            \App\Domain\Store\Events\VerificationDocumentApproved::class,
            \App\Domain\Store\Events\VerificationDocumentRejected::class,
        ]);

        $store = Store::factory()->create(['owner_user_id' => $this->merchant->id, 'is_verified' => false]);
        $verification = StoreVerification::factory()->create([
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/store-verifications/{$verification->id}/approve", [
                'notes' => 'Looks good',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('store_verifications', [
            'id' => $verification->id,
            'status' => 'approved',
            'verified_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('stores', [
            'id' => $store->id,
            'is_verified' => true,
        ]);
        
        Event::assertDispatched(\App\Domain\Store\Events\VerificationDocumentApproved::class);
    }

    public function test_admin_can_reject_verification_with_reason()
    {
        Event::fake([
            \App\Domain\Store\Events\VerificationDocumentApproved::class,
            \App\Domain\Store\Events\VerificationDocumentRejected::class,
        ]);

        $store = Store::factory()->create(['owner_user_id' => $this->merchant->id]);
        $verification = StoreVerification::factory()->create([
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/store-verifications/{$verification->id}/reject", [
                'reason' => 'Image is blurry',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('store_verifications', [
            'id' => $verification->id,
            'status' => 'rejected',
            'rejection_reason' => 'Image is blurry',
        ]);
        
        Event::assertDispatched(\App\Domain\Store\Events\VerificationDocumentRejected::class);
    }

    public function test_admin_cannot_reject_verification_without_reason()
    {
        $store = Store::factory()->create(['owner_user_id' => $this->merchant->id]);
        $verification = StoreVerification::factory()->create([
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/v1/admin/store-verifications/{$verification->id}/reject", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_non_admin_cannot_access_endpoints()
    {
        $store = Store::factory()->create(['owner_user_id' => $this->merchant->id]);
        $verification = StoreVerification::factory()->create([
            'store_id' => $store->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->merchant, 'sanctum')
            ->getJson('/api/v1/admin/store-verifications')
            ->assertStatus(403);

        $this->actingAs($this->merchant, 'sanctum')
            ->postJson("/api/v1/admin/store-verifications/{$verification->id}/approve")
            ->assertStatus(403);
    }
}
