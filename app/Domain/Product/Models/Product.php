<?php

namespace App\Domain\Product\Models;

use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
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
        'base_price',
        'compare_at_price',
        'currency',
        'sku',
        'status',
        'approval_status',
        'published_revision_no',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'admin_notes',
        'is_featured',
        'sale_count',
        'redemption_count',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'status' => ProductStatus::class,
            'approval_status' => ProductApprovalStatus::class,
            'published_revision_no' => 'integer',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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

            $product->status ??= ProductStatus::INACTIVE;
            $product->approval_status ??= ProductApprovalStatus::PENDING;
            $product->published_revision_no ??= 0;
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

    public function offer()
    {
        return $this->hasOne(ProductOffer::class, 'product_id');
    }

    public function revisions()
    {
        return $this->hasMany(ProductRevision::class, 'product_id')->orderByDesc('revision_no')->orderByDesc('id');
    }

    public function offerClaims()
    {
        return $this->hasMany(OfferClaim::class, 'product_id');
    }

    public function pendingRevision()
    {
        return $this->hasOne(ProductRevision::class, 'product_id')
            ->where('status', \App\Domain\Product\Enums\ProductRevisionStatus::PENDING);
    }

    public function latestRequestedChangesRevision()
    {
        return $this->hasOne(ProductRevision::class, 'product_id')
            ->where('status', \App\Domain\Product\Enums\ProductRevisionStatus::REJECTED)
            ->whereNotNull('requested_changes')
            ->latestOfMany('revision_no');
    }

    public function activeVariants()
    {
        return $this->variants()->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query
            ->where('status', ProductStatus::ACTIVE)
            ->where('approval_status', ProductApprovalStatus::APPROVED);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    protected static function newFactory()
    {
        return \Database\Factories\ProductFactory::new();
    }
}
