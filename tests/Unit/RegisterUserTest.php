<?php

namespace Tests\Unit;

use App\Domain\User\Actions\RegisterUser;
use App\Domain\User\DTOs\UserData;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    private RegisterUser $registerUser;
    private UserRepository $userRepository;
    private Hasher $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->app->make(UserRepository::class);
        $this->hasher = $this->app->make(Hasher::class);
        $this->registerUser = new RegisterUser($this->userRepository, $this->hasher);

        // Create roles
        Role::create(['name' => 'customer', 'guard_name' => 'sanctum']);
        Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
    }

    public function test_register_customer_creates_user_with_profile()
    {
        $userData = new UserData(
            firstName: 'John',
            lastName: 'Doe',
            email: 'john@example.com',
            phone_number: '+1234567890',
            password: 'password123',
            role: 'customer'
        );

        $context = [
            'ip_address' => '127.0.0.1',
        ];

        $user = $this->registerUser->execute($userData, $context);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals(UserStatus::ACTIVE, $user->status);
        $this->assertTrue($user->hasRole('customer'));

        $this->assertNotNull($user->profile);
        $this->assertEquals('John', $user->profile->first_name);
        $this->assertEquals('Doe', $user->profile->last_name);
    }

    public function test_register_admin_creates_verified_user()
    {
        // Create an admin user first to satisfy foreign key constraint
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $userData = new UserData(
            firstName: 'Admin',
            lastName: 'User',
            email: 'admin@example.com',
            phone_number: null,
            password: 'password123',
            role: 'admin'
        );

        $context = [
            'ip_address' => '127.0.0.1',
            'admin_id' => $adminUser->id,
        ];

        $user = $this->registerUser->execute($userData, $context);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertEquals(UserStatus::ACTIVE, $user->status);
    }

    public function test_register_hashes_password()
    {
        $userData = new UserData(
            firstName: 'Test',
            lastName: 'User',
            email: 'test@example.com',
            phone_number: null,
            password: 'plainpassword',
            role: 'customer'
        );

        $user = $this->registerUser->execute($userData, ['ip_address' => '127.0.0.1']);

        $this->assertNotEquals('plainpassword', $user->password_hash);
        $this->assertTrue($this->hasher->check('plainpassword', $user->password_hash));
    }

    public function test_register_assigns_correct_role()
    {
        $userData = new UserData(
            firstName: 'Test',
            lastName: 'User',
            email: 'test@example.com',
            phone_number: null,
            password: 'password123',
            role: 'customer'
        );

        $user = $this->registerUser->execute($userData, ['ip_address' => '127.0.0.1']);

        $this->assertTrue($user->hasRole('customer'));
        $this->assertFalse($user->hasRole('admin'));
    }

    public function test_register_stores_ip_address()
    {
        $userData = new UserData(
            firstName: 'Test',
            lastName: 'User',
            email: 'test@example.com',
            phone_number: null,
            password: 'password123',
            role: 'customer'
        );

        $context = ['ip_address' => '192.168.1.1'];

        $user = $this->registerUser->execute($userData, $context);

        $this->assertEquals('192.168.1.1', $user->last_ip);
    }
}
