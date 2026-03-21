<?php

namespace App\Domain\Admin\Services;

use App\Application\Http\Resources\UserResource;
use App\Domain\User\Actions\RegisterUser;
use App\Domain\User\DTOs\UserData;
use App\Domain\User\Enums\OtpChannels;
use App\Domain\User\Enums\OtpPurposes;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Events\UserLoggedIn;
use App\Domain\User\Events\UserLoggedOut;
use App\Domain\User\Models\User;
use App\Domain\User\Services\AuthenticationService;
use Auth;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

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
