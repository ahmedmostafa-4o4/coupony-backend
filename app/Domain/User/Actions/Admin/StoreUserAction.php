<?php

namespace App\Domain\User\Actions\Admin;

use App\Domain\User\DTOs\Admin\StoreUserDTO;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StoreUserAction
{
    public function execute(StoreUserDTO $dto): User
    {
        $user = null;

        DB::transaction(function () use ($dto, &$user) {
            $user = User::create([
                'email' => $dto->email,
                'phone_number' => $dto->phoneNumber,
                'password_hash' => Hash::make($dto->password),
                'language' => $dto->language ?? config('app.fallback_locale'),
                'timezone' => $dto->timezone ?? 'Africa/Cairo',
                'status' => $dto->status ?? UserStatus::ACTIVE->value,
                'email_verified_at' => now(),
                'phone_verified_at' => $dto->phoneNumber ? now() : null,
            ]);

            $user->assignRole($dto->role);

            $user->profile()->create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'date_of_birth' => $dto->dateOfBirth,
                'gender' => $dto->gender,
                'bio' => $dto->bio,
            ]);
        });

        $user->load(['profile', 'roles']);

        return $user;
    }
}
