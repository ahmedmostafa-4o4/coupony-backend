<?php

namespace App\Domain\Admin\Services;

use App\Domain\User\Actions\RegisterUser;
use App\Domain\User\DTOs\UserData;

class AdminService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private RegisterUser $registerUser
    ) {
        //
    }

    public function register(UserData $data, array $context)
    {
        $admin = $this->registerUser->execute($data, $context);

        return $admin;
    }
}
