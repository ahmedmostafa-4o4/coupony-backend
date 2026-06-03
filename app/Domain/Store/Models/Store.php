<?php

namespace App\Domain\Store\Models;

use App\Domain\Points\Models\StorePoints;
use App\Domain\Points\Models\StorePointTransaction;
use App\Domain\Banner\Models\Banner;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Store\Enums\StorePermission;
use App\Domain\User\Models\Address;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory, Notifiable, LogsActivity;

    protected static function newFactory()
    {
        return \Database\Factories\StoreFactory::new();
    }

    protected $table = 'stores';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'owner_user_id',
        'name',
        'description',
        'logo_url',
        'banner_url',
        'email',
        'phone',
        'tax_id',
        'commission_rate',
        'status',
        'subscription_tier',
        'is_verified',
        'verified_at',
        'total_sales',
        'rating_avg',
        'rating_count',
        'followers_count',
        'monthly_goal',
        'shard_key',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'admin_notes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'description',
                'status',
                'commission_rate',
                'subscription_tier',
                'is_verified',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function casts(): array
    {
        return [
            'status' => \App\Domain\Store\Enums\StoreStatus::class,
            'commission_rate' => 'decimal:4',
            'total_sales' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'followers_count' => 'integer',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            if (empty($store->id)) {
                $store->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function socials()
    {
        return $this->hasMany(StoreSocial::class, 'store_id');
    }

    public function verifications()
    {
        return $this->hasMany(StoreVerification::class, 'store_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('role_id', 'granted_at', 'granted_by_user_id', 'expires_at')
            ->withTimestamps();
    }

    public function staff(): BelongsToMany
    {
        return $this->employees();
    }

    public function managers(): BelongsToMany
    {
        return $this->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'store_manager');
            });
    }

    public function employeeLinks()
    {
        return $this->hasMany(StoreEmployee::class, 'store_id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_employees', 'store_id', 'user_id')
            ->withTimestamps();
    }

    public function hours()
    {
        return $this->hasMany(StoreHours::class, 'store_id');
    }

    public function followers()
    {
        return $this->hasMany(StoreFollowers::class, 'store_id');
    }

    public function followerUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_followers', 'store_id', 'user_id')
            ->withPivot('notification_enabled', 'followed_at');
    }

    public function invitations()
    {
        return $this->hasMany(StoreInvitation::class, 'store_id');
    }

    public function comments()
    {
        return $this->hasMany(StoreComment::class, 'store_id');
    }

    public function hasReachedEmployeeLimit(): bool
    {
        $maxEmployees = config('store.max_employees', 10);

        $currentEmployees = $this->employees()->count();
        $pendingInvitations = $this->invitations()->pending()->where('expires_at', '>', now())->count();

        return ($currentEmployees + $pendingInvitations) >= $maxEmployees;
    }

    public function userRoles()
    {
        return $this->hasMany(UserRoles::class, 'store_id');
    }

    public function addresses()
    {
        return $this->morphToMany(Address::class, 'owner', 'addressables')
            ->withPivot([
                'label',
                'is_default_shipping',
                'is_default_billing',
            ])
            ->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(
            StoreCategory::class,
            'store_store_category'
        );
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    public function banners()
    {
        return $this->hasMany(Banner::class, 'store_id');
    }

    public function offerClaims()
    {
        return $this->hasMany(OfferClaim::class, 'store_id');
    }

    public function points()
    {
        return $this->hasOne(StorePoints::class, 'store_id');
    }

    public function pointTransactions()
    {
        return $this->hasMany(StorePointTransaction::class, 'store_id');
    }

    public function claims()
    {
        return $this->offerClaims();
    }

    public function hasEmployee(User $user): bool
    {
        return $this->employees()->whereKey($user->id)->exists();
    }

    public function employeeLinkFor(User $user): ?StoreEmployee
    {
        return $this->employeeLinks()
            ->where('user_id', $user->id)
            ->first();
    }

    public function employeeHasPermission(User $user, string $permission): bool
    {
        return $this->employeeHasAnyPermission($user, [$permission]);
    }

    public function employeeHasAnyPermission(User $user, array $permissions): bool
    {
        $employee = $this->employeeLinkFor($user);

        if (! $employee) {
            return false;
        }

        // Explicit per-store permissions override role defaults. Null keeps
        // legacy/invited employees working by falling back to their role.
        $effectivePermissions = $employee->permissions
            ?? $this->defaultPermissionsForStoreRole($employee->role);

        return collect($effectivePermissions)
            ->intersect($permissions)
            ->isNotEmpty();
    }

    public function defaultPermissionsForStoreRole(?string $role): array
    {
        return match ($role) {
            'store_manager' => [
                StorePermission::DASHBOARD_VIEW->value,
                StorePermission::PRODUCTS_MANAGE->value,
                StorePermission::OFFERS_MANAGE->value,
                StorePermission::CLAIMS_MANAGE->value,
                StorePermission::ORDERS_MANAGE->value,
                StorePermission::EMPLOYEES_VIEW->value,
                StorePermission::BRANCHES_VIEW->value,
                StorePermission::ANALYTICS_VIEW->value,
                StorePermission::REVIEWS_VIEW->value,
            ],
            'store_employee' => [
                StorePermission::CLAIMS_VIEW->value,
                StorePermission::CLAIMS_REDEEM->value,
            ],
            'cashier' => [
                StorePermission::CLAIMS_VIEW->value,
                StorePermission::CLAIMS_REDEEM->value,
                StorePermission::ORDERS_VIEW->value,
            ],
            'branch_manager' => [
                StorePermission::DASHBOARD_VIEW->value,
                StorePermission::CLAIMS_MANAGE->value,
                StorePermission::ORDERS_VIEW->value,
                StorePermission::EMPLOYEES_VIEW->value,
                StorePermission::BRANCHES_VIEW->value,
            ],
            'inventory_manager' => [
                StorePermission::PRODUCTS_VIEW->value,
                StorePermission::PRODUCTS_CREATE->value,
                StorePermission::PRODUCTS_UPDATE->value,
                StorePermission::OFFERS_VIEW->value,
                StorePermission::OFFERS_UPDATE->value,
            ],
            'content_manager' => [
                StorePermission::PRODUCTS_MANAGE->value,
                StorePermission::OFFERS_MANAGE->value,
                StorePermission::REVIEWS_VIEW->value,
            ],
            'support_agent' => [
                StorePermission::ORDERS_VIEW->value,
                StorePermission::CLAIMS_VIEW->value,
                StorePermission::REVIEWS_VIEW->value,
            ],
            default => [],
        };
    }

    public function addBranchAddress(array $data): Address
    {
        $address = Address::create($data);

        $this->addresses()->attach($address->id, [
            'label' => 'branch',
        ]);

        return $address;
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
