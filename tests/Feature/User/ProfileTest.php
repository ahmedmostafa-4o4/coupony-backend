<?php

namespace Tests\Feature\User;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_created_without_gender_stores_null(): void
    {
        $profile = $this->createProfile();

        $this->assertNull($profile->gender);
        $this->assertDatabaseHas('profiles', [
            'id' => $profile->id,
            'gender' => null,
        ]);
    }

    public function test_profile_created_with_unsupported_gender_stores_null(): void
    {
        $profile = $this->createProfile(['gender' => 'other']);

        $this->assertNull($profile->gender);
    }

    public function test_profile_created_with_valid_gender_normalizes_it_to_lowercase(): void
    {
        $profile = $this->createProfile(['gender' => 'FEMALE']);

        $this->assertSame('female', $profile->gender);
    }

    private function createProfile(array $attributes = []): Profile
    {
        $user = User::factory()->create();
        $user->profile()->delete();

        return $user->profile()->create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
        ], $attributes));
    }
}
