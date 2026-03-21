<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Models\StoreFollowers;
use App\Domain\Store\Models\StoreHours;
use App\Domain\User\Models\Address;
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
        'shard_key',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => \App\Domain\Store\Enums\StoreStatus::class,
            'commission_rate' => 'decimal:4',
            'total_sales' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
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
                $store->id = (string)\Illuminate\Support\Str::uuid();
            }
        });
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
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
