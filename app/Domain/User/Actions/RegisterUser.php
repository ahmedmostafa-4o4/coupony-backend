<?php

namespace App\Domain\User\Actions;

use App\Domain\User\DTOs\UserData;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Events\UserRegistered;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use App\Domain\User\Repositories\UserRepository;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RegisterUser
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private UserRepository $users,
        private Hasher $hasher,
    ) {
    }

    public function execute(UserData $data, array $context): User
    {
        return DB::transaction(function () use ($data, $context) {
            $primaryRole = isset($context['admin_id'])
                ? 'admin'
                : ($data->role === 'seller' ? 'seller_pending' : 'customer');

            $user = $this->users->create([
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'password_hash' => $this->hasher->make($data->password),
                'provider' => $data->provider,
                'provider_id' => $data->providerId,
                'language' => $data->language,
                'last_ip' => $context['ip_address'] ?? null,
                'status' => UserStatus::ACTIVE,
            ]);

            if ($data->firstName || $data->lastName) {
                $user->profile()->create([
                    'first_name' => $data->firstName,
                    'last_name' => $data->lastName,
                ]);
            }

            $rolesToAssign = $primaryRole === 'seller_pending'
                ? ['customer', 'seller_pending']
                : [$primaryRole];

            foreach ($rolesToAssign as $roleName) {
                $this->assignGlobalRole(
                    user: $user,
                    roleName: $roleName,
                    grantedByUserId: $context['admin_id'] ?? null
                );
            }

            if ($primaryRole === 'admin') {
                $user->forceFill([
                    'status' => UserStatus::ACTIVE,
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                ])->save();
            }

            event(new UserRegistered($user, $context));

            return $user;
        });
    }

    private function assignGlobalRole(User $user, string $roleName, ?string $grantedByUserId): void
    {
        $roleId = Role::where('name', $roleName)->value('id');

        if ($roleId === null) {
            throw new \RuntimeException("Role [{$roleName}] is not configured.");
        }

        if (!$user->hasRole($roleName)) {
            $user->assignRole($roleName);
        }

        UserRoles::firstOrCreate([
            'user_id' => $user->id,
            'role_id' => $roleId,
            'store_id' => null,
        ], [
            'granted_by_user_id' => $grantedByUserId,
            'granted_at' => now(),
        ]);
    }
}
