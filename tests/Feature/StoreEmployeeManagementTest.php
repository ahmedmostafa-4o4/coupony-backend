<?php

namespace Tests\Feature;

use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Enums\InvitationStatus;
use App\Domain\Store\Enums\StorePermission;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\Store\Models\StoreInvitation;
use App\Domain\User\Models\Address;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StoreEmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private const STORE_ROLES = [
        'store_manager',
        'store_employee',
        'branch_manager',
        'cashier',
        'inventory_manager',
        'content_manager',
        'support_agent',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['customer', 'seller', 'admin', 'super_admin', ...self::STORE_ROLES] as $role) {
            Role::query()->firstOrCreate([
                'name' => $role,
                'guard_name' => 'sanctum',
            ]);
        }
    }

    public function test_owner_can_list_employees(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/employees")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.user_id', $employee->id)
            ->assertJsonPath('data.0.user.profile.first_name', $employee->profile->first_name);
    }

    public function test_owner_can_show_employee(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $address = $this->storeAddress($store);
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ], 'store_employee', $address);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.user_id', $employee->id)
            ->assertJsonPath('data.address.id', $address->id);
    }

    public function test_owner_can_update_employee_role(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/employees/{$employee->id}", [
                'role' => 'store_manager',
            ])
            ->assertOk()
            ->assertJsonPath('data.role', 'store_manager');

        $this->assertDatabaseHas('store_employees', [
            'store_id' => $store->id,
            'user_id' => $employee->id,
            'role' => 'store_manager',
        ]);
        $this->assertTrue($employee->fresh()->hasRole('store_manager'));
    }

    public function test_owner_can_update_employee_permissions(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ]);
        $permissions = [
            StorePermission::PRODUCTS_MANAGE->value,
            StorePermission::EMPLOYEES_MANAGE->value,
        ];

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/employees/{$employee->id}", [
                'permissions' => $permissions,
            ])
            ->assertOk()
            ->assertJsonPath('data.permissions', $permissions);

        $this->assertSame($permissions, StoreEmployee::query()->where('user_id', $employee->id)->first()->permissions);
    }

    public function test_owner_can_assign_employee_to_a_store_address(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ]);
        $address = $this->storeAddress($store);

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/employees/{$employee->id}", [
                'address_id' => $address->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.address_id', $address->id)
            ->assertJsonPath('data.address.id', $address->id);
    }

    public function test_owner_cannot_remove_himself_as_store_owner(): void
    {
        [$owner, $store] = $this->storeWithOwner();

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/stores/{$store->id}/employees/{$owner->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_owner_can_remove_employee(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/stores/{$store->id}/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('store_employees', [
            'store_id' => $store->id,
            'user_id' => $employee->id,
        ]);
    }

    public function test_employee_loses_store_specific_role_if_not_employee_anywhere_else(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ], 'cashier');
        $employee->assignRole('customer');

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/stores/{$store->id}/employees/{$employee->id}")
            ->assertOk();

        $employee->refresh();
        $this->assertFalse($employee->hasRole('cashier'));
        $this->assertTrue($employee->hasRole('customer'));
    }

    public function test_accept_invitation_creates_store_employee_row(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $invitee = User::factory()->create();
        $address = $this->storeAddress($store);
        $invitation = $this->pendingInvitation($store, $owner, $invitee, 'store_employee', [
            StorePermission::CLAIMS_VIEW->value,
        ], $address);

        $this->actingAs($invitee, 'sanctum')
            ->postJson("/api/v1/invitations/{$invitation->id}/accept")
            ->assertOk();

        $this->assertDatabaseHas('store_employees', [
            'store_id' => $store->id,
            'user_id' => $invitee->id,
            'role' => 'store_employee',
            'address_id' => $address->id,
        ]);
    }

    public function test_accept_invitation_assigns_role_without_removing_existing_roles(): void
    {
        [$owner, $store] = $this->storeWithOwner();
        $invitee = User::factory()->create();
        $invitee->assignRole('customer');
        $invitation = $this->pendingInvitation($store, $owner, $invitee, 'store_employee', [
            StorePermission::CLAIMS_VIEW->value,
        ]);

        $this->actingAs($invitee, 'sanctum')
            ->postJson("/api/v1/invitations/{$invitation->id}/accept")
            ->assertOk();

        $invitee->refresh();
        $this->assertTrue($invitee->hasRole('store_employee'));
        $this->assertTrue($invitee->hasRole('customer'));
    }

    public function test_permissions_endpoint_returns_store_permission_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/store-employee-permissions')
            ->assertOk()
            ->assertJsonPath('success', true);

        $keys = collect($response->json('data'))
            ->flatMap(fn (array $group) => collect($group['permissions'])->pluck('key'))
            ->values()
            ->all();

        foreach (StorePermission::values() as $permission) {
            $this->assertContains($permission, $keys);
        }
    }

    public function test_non_owner_cannot_manage_employees_without_permission(): void
    {
        [, $store] = $this->storeWithOwner();
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ], 'cashier');

        $this->actingAs($employee, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/employees")
            ->assertForbidden();
    }

    public function test_employee_with_employee_manage_permission_can_manage_employees(): void
    {
        [, $store] = $this->storeWithOwner();
        $manager = $this->employeeFor($store, [
            StorePermission::EMPLOYEES_MANAGE->value,
        ], 'store_manager');
        $employee = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
        ]);

        $this->actingAs($manager, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/employees/{$employee->id}")
            ->assertOk()
            ->assertJsonPath('data.user_id', $employee->id);
    }

    public function test_cashier_can_access_claims_but_cannot_manage_employees(): void
    {
        [, $store] = $this->storeWithOwner();
        $cashier = $this->employeeFor($store, [
            StorePermission::CLAIMS_VIEW->value,
            StorePermission::CLAIMS_REDEEM->value,
        ], 'cashier');
        $claim = $this->claimForStore($store);

        $this->actingAs($cashier, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/offer-claims")
            ->assertOk()
            ->assertJsonPath('data.0.id', $claim->id);

        $this->actingAs($cashier, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/offer-claims/redeem", [
                'qr_code_token' => $claim->qr_code_token,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', OfferClaimStatus::REDEEMED->value);

        $this->actingAs($cashier, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/employees")
            ->assertForbidden();
    }

    private function storeWithOwner(): array
    {
        $owner = User::factory()->create();
        $owner->assignRole('seller');
        $store = Store::factory()->create(['owner_user_id' => $owner->id]);

        return [$owner, $store];
    }

    private function employeeFor(Store $store, array $permissions, string $role = 'store_employee', ?Address $address = null): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        StoreEmployee::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'address_id' => $address?->id,
            'role' => $role,
            'permissions' => $permissions,
        ]);

        return $user;
    }

    private function storeAddress(Store $store): Address
    {
        $address = Address::factory()->create();
        $store->addresses()->attach($address->id, ['label' => 'branch']);

        return $address;
    }

    private function pendingInvitation(
        Store $store,
        User $owner,
        User $invitee,
        string $role,
        array $permissions,
        ?Address $address = null
    ): StoreInvitation {
        return StoreInvitation::query()->create([
            'store_id' => $store->id,
            'invited_by_user_id' => $owner->id,
            'invitee_user_id' => $invitee->id,
            'address_id' => $address?->id,
            'role' => $role,
            'permissions' => $permissions,
            'status' => InvitationStatus::PENDING,
            'expires_at' => now()->addDay(),
        ]);
    }

    private function claimForStore(Store $store): OfferClaim
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $product = Product::factory()->active()->approved()->create(['store_id' => $store->id]);
        $offer = $product->offer()->firstOrFail();

        return OfferClaim::query()->create([
            'user_id' => $customer->id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'offer_id' => $offer->id,
            'status' => OfferClaimStatus::ACTIVE,
            'claim_token' => 'claim-'.Str::uuid(),
            'qr_code_token' => 'qr-'.Str::uuid(),
            'offer_snapshot' => [
                'selected_variants' => [],
            ],
            'expires_at' => now()->addHour(),
        ]);
    }
}
