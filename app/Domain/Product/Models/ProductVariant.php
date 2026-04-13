<?php

namespace App\Domain\Product\Models;

use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Models\ProductOfferVariantTarget;
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
        'inventory_mode',
        'stock_qty',
        'low_stock_threshold',
        'allow_backorder',
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
            'inventory_mode' => InventoryMode::class,
            'stock_qty' => 'integer',
            'low_stock_threshold' => 'integer',
            'allow_backorder' => 'boolean',
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

            $variant->inventory_mode ??= InventoryMode::UNLIMITED;
            $variant->allow_backorder ??= false;

            if ($variant->inventory_mode === InventoryMode::UNLIMITED) {
                $variant->stock_qty = null;
                $variant->low_stock_threshold = null;
            } else {
                $variant->stock_qty ??= 0;
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

    public function offerTargets()
    {
        return $this->hasMany(ProductOfferVariantTarget::class, 'variant_id');
    }

    public function isInStock(): bool
    {
        if ($this->inventory_mode === InventoryMode::UNLIMITED) {
            return true;
        }

        return ($this->stock_qty ?? 0) > 0 || $this->allow_backorder;
    }

    protected static function newFactory()
    {
        return \Database\Factories\ProductVariantFactory::new();
    }
}
