<?php

namespace Tests\Feature\Seeders;

use App\Domain\User\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminUserSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        putenv('ADMIN_PHONE');
        putenv('ADMIN_FIRST_NAME');
        putenv('ADMIN_LAST_NAME');

        parent::tearDown();
    }

    public function test_it_creates_an_idempotent_admin_account_from_environment_values(): void
    {
        putenv('ADMIN_EMAIL=owner@example.com');
        putenv('ADMIN_PASSWORD=secret-password');
        putenv('ADMIN_PHONE=+201000000001');
        putenv('ADMIN_FIRST_NAME=Owner');
        putenv('ADMIN_LAST_NAME=Admin');

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'owner@example.com')->count());

        $admin = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('secret-password', $admin->password_hash));
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertDatabaseHas('profiles', [
            'user_id' => $admin->id,
            'first_name' => 'Owner',
            'last_name' => 'Admin',
        ]);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $admin->id,
            'preferred_language' => 'en',
            'preferred_currency' => 'EGP',
        ]);
    }

    public function test_it_requires_a_password_when_creating_a_new_admin(): void
    {
        putenv('ADMIN_EMAIL=owner@example.com');
        putenv('ADMIN_PASSWORD');

        $this->seed(RoleAndPermissionSeeder::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_PASSWORD is required');

        $this->seed(AdminUserSeeder::class);
    }
}
