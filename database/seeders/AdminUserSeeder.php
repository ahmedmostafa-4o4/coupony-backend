<?php

namespace Database\Seeders;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->syncAdminUser();
        $admin->assignRole('admin');

        $this->syncProfile($admin);
        $this->syncPreferences($admin);

        $this->command?->info("Admin account synced: {$admin->email}");
    }

    private function syncAdminUser(): User
    {
        $admin = User::query()->firstOrNew(['email' => $this->adminEmail()]);
        $password = env('ADMIN_PASSWORD');

        $this->ensurePasswordAvailable($admin, $password);

        $admin->fill($this->adminAttributes($admin));

        if (filled($password)) {
            $admin->password_hash = Hash::make($password);
        }

        $admin->save();

        return $admin;
    }

    private function syncProfile(User $admin): void
    {
        Profile::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'first_name' => env('ADMIN_FIRST_NAME', 'Admin'),
                'last_name' => env('ADMIN_LAST_NAME', 'User'),
                'gender' => 'male',
            ]
        );
    }

    private function syncPreferences(User $admin): void
    {
        UserPreference::query()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'preferred_language' => 'en',
                'preferred_currency' => 'EGP',
                'email_marketing' => false,
                'email_order_updates' => true,
                'sms_notifications' => false,
                'push_notifications' => true,
            ]
        );
    }

    private function ensurePasswordAvailable(User $admin, ?string $password): void
    {
        if (! $admin->exists && blank($password)) {
            throw new RuntimeException('ADMIN_PASSWORD is required when creating the production admin account.');
        }
    }

    private function adminAttributes(User $admin): array
    {
        return [
            'phone_number' => env('ADMIN_PHONE') ?: $admin->phone_number,
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'phone_verified_at' => env('ADMIN_PHONE') ? ($admin->phone_verified_at ?? now()) : $admin->phone_verified_at,
            'status' => 'active',
        ];
    }

    private function adminEmail(): string
    {
        return env('ADMIN_EMAIL', 'admin@coupony.com');
    }
}
