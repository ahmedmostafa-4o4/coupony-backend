<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\DTOs\Admin\UserFilterDTO;
use App\Domain\User\Enums\UserStatus;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserRepository
{
    public function paginateUsers(UserFilterDTO $filters): LengthAwarePaginator
    {
        $query = User::query()->with(['profile', 'roles']);

        if ($filters->status) {
            $query->where('status', $filters->status);
        }

        if ($filters->role) {
            $query->role($filters->role, 'sanctum');
        }

        if ($filters->fromDate) {
            $query->whereDate('created_at', '>=', $filters->fromDate);
        }

        if ($filters->toDate) {
            $query->whereDate('created_at', '<=', $filters->toDate);
        }

        if ($filters->search) {
            $search = $filters->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($profileQuery) use ($search) {
                      $profileQuery->where('first_name', 'like', "%{$search}%")
                                   ->orWhere('last_name', 'like', "%{$search}%");
                                   
                      if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
                          $profileQuery->orWhereRaw("first_name || ' ' || last_name LIKE ?", ["%{$search}%"]);
                      } else {
                          $profileQuery->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                      }
                  });
            });
        }

        return $query->latest()->paginate($filters->perPage);
    }

    public function getStatistics(): array
    {
        return [
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
    }
}
