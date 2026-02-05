<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Models\StoreFollowers;
use App\Domain\Store\Models\StoreHours;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guard_name = 'web'; // <-- matches your role

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
        'shard_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:4',
            'total_sales' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($store) {
            // Generate UUID if not set
            if (empty($store->id)) {
                $store->id = (string) \Illuminate\Support\Str::uuid();
            }

            //  // Generate shard key for partitioning
            // if (empty($user->shard_key)) {
            //     $store->shard_key = substr(md5($store->email), 0, 8);
            // }
        });
    }

    public function owner()
    {
        return $this->belongsTo(
            User::class,
            'owner_user_id'
        );
    }

    public function verifications()
    {
        return $this->hasMany(StoreVerification::class, 'store_id');
    }

    /**
     * Get all users who have access to this store (staff/managers).
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
            ->withPivot('role_id', 'granted_at', 'granted_by_user_id', 'expires_at')
            ->withTimestamps();
    }

    /**
     * Get store staff members.
     */
    public function staff(): BelongsToMany
    {
        return $this->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['store_manager', 'store_staff']);
            });
    }

    public function managers(): BelongsToMany
    {
        return $this->users()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'store_manager');
            });
    }

    // public function staffs(): BelongsToMany
    // {
    //     return $this->users()
    //         ->whereHas('roles', function ($query) {
    //             $query->where('name', 'store_staff');
    //         });
    // }



    /**
     * Get free plan ID.
     */
    // private function getFreePlanId(): int
    // {
    //     return \Domain\Subscription\Models\SubscriptionPlan::where('slug', 'free')->first()->id;
    // }

    public function hours()
    {
        return $this->hasMany(StoreHours::class, 'store_id');
    }


    public function followers()
    {
        return $this->hasMany(StoreFollowers::class, 'store_id');
    }

    public function userRoles()
    {
        return $this->hasMany(UserRoles::class, 'store_id');
    }
}
