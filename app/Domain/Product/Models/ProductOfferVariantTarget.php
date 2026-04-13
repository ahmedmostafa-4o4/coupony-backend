<?php

namespace App\Domain\Product\Models;

use App\Domain\Product\Enums\ProductOfferTargetRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOfferVariantTarget extends Model
{
    use HasFactory;

    protected $table = 'product_offer_variant_targets';

    protected $fillable = [
        'offer_id',
        'variant_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => ProductOfferTargetRole::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function offer()
    {
        return $this->belongsTo(ProductOffer::class, 'offer_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
