<?php

namespace Tests\Unit;

use App\Domain\Store\Actions\CreateStore;
use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\Store\Repositories\StoreRepository;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateStoreTest extends TestCase
{
    use RefreshDatabase;

    private CreateStore $createStore;
    private StoreRepository $storeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->storeRepository = $this->app->make(StoreRepository::class);
        $this->createStore = new CreateStore($this->storeRepository);

        // Create roles
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'seller_pending', 'guard_name' => 'sanctum']);
    }

    public function test_create_store_with_valid_data()
    {
         $owner = User::factory()->create();
        $category = StoreCategory::factory()->create();

        $storeData = new StoreData(
            name: 'Test Store',
            description: 'A test store',
            ownerUserId: $owner->id,
            address_line1: '123 Main St',
            city: 'Test City',
            latitude: '40.7128',
            longitude: '-74.0060',
            phone: '+1234567890',
            email: $owner->email,
            categories: [$category->id]
        );

        $result = $this->createStore->execute($owner, $storeData);

        $this->assertNotNull($result);
        $this->assertDatabaseHas('stores', [
            'name' => 'Test Store',
            'owner_user_id' => $owner->id,
            'status' => StoreStatus::PENDING,
        ]);
    }

    public function test_create_store_assigns_categories()
    {
        $owner = User::factory()->create();
        $category1 = StoreCategory::factory()->create();
        $category2 = StoreCategory::factory()->create();

        $storeData = new StoreData(
            name: 'Test Store',
            description: 'A test store',
            ownerUserId: $owner->id,
            address_line1: '123 Main St',
            city: 'Test City',
            phone: '+1234567890',
            email: $owner->email,
            categories: [$category1->id, $category2->id]
        );

        $result = $this->createStore->execute($owner, $storeData);

        $store = Store::where('name', 'Test Store')->first();
        $this->assertCount(2, $store->categories);
    }

    public function test_create_store_creates_address()
    {
        $owner = User::factory()->create();
        $category = StoreCategory::factory()->create();

        $storeData = new StoreData(
            name: 'Test Store',
            description: 'A test store',
            ownerUserId: $owner->id,
            address_line1: '123 Main St',
            address_line2: 'Suite 100',
            city: 'Test City',
            latitude: '40.7128',
            longitude: '-74.0060',
            phone: '+1234567890',
            email: $owner->email,
            categories: [$category->id]
        );

        $result = $this->createStore->execute($owner, $storeData);

        $store = Store::where('name', 'Test Store')->first();
        $this->assertCount(1, $store->addresses);
        $this->assertEquals('123 Main St', $store->addresses->first()->address_line1);
    }

    public function test_create_store_assigns_seller_pending_role()
    {
        $owner = User::factory()->create();
        $owner->assignRole('customer');
        $category = StoreCategory::factory()->create();

        $storeData = new StoreData(
            name: 'Test Store',
            description: 'A test store',
            ownerUserId: $owner->id,
            address_line1: '123 Main St',
            city: 'Test City',
            phone: '+1234567890',
            email: $owner->email,
            categories: [$category->id]
        );

        $this->createStore->execute($owner, $storeData);

        $owner->refresh();
        $this->assertTrue($owner->hasRole('seller_pending'));
        $this->assertTrue($owner->hasRole('customer'));
        $this->assertFalse($owner->hasRole('seller'));

        $sellerPendingRoleId = Role::where('name', 'seller_pending')->value('id');
        $sellerRoleId = Role::where('name', 'seller')->value('id');
        $store = Store::where('name', 'Test Store')->firstOrFail();

        $this->assertDatabaseHas('user_roles', [
            'user_id' => $owner->id,
            'role_id' => $sellerPendingRoleId,
            'store_id' => null,
        ]);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $owner->id,
            'role_id' => $sellerRoleId,
            'store_id' => $store->id,
        ]);
    }

    public function test_create_store_creates_default_hours()
    {
        $owner = User::factory()->create();
        $category = StoreCategory::factory()->create();

        $storeData = new StoreData(
            name: 'Test Store',
            description: 'A test store',
            ownerUserId: $owner->id,
            address_line1: '123 Main St',
            city: 'Test City',
            phone: '+1234567890',
            email: $owner->email,
            categories: [$category->id]
        );

        $result = $this->createStore->execute($owner, $storeData);

        $store = Store::where('name', 'Test Store')->first();
        $this->assertCount(7, $store->hours); // 7 days of the week
    }

    public function test_create_store_creates_verifications()
    {
        $owner = User::factory()->create();
        $category = StoreCategory::factory()->create();

        $storeData = new StoreData(
            name: 'Test Store',
            description: 'A test store',
            ownerUserId: $owner->id,
            address_line1: '123 Main St',
            city: 'Test City',
            phone: '+1234567890',
            email: $owner->email,
            commercial_register: 'path/to/commercial.pdf',
            tax_card: 'path/to/tax.pdf',
            id_card_front: 'path/to/id_front.pdf',
            id_card_back: 'path/to/id_back.pdf',
            categories: [$category->id]
        );

        $result = $this->createStore->execute($owner, $storeData);

        $store = Store::where('name', 'Test Store')->first();
        $this->assertCount(4, $store->verifications);
    }
}
