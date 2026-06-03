<?php

namespace App\Domain\User\Actions\Admin;

use App\Domain\User\Models\User;

class RevokeUserSessionAction
{
    public function execute(User $user, string $sessionId): void
    {
        $session = $user->sessions()->where('id', $sessionId)->first();
        
        if ($session) {
            $session->delete();
        } else {
            $token = $user->tokens()->where('id', $sessionId)->first();
            if ($token) {
                $token->delete();
            }
        }
    }
}
