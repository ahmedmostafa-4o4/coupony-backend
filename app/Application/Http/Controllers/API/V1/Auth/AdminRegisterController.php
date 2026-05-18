<?php

namespace App\Application\Http\Controllers\API\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\registerUserRequest;
use App\Domain\User\Actions\RegisterUser;
use App\Domain\User\DTOs\UserData;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AdminRegisterController extends Controller implements HasMiddleware
{
    public function __construct(
        private RegisterUser $registerUser,
    ) {}

    public static function middleware(): array
    {
        return [
            // examples with aliases, pipe-separated names, guards, etc:
            new Middleware(\Spatie\Permission\Middleware\RoleMiddleware::using('admin')),
            new Middleware('auth:sanctum'),
            new Middleware('throttle:5,1'),
        ];
    }

    public function __invoke(registerUserRequest $request)
    {
        $this->applyAuthenticatedLocale($request);

        $context = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $request->input('device_name'),
            'admin_id' => $request->user()->id,
        ];
        $user = $this->registerUser->execute(
            UserData::fromRequest($request),
            context: $context
        );

        return response()->json([
            'success' => true,
            'message' => __('api.auth.admin_registered'),
        ], 200);
    }
}
