<?php

namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'product_variants';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'title',
        'option_summary',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'currency',
        'sort_order',
        'is_default',
        'is_active',
        'sale_count',
        'redemption_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'sort_order' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sale_count' => 'integer',
            'redemption_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant) {
            if (blank($variant->id)) {
                $variant->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function attributes()
    {
        return $this->hasMany(ProductVariantAttribute::class, 'variant_id')->orderBy('sort_order')->orderBy('id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\ProductVariantFactory::new();
    }
}
