<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\UserResource;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(array_column(UserStatus::cases(), 'value'))],
            'role' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $query = User::query()
                ->with(['profile', 'roles', 'stores']);

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['role'])) {
                $query->role($validated['role'], 'sanctum');
            }

            if (!empty($validated['search'])) {
                $search = $validated['search'];

                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('email', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhereHas('profile', function ($profileQuery) use ($search) {
                            $profileQuery->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%');
                        });
                });
            }

            if (!empty($validated['from_date'])) {
                $query->whereDate('created_at', '>=', $validated['from_date']);
            }

            if (!empty($validated['to_date'])) {
                $query->whereDate('created_at', '<=', $validated['to_date']);
            }

            $users = $query->latest()->paginate($validated['per_page'] ?? 15);

            return $this->localizedJson([
                'message' => __('api.admin.users.retrieved'),
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve users', ['error' => $e->getMessage()]);

            return $this->localizedJson([
                'message' => __('api.admin.users.retrieve_failed'),
            ], 500);
        }
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $user->load(['profile', 'roles', 'points', 'stores']);

            return $this->localizedJson([
                'message' => __('api.admin.users.details_retrieved'),
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user details', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.details_failed'),
            ], 500);
        }
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'language' => ['sometimes', 'nullable', 'string', 'max:10'],
            'timezone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', Rule::in(array_column(UserStatus::cases(), 'value'))],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female'])],
            'bio' => ['sometimes', 'nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($validated, $user) {
                $userFields = collect($validated)->only([
                    'email',
                    'phone_number',
                    'language',
                    'timezone',
                    'status',
                ])->filter(function ($value, $key) use ($validated) {
                    return array_key_exists($key, $validated);
                })->all();

                if ($userFields !== []) {
                    $user->fill($userFields);
                    $user->save();
                }

                if (array_key_exists('role', $validated)) {
                    $user->syncRoles([$validated['role']]);
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

                if ($profileFields !== []) {
                    $user->profile()->updateOrCreate(
                        ['user_id' => $user->id],
                        $profileFields
                    );
                }
            });

            $user->load(['profile', 'roles', 'points', 'stores']);

            return $this->localizedJson([
                'message' => __('api.admin.users.updated'),
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.update_failed'),
            ], 500);
        }
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_column(UserStatus::cases(), 'value'))],
        ]);

        try {
            $user->update([
                'status' => $validated['status'],
            ]);

            $user->load(['profile', 'roles']);

            return $this->localizedJson([
                'message' => __('api.admin.users.status_updated'),
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update user status', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.status_update_failed'),
            ], 500);
        }
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        if ($request->user()->id === $user->id) {
            return $this->localizedJson([
                'message' => __('api.admin.users.cannot_delete_self'),
            ], 400);
        }

        try {
            DB::transaction(function () use ($user) {
                $user->tokens()->delete();
                $user->sessions()->delete();
                $user->delete();
            });

            return $this->localizedJson([
                'message' => __('api.admin.users.deleted'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.users.delete_failed'),
            ], 500);
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $stats = [
                'total' => User::count(),
                'active' => User::where('status', UserStatus::ACTIVE->value)->count(),
                'suspended' => User::where('status', UserStatus::SUSPENDED->value)->count(),
                'deleted' => User::where('status', UserStatus::DELETED->value)->count(),
                'admins' => User::role('admin', 'sanctum')->count(),
                'customers' => User::role('customer', 'sanctum')->count(),
                'sellers' => User::role('seller', 'sanctum')->count(),
                'pending_sellers' => User::role('seller_pending', 'sanctum')->count(),
                'recent' => User::where('created_at', '>=', now()->subDays(7))->count(),
            ];

            return $this->localizedJson([
                'message' => __('api.admin.users.statistics_retrieved'),
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user statistics', ['error' => $e->getMessage()]);

            return $this->localizedJson([
                'message' => __('api.admin.users.statistics_failed'),
            ], 500);
        }
    }
}
