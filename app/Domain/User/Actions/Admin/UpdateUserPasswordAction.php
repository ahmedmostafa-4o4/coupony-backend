<?php

namespace App\Domain\User\Actions\Admin;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUserPasswordAction
{
    public function execute(User $user, string $password): void
    {
        $user->update([
            'password_hash' => Hash::make($password),
        ]);

        // Revoke all sessions to force the user to re-login with their new password
        $user->tokens()->delete();
        $user->sessions()->delete();
    }
}
