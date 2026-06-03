<?php

namespace App\Domain\User\Actions\Admin;

use App\Domain\User\Models\User;

class UpdateUserStatusAction
{
    public function execute(User $user, string $status): User
    {
        $user->update([
            'status' => $status,
        ]);

        $user->load(['profile', 'roles']);

        return $user;
    }
}
