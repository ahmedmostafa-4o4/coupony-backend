<?php

namespace Tests\Feature;

use App\Domain\User\Models\Address;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_addresses(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'address_line1' => '12 Nile Street',
            'city' => 'Cairo',
        ]);

        $user->addresses()->attach($address->id, [
            'label' => 'home',
            'is_default_shipping' => true,
            'is_default_billing' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/addresses');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.address_line1', '12 Nile Street')
            ->assertJsonPath('data.0.label', 'home');
    }

    public function test_user_can_create_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/addresses', [
                'first_name' => 'Ahmed',
                'last_name' => 'Mostafa',
                'address_line1' => '12 Nile Street',
                'city' => 'Cairo',
                'state_province' => 'Cairo',
                'postal_code' => '11511',
                'country_code' => 'EG',
                'phone_number' => '+201000000000',
                'label' => 'home',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.address_line1', '12 Nile Street')
            ->assertJsonPath('data.label', 'home')
            ->assertJsonPath('data.is_default_shipping', true)
            ->assertJsonPath('data.is_default_billing', true);

        $this->assertDatabaseHas('addresses', [
            'address_line1' => '12 Nile Street',
            'city' => 'Cairo',
        ]);

        $this->assertDatabaseHas('addressables', [
            'owner_id' => $user->id,
            'owner_type' => User::class,
            'label' => 'home',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);
    }

    public function test_user_can_search_own_addresses(): void
    {
        $user = User::factory()->create();

        $matchingAddress = Address::factory()->create([
            'address_line1' => '12 Nile Street',
            'city' => 'Cairo',
        ]);

        $nonMatchingAddress = Address::factory()->create([
            'address_line1' => '45 Desert Road',
            'city' => 'Giza',
        ]);

        $user->addresses()->attach($matchingAddress->id, [
            'label' => 'home',
            'is_default_shipping' => true,
            'is_default_billing' => false,
        ]);

        $user->addresses()->attach($nonMatchingAddress->id, [
            'label' => 'office',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/addresses?search=Nile');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingAddress->id)
            ->assertJsonPath('data.0.address_line1', '12 Nile Street');
    }

    public function test_user_can_update_own_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'address_line1' => 'Old Street',
            'city' => 'Giza',
        ]);

        $user->addresses()->attach($address->id, [
            'label' => 'home',
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/me/addresses/{$address->id}", [
                'address_line1' => 'New Street',
                'city' => 'Cairo',
                'label' => 'work',
                'is_default_shipping' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.address_line1', 'New Street')
            ->assertJsonPath('data.city', 'Cairo')
            ->assertJsonPath('data.label', 'work')
            ->assertJsonPath('data.is_default_shipping', true);

        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'address_line1' => 'New Street',
            'city' => 'Cairo',
        ]);

        $this->assertDatabaseHas('addressables', [
            'address_id' => $address->id,
            'owner_id' => $user->id,
            'label' => 'work',
            'is_default_shipping' => true,
        ]);
    }

    public function test_user_can_delete_own_address(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create();

        $user->addresses()->attach($address->id, [
            'label' => 'home',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/addresses/{$address->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Address deleted successfully.');

        $this->assertDatabaseMissing('addressables', [
            'address_id' => $address->id,
            'owner_id' => $user->id,
            'owner_type' => User::class,
        ]);

        $this->assertDatabaseMissing('addresses', [
            'id' => $address->id,
        ]);
    }

    public function test_user_cannot_update_another_users_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();

        $otherUser->addresses()->attach($address->id, [
            'label' => 'home',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson("/api/v1/me/addresses/{$address->id}", [
                'city' => 'Cairo',
            ]);

        $response->assertStatus(404);
    }
}
