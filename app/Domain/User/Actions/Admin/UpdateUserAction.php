<?php

namespace App\Domain\User\Actions\Admin;

use App\Domain\User\DTOs\Admin\UpdateUserDTO;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUserAction
{
    public function execute(User $user, UpdateUserDTO $dto): User
    {
        DB::transaction(function () use ($user, $dto) {
            $userFields = collect($dto->all())->only([
                'email',
                'phone_number',
                'language',
                'timezone',
                'status',
            ])->all();

            if ($userFields !== []) {
                $user->fill($userFields);
                $user->save();
            }

            if ($dto->has('role')) {
                $user->syncRoles([$dto->get('role')]);
            }

            $profileFields = collect($dto->all())->only([
                'first_name',
                'last_name',
                'date_of_birth',
                'gender',
                'bio',
            ])->all();

            if ($profileFields !== []) {
                $user->profile()->updateOrCreate(
                    ['user_id' => $user->id],
                    $profileFields
                );
            }
        });

        $user->load(['profile', 'roles', 'points', 'stores']);

        return $user;
    }
}
