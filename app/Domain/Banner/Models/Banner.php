<?php

namespace App\Domain\Banner\Models;

use App\Domain\Banner\Enums\BannerStatus;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\Address;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'requested_by',
        'image_url',
        'discount_label',
        'date_range',
        'cta_label',
        'terms_of_use',
        'end_time',
        'priority',
        'is_active',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'end_time' => 'datetime',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'status' => BannerStatus::class,
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Banner $banner) {
            if (blank($banner->id)) {
                $banner->id = (string) Str::uuid();
            }

            $banner->status ??= BannerStatus::PENDING;
            $banner->is_active ??= false;
            $banner->priority ??= 100;
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function offers()
    {
        return $this->belongsToMany(ProductOffer::class, 'banner_offers', 'banner_id', 'offer_id')
            ->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(Address::class, 'banner_branches', 'banner_id', 'address_id')
            ->withTimestamps();
    }

    public function likes()
    {
        return $this->hasMany(BannerLike::class, 'banner_id');
    }

    public function favorites()
    {
        return $this->hasMany(BannerFavorite::class, 'banner_id');
    }

    public function shares()
    {
        return $this->hasMany(BannerShare::class, 'banner_id');
    }

    public function claims()
    {
        return $this->hasMany(BannerClaim::class, 'banner_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\BannerFactory::new();
    }
}
