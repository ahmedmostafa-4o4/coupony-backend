<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Models\User;
use Cache;
use DB;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(string $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = User::findOrFail($id);
            $user->update($data);
            Cache::forget("user.by_id.{$id}");

            return $user->fresh();
        });

    }

    public function delete(string $id): bool
    {
        return DB::transaction(function () use ($id) {
            $user = User::findOrFail($id);
            $deleted = $user->delete();
            Cache::forget("user.by_id.{$id}");

            return $deleted;
        });
    }

    public function find(string $id): ?User
    {
        return DB::transaction(function () use ($id) {
            $cacheKey = "user.by_id.{$id}";

            $user = Cache::get($cacheKey);
            if ($user !== null) {
                return $user;
            }

            $user = User::findOrFail($id);

            if ($user) {
                Cache::put($cacheKey, $user, now()->addHour());
            }

            return $user;
        });

    }

    public function findByEmail(string $email): ?User
    {
        return DB::transaction(function () use ($email) {
            $cacheKey = "user.by_email.{$email}";

            $user = Cache::get($cacheKey);

            if ($user !== null) {
                return $user;
            }

            $user = User::where('email', $email)->first();

            if ($user) {
                Cache::put($cacheKey, $user, now()->addHour());
            }

            return $user;
        });
    }

    public function all(): array
    {
        $users = User::all();

        return $users->toArray();
    }
}
