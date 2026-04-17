<?php

namespace Tests\Feature;

use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\User\Models\Address;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
    }

    public function test_seller_can_list_their_store_addresses(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $address = Address::factory()->create(['address_line1' => '12 Nile Street']);

        $store->addresses()->attach($address->id, [
            'label' => 'main branch',
            'is_default_shipping' => true,
            'is_default_billing' => false,
        ]);

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/addresses")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $address->id)
            ->assertJsonPath('data.0.address_line1', '12 Nile Street')
            ->assertJsonPath('data.0.label', 'main branch')
            ->assertJsonPath('data.0.is_default_shipping', true);
    }

    public function test_seller_can_create_a_store_address(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);

        $this->actingAs($seller, 'sanctum')
            ->postJson("/api/v1/stores/{$store->id}/addresses", [
                'first_name' => 'Branch',
                'last_name' => 'Manager',
                'address_line1' => '45 Desert Road',
                'city' => 'Cairo',
                'state_province' => 'Cairo',
                'postal_code' => '11728',
                'country_code' => 'EG',
                'phone_number' => '+201111111111',
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'delivery_instructions' => 'Back entrance',
                'label' => 'downtown',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.address_line1', '45 Desert Road')
            ->assertJsonPath('data.label', 'downtown')
            ->assertJsonPath('data.is_default_shipping', true)
            ->assertJsonPath('data.is_default_billing', true);

        $this->assertDatabaseHas('addresses', [
            'address_line1' => '45 Desert Road',
            'city' => 'Cairo',
            'state_province' => 'Cairo',
            'country_code' => 'EG',
        ]);

        $this->assertDatabaseHas('addressables', [
            'owner_id' => $store->id,
            'owner_type' => Store::class,
            'label' => 'downtown',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);
    }

    public function test_seller_can_view_a_store_address_that_belongs_to_their_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $address = Address::factory()->create();

        $store->addresses()->attach($address->id, ['label' => 'branch']);

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/addresses/{$address->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $address->id);
    }

    public function test_seller_can_update_a_store_address_that_belongs_to_their_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $existingPrimary = Address::factory()->create();
        $address = Address::factory()->create([
            'address_line1' => 'Old Street',
            'city' => 'Giza',
        ]);

        $store->addresses()->attach($existingPrimary->id, [
            'label' => 'old primary',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        $store->addresses()->attach($address->id, [
            'label' => 'secondary',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        $this->actingAs($seller, 'sanctum')
            ->patchJson("/api/v1/stores/{$store->id}/addresses/{$address->id}", [
                'address_line1' => 'New Street',
                'city' => 'Alexandria',
                'label' => 'updated branch',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.address_line1', 'New Street')
            ->assertJsonPath('data.city', 'Alexandria')
            ->assertJsonPath('data.label', 'updated branch')
            ->assertJsonPath('data.is_default_shipping', true)
            ->assertJsonPath('data.is_default_billing', true);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'address_line1' => 'New Street',
            'city' => 'Alexandria',
        ]);

        $this->assertDatabaseHas('addressables', [
            'owner_id' => $store->id,
            'owner_type' => Store::class,
            'address_id' => $address->id,
            'label' => 'updated branch',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        $this->assertDatabaseHas('addressables', [
            'owner_id' => $store->id,
            'owner_type' => Store::class,
            'address_id' => $existingPrimary->id,
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);
    }

    public function test_seller_can_delete_a_store_address_that_belongs_to_their_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $address = Address::factory()->create();

        $store->addresses()->attach($address->id, ['label' => 'branch']);

        $this->actingAs($seller, 'sanctum')
            ->deleteJson("/api/v1/stores/{$store->id}/addresses/{$address->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('addressables', [
            'owner_id' => $store->id,
            'owner_type' => Store::class,
            'address_id' => $address->id,
        ]);

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_seller_cannot_access_another_sellers_store_addresses(): void
    {
        $seller = $this->seller();
        $otherSeller = $this->seller();
        $store = $this->storeFor($otherSeller);

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/addresses")
            ->assertForbidden();
    }

    public function test_nested_route_binding_rejects_address_that_does_not_belong_to_store(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $otherStore = $this->storeFor($seller);
        $address = Address::factory()->create();

        $otherStore->addresses()->attach($address->id, ['label' => 'other']);

        $this->actingAs($seller, 'sanctum')
            ->getJson("/api/v1/stores/{$store->id}/addresses/{$address->id}")
            ->assertNotFound();
    }

    public function test_store_responses_still_include_addresses(): void
    {
        $seller = $this->seller();
        $store = $this->storeFor($seller);
        $address = Address::factory()->create(['address_line1' => 'Store Response Street']);

        $store->addresses()->attach($address->id, ['label' => 'response branch']);

        $this->actingAs($seller, 'sanctum')
            ->getJson('/api/v1/stores')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $store->id)
            ->assertJsonPath('data.data.0.addresses.0.id', $address->id)
            ->assertJsonPath('data.data.0.addresses.0.address_line1', 'Store Response Street');
    }

    public function test_existing_store_create_and_update_flows_with_embedded_address_data_still_work(): void
    {
        $seller = $this->seller();
        $category = StoreCategory::factory()->create();

        $createResponse = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/stores', [
                'name' => 'Address Flow Store',
                'description' => 'Store with embedded address flow',
                'phone' => '+201222222222',
                'address_line1' => '123 Main Street',
                'address_line2' => 'Suite 4',
                'city' => 'Cairo',
                'latitude' => 30.1,
                'longitude' => 31.2,
                'categories' => [$category->id],
            ]);

        $storeId = $createResponse->json('data.id');

        $createResponse->assertCreated()
            ->assertJsonPath('data.addresses.0.address_line1', '123 Main Street')
            ->assertJsonPath('data.addresses.0.city', 'Cairo');

        $this->actingAs($seller, 'sanctum')
            ->putJson("/api/v1/stores/{$storeId}", [
                'name' => 'Address Flow Store',
                'description' => 'Updated address',
                'phone' => '+201222222222',
                'email' => 'seller@example.com',
                'category_ids' => [$category->id],
                'address' => [
                    'address_line1' => '456 Updated Street',
                    'address_line2' => 'Floor 2',
                    'city' => 'Alexandria',
                    'latitude' => 31.2,
                    'longitude' => 29.9,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.addresses.0.address_line1', '456 Updated Street')
            ->assertJsonPath('data.addresses.0.city', 'Alexandria')
            ->assertJsonPath('data.addresses.0.address_line2', 'Floor 2');
    }

    private function seller(): User
    {
        $seller = User::factory()->create();
        $seller->assignRole('seller');

        return $seller;
    }

    private function storeFor(User $user): Store
    {
        return Store::factory()->create([
            'owner_user_id' => $user->id,
        ]);
    }
}
