<?php

namespace App\Domain\User\Actions\Admin;

use App\Domain\User\Models\User;

class RevokeAllUserSessionsAction
{
    public function execute(User $user): void
    {
        $user->tokens()->delete();
        $user->sessions()->delete();
    }
}
