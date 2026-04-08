<?php

namespace App\Domain\Product\Models;

use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Enums\ProductType;
use App\Domain\Store\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'store_id',
        'title',
        'slug',
        'short_description',
        'description',
        'product_type',
        'base_price',
        'compare_at_price',
        'currency',
        'sku',
        'status',
        'is_featured',
        'sale_count',
        'redemption_count',
    ];

    protected function casts(): array
    {
        return [
            'product_type' => ProductType::class,
            'base_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'sale_count' => 'integer',
            'redemption_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (blank($product->id)) {
                $product->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id')->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants()
    {
        return $this->variants()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ProductStatus::ACTIVE);
    }

    protected static function newFactory()
    {
        return \Database\Factories\ProductFactory::new();
    }
}
