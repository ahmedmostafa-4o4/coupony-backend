<?php

namespace App\Domain\User\Actions;

use App\Domain\User\DTOs\UserData;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use App\Domain\User\Repositories\UserRepository;
use App\Domain\User\Events\UserRegistered;
use DB;
use Illuminate\Contracts\Hashing\Hasher;

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

    public function excute(UserData $data, array $context): User
    {
        return DB::transaction(function () use ($data, $context) {

            $user = $this->users->create([
                'email' => $data->email,
                'password_hash' => $this->hasher->make($data->password),
                'provider' => $data->provider,
                'provider_id' => $data->providerId,
                'last_ip' => $context['ip_address'] ?? null,
                'status' => UserStatus::ACTIVE
            ]);
            if ($data->firstName || $data->lastName) {
                $user->profile()->create([
                    'first_name' => $data->firstName,
                    'last_name' => $data->lastName,
                    // 'avatar_url' => $data->avatarUrl,
                ]);
            }
            if ($data->role === 'seller')
                $user->assignRole('seller');
            $user->assignRole('customer');

            UserRoles::create([
                'user_id' => $user->id,
                'role_id' => $user->roles()->where('name', $data->role)->first()->id,
            ]);

            // $user->points->create(["points" => 0]);

            event(new UserRegistered($user, $context));
            return $user;
        });
    }
}
