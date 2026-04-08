<?php

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantAttribute extends Model
{
    protected $table = 'product_variant_attributes';

    public $timestamps = false;

    protected $fillable = [
        'variant_id',
        'attribute_name',
        'attribute_value',
        'sort_order',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
