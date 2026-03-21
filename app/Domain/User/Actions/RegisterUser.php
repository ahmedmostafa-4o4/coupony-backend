<?php

namespace App\Domain\User\Actions;

use App\Domain\User\DTOs\UserData;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Events\UserRegistered;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RegisterUser
{
    /**
     * Create a new class instance.
     */
    public function __construct(private
        UserRepository $users, private
        Hasher $hasher,
        )
    {

    }

    public function execute(UserData $data, array $context): User
    {
        return DB::transaction(function () use ($data, $context) {

            $user = $this->users->create([
                'email' => $data->email,
                'password_hash' => $this->hasher->make($data->password),
                'provider' => $data->provider,
                'provider_id' => $data->providerId,
                'last_ip' => $context['ip_address'] ?? null,
                'status' => UserStatus::ACTIVE,
            ]);

            if ($data->firstName || $data->lastName) {
                $user->profile()->create([
                    'first_name' => $data->firstName,
                    'last_name' => $data->lastName,
                ]);
            }

            // ✅ القرار من السيستم مش من الـ input
            $roleName = isset($context['admin_id']) ? 'admin' : 'customer';
            $roleId = Role::where('name', $roleName)->value('id');

            $user->assignRole($roleName);

            UserRoles::create([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'granted_by_user_id' => $context['admin_id'] ?? null,
            ]);

            if ($roleName === 'admin') {
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

}
