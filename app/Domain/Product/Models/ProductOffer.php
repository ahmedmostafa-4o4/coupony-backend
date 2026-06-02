<?php

namespace App\Domain\Product\Models;

use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOffer extends Model
{
    use HasFactory;

    protected $table = 'product_offers';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'type',
        'status',
        'label',
        'starts_at',
        'ends_at',
        'duration_days',
        'duration_hours',
        'claim_expiration_minutes',
        'fixed_amount',
        'percentage_value',
        'max_discount',
        'buy_qty',
        'get_qty',
        'allow_mix_buy_variants',
        'allow_mix_reward_variants',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductOfferType::class,
            'status' => ProductOfferStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'duration_days' => 'integer',
            'duration_hours' => 'integer',
            'claim_expiration_minutes' => 'integer',
            'fixed_amount' => 'decimal:2',
            'percentage_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'buy_qty' => 'integer',
            'get_qty' => 'integer',
            'allow_mix_buy_variants' => 'boolean',
            'allow_mix_reward_variants' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProductOffer $offer) {
            if (blank($offer->id)) {
                $offer->id = (string) \Illuminate\Support\Str::uuid();
            }

            $offer->status ??= ProductOfferStatus::ACTIVE;
            $offer->allow_mix_buy_variants ??= false;
            $offer->allow_mix_reward_variants ??= false;
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function targets()
    {
        return $this->hasMany(ProductOfferVariantTarget::class, 'offer_id');
    }

    public function claims()
    {
        return $this->hasMany(OfferClaim::class, 'offer_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\ProductOfferFactory::new();
    }
}
