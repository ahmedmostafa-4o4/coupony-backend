<?php

namespace App\Application\Http\Controllers\API\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ChangePasswordRequest;
use App\Application\Http\Requests\loginUserRequest;
use App\Application\Http\Resources\UserResource;
use App\Domain\Store\Models\Store;
use App\Domain\User\Services\AuthenticationService;
use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LoginController extends Controller
{
    public function __construct(
        private AuthenticationService $authService
    ) {
        //
    }
    public function login(loginUserRequest $request)
    {
        $context = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $request->input('device_name'),
            'role' => $request->input('role'),
        ];

        try {
            $result = $this->authService->login(
                email: $request->input('email'),
                password: $request->input('password'),
                context: $context
            );

            $role = $request->input('role');

            if ($role === 'seller') {
                return $this->localizedJson([
                    'message' => __('api.auth.login_successful'),
                    'data' => [
                        'user' => new UserResource($result['user']->load('stores')),
                        'session' => $result['session'],
                        'role' => $role,
                        'access_token' => $result['access_token'],
                        'refresh_token' => $result['refresh_token'],
                        'token_type' => $result['token_type'],
                        'expires_in' => $result['expires_in'],
                    ],
                ], 200);
            }

            return $this->localizedJson([
                'message' => __('api.auth.login_successful'),
                'data' => [
                    'user' => new UserResource($result['user']),
                    'session' => $result['session'],
                    'role' => $role,
                    'access_token' => $result['access_token'],
                    'refresh_token' => $result['refresh_token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->localizedJson([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 401);
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'email' => $request->input('email'),
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.auth.login_failed'),
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();
        $currentToken = $request->bearerToken();

        $this->authService->logout($user, $currentToken);

        return $this->localizedJson([
            'message' => __('api.auth.logout_successful'),
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        $user = new UserResource($user->load(['profile', 'roles', 'sessions', 'points']));

        return $this->localizedJson([
            'data' => $user
        ], 200);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();
        $validated = $request->validated();

        if (!Hash::check($validated['current_password'], $user->password_hash)) {
            return $this->localizedJson([
                'message' => __('api.auth.invalid_credentials'),
                'errors' => [
                    'current_password' => [__('api.auth.invalid_credentials')],
                ],
            ], 401);
        }

        try {
            DB::transaction(function () use ($user, $validated) {
                $user->forceFill([
                    'password_hash' => Hash::make($validated['password']),
                ])->save();

                $user->tokens()->delete();
            });

            return $this->localizedJson([
                'message' => __('api.auth.password_changed'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to change password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.auth.login_failed'),
            ], 500);
        }
    }

    public function updateMe(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        $validated = $request->validate([
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'language' => ['sometimes', 'nullable', 'string', 'max:10'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],
            'bio' => ['sometimes', 'nullable', 'string'],
            'avatar' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ]);

        try {
            Log::info('updateMe', [
                'user_id' => $user->id,
                'validated_data' => $validated,
            ]);
            DB::transaction(function () use ($validated, $user, $request) {
                $userFields = collect($validated)->only([
                    'phone_number',
                    'language',
                    'timezone',
                ])->filter(function ($value, $key) use ($validated) {
                    return array_key_exists($key, $validated);
                })->all();

                if ($userFields !== []) {
                    $user->fill($userFields);
                    $user->save();
                }

                $profileFields = collect($validated)->only([
                    'first_name',
                    'last_name',
                    'date_of_birth',
                    'gender',
                    'bio',
                ])->filter(function ($value, $key) use ($validated) {
                    return array_key_exists($key, $validated);
                })->all();

                if (!empty($validated['remove_avatar'])) {
                    $this->deleteStoredAvatarIfExists($user->profile?->avatar_url);
                    $profileFields['avatar_url'] = $this->defaultAvatarUrl();
                } elseif ($request->hasFile('avatar')) {
                    $profileFields['avatar_url'] = $this->replaceAvatar($request, $user->id, $user->profile?->avatar_url);
                }

                if ($profileFields !== []) {
                    $user->profile()->updateOrCreate(
                        ['user_id' => $user->id],
                        $profileFields
                    );
                }
            });

            $user->load(['profile', 'roles', 'points', 'stores']);

            return $this->localizedJson([
                'data' => new UserResource($user),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update authenticated user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.auth.login_failed'),
            ], 500);
        }
    }

    public function destroyMe(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $user = $request->user();

        if ($user->hasAnyRole(['admin'])) {
            return $this->localizedJson([
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        if (blank($user->provider)) {
            $validated = $request->validate([
                'current_password' => ['required', 'string', 'min:8'],
            ]);

            if (!Hash::check($validated['current_password'], $user->password_hash)) {
                return $this->localizedJson([
                    'message' => __('api.auth.invalid_credentials'),
                    'errors' => [
                        'current_password' => [__('api.auth.invalid_credentials')],
                    ],
                ], 401);
            }
        }

        try {
            DB::transaction(function () use ($user) {
                $avatarPath = $this->resolvePublicStoragePath($user->profile?->avatar_url);
                $filePathsToDelete = $this->deleteOwnedStores($user);
                $filePathsToDelete = array_merge($filePathsToDelete, $this->detachOwnerAddresses(User::class, [$user->id]));

                $this->authService->logoutAll($user);
                Cache::forget("user.by_email.{$user->email}");
                Cache::forget("user.by_id.{$user->id}");
                $user->delete();

                if ($avatarPath) {
                    $filePathsToDelete[] = $avatarPath;
                }

                DB::afterCommit(function () use ($filePathsToDelete) {
                    $this->deleteFiles($filePathsToDelete);
                });
            });

            return $this->localizedJson([
                'message' => __('api.admin.users.deleted'),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete authenticated customer account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.delete_failed'),
            ], 500);
        }
    }

    private function deleteOwnedStores(User $user): array
    {
        $stores = $user->stores()
            ->with(['verifications:id,store_id,document_path', 'products.images:id,product_id,image_url'])
            ->get();

        if ($stores->isEmpty()) {
            return [];
        }

        $storeIds = $stores->pluck('id')->all();
        $filePaths = [];

        foreach ($stores as $store) {
            $filePaths = array_merge($filePaths, array_filter([
                $store->logo_url,
                $store->banner_url,
            ]));

            $filePaths = array_merge(
                $filePaths,
                $store->verifications->pluck('document_path')->filter()->values()->all(),
                $store->products->flatMap(fn($product) => $product->images->pluck('image_url'))->filter()->values()->all()
            );
        }

        $filePaths = array_merge($filePaths, $this->detachOwnerAddresses(Store::class, $storeIds));

        foreach ($stores as $store) {
            $store->delete();
        }

        return $filePaths;
    }

    private function detachOwnerAddresses(string $ownerType, array $ownerIds): array
    {
        if ($ownerIds === []) {
            return [];
        }

        $addressIds = DB::table('addressables')
            ->where('owner_type', $ownerType)
            ->whereIn('owner_id', $ownerIds)
            ->pluck('address_id')
            ->unique()
            ->values()
            ->all();

        DB::table('addressables')
            ->where('owner_type', $ownerType)
            ->whereIn('owner_id', $ownerIds)
            ->delete();

        if ($addressIds === []) {
            return [];
        }

        $orphanedAddressIds = DB::table('addresses')
            ->whereIn('id', $addressIds)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('addressables')
                    ->whereColumn('addressables.address_id', 'addresses.id');
            })
            ->pluck('id')
            ->all();

        if ($orphanedAddressIds !== []) {
            DB::table('addresses')->whereIn('id', $orphanedAddressIds)->delete();
        }

        return [];
    }

    private function resolvePublicStoragePath(?string $pathOrUrl): ?string
    {
        if (!$pathOrUrl) {
            return null;
        }

        $storagePrefix = rtrim(Storage::disk('public')->url(''), '/');

        if (str_starts_with($pathOrUrl, $storagePrefix . '/')) {
            return ltrim(substr($pathOrUrl, strlen($storagePrefix)), '/');
        }

        return $pathOrUrl;
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }


    private function replaceAvatar(Request $request, string $userId, ?string $currentAvatarUrl): string
    {
        $this->deleteStoredAvatarIfExists($currentAvatarUrl);

        $path = $request->file('avatar')->store("users/{$userId}/avatar", 'public');

        return Storage::disk('public')->url($path);
    }

    private function deleteStoredAvatarIfExists(?string $avatarUrl): void
    {
        if (!$avatarUrl) {
            return;
        }

        $storagePrefix = rtrim(Storage::disk('public')->url(''), '/');

        if (!str_starts_with($avatarUrl, $storagePrefix . '/')) {
            return;
        }

        $relativePath = ltrim(substr($avatarUrl, strlen($storagePrefix)), '/');

        if ($relativePath !== '' && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function defaultAvatarUrl(): string
    {
        return config('app.url') . '/users/avatars/default.svg';
    }
}
