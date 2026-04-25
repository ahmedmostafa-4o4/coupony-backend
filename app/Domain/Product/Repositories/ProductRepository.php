<?php

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferTargetRole;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductOfferVariantTarget;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
use DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductRepository
{
    public function create(Store $store, array $attributes): Product
    {
        $attributes['store_id'] = $store->id;

        return Product::create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->update($attributes);

        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    public function updateStatus(Product $product, string $status): Product
    {
        $product->update(['status' => $status]);

        return $product->fresh();
    }

    public function syncCategories(Product $product, array $categoryIds): void
    {
        $product->categories()->sync($categoryIds);
    }

    public function replaceImages(Product $product, array $images): array
    {
        $storedPaths = [];
        $deletedPaths = $product->images()
            ->pluck('image_url')
            ->filter()
            ->values()
            ->all();

        $records = collect($images)
            ->map(function (array $image) use ($product, &$storedPaths) {
                $path = $image['file']->store("products/{$product->id}/images", 'public');
                $storedPaths[] = $path;

                return [
                    'image_url' => $path,
                    'sort_order' => (int) ($image['sort_order'] ?? 0),
                    'is_primary' => (bool) ($image['is_primary'] ?? false),
                    'created_at' => now(),
                ];
            })
            ->all();

        $product->images()->delete();

        if ($records !== []) {
            $product->images()->createMany($records);
        }

        return [
            'stored' => $storedPaths,
            'deleted' => $deletedPaths,
        ];
    }

    public function replaceVariants(Product $product, array $variants): void
    {
        $product->variants()->with('attributes')->get()->each(function (ProductVariant $variant) {
            $variant->attributes()->delete();
            $variant->forceDelete();
        });

        foreach ($variants as $variantData) {
            $variant = $product->variants()->create([
                'title' => $variantData['title'],
                'option_summary' => $variantData['option_summary'],
                'sku' => $variantData['sku'],
                'barcode' => $variantData['barcode'],
                'original_price' => $variantData['original_price'],
                'price' => $variantData['price'],
                'compare_at_price' => $variantData['compare_at_price'],
                'currency' => $variantData['currency'],
                'sort_order' => $variantData['sort_order'],
                'is_default' => $variantData['is_default'],
                'is_active' => $variantData['is_active'],
                'inventory_mode' => $variantData['inventory_mode'] ?? InventoryMode::UNLIMITED,
                'stock_qty' => $variantData['stock_qty'] ?? null,
                'low_stock_threshold' => $variantData['low_stock_threshold'] ?? null,
                'allow_backorder' => $variantData['allow_backorder'] ?? false,
            ]);

            if (($variantData['attributes'] ?? []) !== []) {
                $variant->attributes()->createMany(
                    collect($variantData['attributes'])
                        ->map(fn (array $attribute) => [
                            'attribute_name' => $attribute['attribute_name'],
                            'attribute_value' => $attribute['attribute_value'],
                            'sort_order' => $attribute['sort_order'],
                            'created_at' => now(),
                        ])
                        ->all()
                );
            }
        }
    }

    public function syncOffer(Product $product, array $attributes): ProductOffer
    {
        $offer = $product->offer()->updateOrCreate(
            ['product_id' => $product->id],
            [
                'type' => $attributes['type'],
                'status' => $attributes['status'] ?? ProductOfferStatus::ACTIVE,
                'label' => $attributes['label'] ?? null,
                'starts_at' => $attributes['starts_at'] ?? null,
                'ends_at' => $attributes['ends_at'] ?? null,
                'claim_expiration_minutes' => $attributes['claim_expiration_minutes'] ?? null,
                'fixed_amount' => $attributes['fixed_amount'] ?? null,
                'percentage_value' => $attributes['percentage_value'] ?? null,
                'max_discount' => $attributes['max_discount'] ?? null,
                'buy_qty' => $attributes['buy_qty'] ?? null,
                'get_qty' => $attributes['get_qty'] ?? null,
                'allow_mix_buy_variants' => (bool) ($attributes['allow_mix_buy_variants'] ?? false),
                'allow_mix_reward_variants' => (bool) ($attributes['allow_mix_reward_variants'] ?? false),
            ]
        );

        $offer->targets()->delete();

        if ($offer->type !== ProductOfferType::BUY_X_GET_Y) {
            return $offer->load('targets');
        }

        $variantsBySku = $product->variants()
            ->get(['id', 'sku'])
            ->filter(fn (ProductVariant $variant) => filled($variant->sku))
            ->keyBy(fn (ProductVariant $variant) => mb_strtolower((string) $variant->sku));

        $records = collect([
            ProductOfferTargetRole::BUY->value => $attributes['buy_variant_skus'] ?? [],
            ProductOfferTargetRole::REWARD->value => $attributes['reward_variant_skus'] ?? [],
        ])->flatMap(function (array $skus, string $role) use ($variantsBySku, $offer) {
            return collect($skus)
                ->map(fn ($sku) => mb_strtolower((string) $sku))
                ->filter()
                ->map(function (string $normalizedSku) use ($variantsBySku, $offer, $role) {
                    $variant = $variantsBySku->get($normalizedSku);

                    if (! $variant) {
                        return null;
                    }

                    return [
                        'offer_id' => $offer->id,
                        'variant_id' => $variant->id,
                        'role' => $role,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->filter();
        })->values()->all();

        if ($records !== []) {
            ProductOfferVariantTarget::query()->insert($records);
        }

        return $offer->load('targets');
    }

    public function pendingRevision(Product $product): ?ProductRevision
    {
        return $product->revisions()
            ->where('status', ProductRevisionStatus::PENDING)
            ->latest('revision_no')
            ->latest('id')
            ->first();
    }

    public function sellerRevisionPaginate(Product $product, int $perPage = 15): LengthAwarePaginator
    {
        return ProductRevision::query()
            ->where('product_id', $product->id)
            ->with('product')
            ->latest('revision_no')
            ->latest('id')
            ->paginate($perPage);
    }

    public function pendingRevisionsPaginate(int $perPage = 15): LengthAwarePaginator
    {
        return ProductRevision::query()
            ->where('status', ProductRevisionStatus::PENDING)
            ->with('product')
            ->latest('submitted_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function loadRevision(ProductRevision $revision): ProductRevision
    {
        return $revision->load('product');
    }

    public function loadOfferClaim(OfferClaim $claim): OfferClaim
    {
        return $claim->load(['user', 'store', 'product', 'offer', 'redeemedBy']);
    }

    public function nextRevisionNumber(Product $product): int
    {
        return ((int) $product->revisions()->max('revision_no')) + 1;
    }

    public function storePendingImages(Product $product, array $images): array
    {
        $storedPaths = [];

        $records = collect($images)
            ->map(function (array $image) use ($product, &$storedPaths) {
                $path = $image['image_url'] ?? null;

                if ($image['file'] ?? null) {
                    $path = $image['file']->store("products/{$product->id}/images", 'public');
                    $storedPaths[] = $path;
                }

                return [
                    'image_url' => $path,
                    'sort_order' => (int) ($image['sort_order'] ?? 0),
                    'is_primary' => (bool) ($image['is_primary'] ?? false),
                ];
            })
            ->all();

        return [
            'stored' => $storedPaths,
            'images' => $records,
        ];
    }

    public function updateImageMetadata(Product $product, array $images): void
    {
        $product->loadMissing('images');

        $imagesById = $product->images->keyBy('id');
        $imagesByPath = $product->images->keyBy('image_url');
        $matchedImages = [];

        foreach ($images as $index => $imageData) {
            $target = null;

            if (filled($imageData['id'] ?? null)) {
                $target = $imagesById->get((int) $imageData['id']);
            }

            if (! $target && filled($imageData['image_url'] ?? null)) {
                $target = $imagesByPath->get($imageData['image_url']);
            }

            if (! $target) {
                $target = $product->images->get($index);
            }

            if (! $target) {
                continue;
            }

            $matchedImages[] = [
                'model' => $target,
                'sort_order' => (int) ($imageData['sort_order'] ?? $target->sort_order),
                'is_primary' => (bool) ($imageData['is_primary'] ?? $target->is_primary),
            ];
        }

        if ($matchedImages === []) {
            return;
        }

        if (collect($matchedImages)->contains(fn (array $image) => $image['is_primary'])) {
            $product->images()->update(['is_primary' => false]);
        }

        foreach ($matchedImages as $image) {
            $product->images()->whereKey($image['model']->getKey())->update([
                'sort_order' => $image['sort_order'],
                'is_primary' => $image['is_primary'],
            ]);
        }
    }

    public function replaceImagesFromSnapshot(Product $product, array $images): void
    {
        $product->images()->delete();

        if ($images === []) {
            return;
        }

        $product->images()->createMany(
            collect($images)->map(fn (array $image) => [
                'image_url' => $image['image_url'],
                'sort_order' => (int) ($image['sort_order'] ?? 0),
                'is_primary' => (bool) ($image['is_primary'] ?? false),
                'created_at' => now(),
            ])->all()
        );
    }

    public function snapshotPayload(Product $product): array
    {
        $product = $product->load([
            'categories:id',
            'images',
            'variants.attributes',
            'offer.targets.variant:id,sku',
        ]);

        /** @var ProductOffer|null $offer */
        $offer = $product->offer;

        return [
            'product' => [
                'title' => $product->title,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'base_price' => $product->base_price,
                'compare_at_price' => $product->compare_at_price,
                'currency' => $product->currency,
                'sku' => $product->sku,
                'is_featured' => $product->is_featured,
                'category_ids' => $product->categories->pluck('id')->values()->all(),
            ],
            'images' => $product->images
                ->map(fn (ProductImage $image) => [
                    'image_url' => $image->image_url,
                    'sort_order' => $image->sort_order,
                    'is_primary' => $image->is_primary,
                ])
                ->values()
                ->all(),
            'variants' => $product->variants
                ->map(fn (ProductVariant $variant) => [
                    'title' => $variant->title,
                    'option_summary' => $variant->option_summary,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'original_price' => $variant->original_price,
                    'price' => $variant->price,
                    'compare_at_price' => $variant->compare_at_price,
                    'currency' => $variant->currency,
                    'sort_order' => $variant->sort_order,
                    'is_default' => $variant->is_default,
                    'is_active' => $variant->is_active,
                    'inventory_mode' => $variant->inventory_mode?->value ?? $variant->inventory_mode,
                    'stock_qty' => $variant->stock_qty,
                    'low_stock_threshold' => $variant->low_stock_threshold,
                    'allow_backorder' => $variant->allow_backorder,
                    'attributes' => $variant->attributes
                        ->map(fn ($attribute) => [
                            'attribute_name' => $attribute->attribute_name,
                            'attribute_value' => $attribute->attribute_value,
                            'sort_order' => $attribute->sort_order,
                        ])
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'offer' => $offer ? [
                'type' => $offer->type?->value ?? $offer->type,
                'status' => $offer->status?->value ?? $offer->status,
                'label' => $offer->label,
                'starts_at' => $offer->starts_at?->toIso8601String(),
                'ends_at' => $offer->ends_at?->toIso8601String(),
                'claim_expiration_minutes' => $offer->claim_expiration_minutes,
                'fixed_amount' => $offer->fixed_amount,
                'percentage_value' => $offer->percentage_value,
                'max_discount' => $offer->max_discount,
                'buy_qty' => $offer->buy_qty,
                'get_qty' => $offer->get_qty,
                'allow_mix_buy_variants' => $offer->allow_mix_buy_variants,
                'allow_mix_reward_variants' => $offer->allow_mix_reward_variants,
                'buy_variant_skus' => $offer->targets
                    ->filter(fn (ProductOfferVariantTarget $target) => ($target->role?->value ?? $target->role) === ProductOfferTargetRole::BUY->value)
                    ->map(fn (ProductOfferVariantTarget $target) => $target->variant?->sku)
                    ->filter()
                    ->values()
                    ->all(),
                'reward_variant_skus' => $offer->targets
                    ->filter(fn (ProductOfferVariantTarget $target) => ($target->role?->value ?? $target->role) === ProductOfferTargetRole::REWARD->value)
                    ->map(fn (ProductOfferVariantTarget $target) => $target->variant?->sku)
                    ->filter()
                    ->values()
                    ->all(),
            ] : null,
        ];
    }

    public function applySnapshot(Product $product, array $payload): Product
    {
        $productAttributes = $payload['product'] ?? [];

        $product = tap($this->update($product, [
            'title' => $productAttributes['title'] ?? $product->title,
            'slug' => $productAttributes['slug'] ?? $product->slug,
            'short_description' => $productAttributes['short_description'] ?? null,
            'description' => $productAttributes['description'] ?? null,
            'base_price' => $productAttributes['base_price'] ?? null,
            'compare_at_price' => $productAttributes['compare_at_price'] ?? null,
            'currency' => $productAttributes['currency'] ?? $product->currency,
            'sku' => $productAttributes['sku'] ?? null,
            'is_featured' => (bool) ($productAttributes['is_featured'] ?? false),
        ]), function (Product $updatedProduct) use ($productAttributes) {
            $this->syncCategories($updatedProduct, $productAttributes['category_ids'] ?? []);
        });

        $this->replaceImagesFromSnapshot($product, $payload['images'] ?? []);
        $this->replaceVariants($product, $payload['variants'] ?? []);

        if (is_array($payload['offer'] ?? null)) {
            $this->syncOffer($product, $payload['offer']);
        }

        return $this->loadSellerProduct($product->fresh());
    }

    public function createVariant(Product $product, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($product, $attributes) {
            $shouldBeDefault = (bool) ($attributes['is_default'] ?? false) || ! $product->variants()->exists();

            if ($shouldBeDefault) {
                $this->clearDefaultVariant($product);
            }

            $variant = $product->variants()->create([
                'title' => $attributes['title'],
                'option_summary' => $attributes['option_summary'] ?? null,
                'sku' => $attributes['sku'] ?? null,
                'barcode' => $attributes['barcode'] ?? null,
                'original_price' => $attributes['original_price'],
                'price' => $attributes['price'] ?? null,
                'compare_at_price' => $attributes['compare_at_price'] ?? null,
                'currency' => $attributes['currency'],
                'sort_order' => $attributes['sort_order'] ?? (($product->variants()->max('sort_order') ?? -1) + 1),
                'is_default' => $shouldBeDefault,
                'is_active' => (bool) ($attributes['is_active'] ?? true),
                'inventory_mode' => $attributes['inventory_mode'] ?? InventoryMode::UNLIMITED,
                'stock_qty' => $attributes['stock_qty'] ?? null,
                'low_stock_threshold' => $attributes['low_stock_threshold'] ?? null,
                'allow_backorder' => (bool) ($attributes['allow_backorder'] ?? false),
            ]);

            if (($attributes['attributes'] ?? []) !== []) {
                $this->replaceVariantAttributes($variant, $attributes['attributes']);
            }

            return $this->loadVariant($variant);
        });
    }

    public function updateVariant(Product $product, ProductVariant $variant, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($product, $variant, $attributes) {
            if (($attributes['is_default'] ?? false) === true) {
                $this->clearDefaultVariant($product, $variant->id);
            }

            $variant->update($attributes);

            if (! $product->variants()->where('is_default', true)->exists()) {
                $variant->update(['is_default' => true]);
            }

            return $this->loadVariant($variant->fresh());
        });
    }

    public function syncDerivedProductPricing(Product $product, array $pricingSummary): Product
    {
        $product->update([
            'base_price' => $pricingSummary['base_price'],
            'compare_at_price' => $pricingSummary['compare_at_price'],
        ]);

        return $product->fresh();
    }

    public function deleteVariant(Product $product, ProductVariant $variant): bool
    {
        return DB::transaction(function () use ($product, $variant) {
            $wasDefault = $variant->is_default;
            $deleted = (bool) $variant->delete();

            if ($deleted && $wasDefault) {
                $fallback = $product->variants()->orderBy('sort_order')->orderBy('id')->first();

                if ($fallback) {
                    $fallback->update(['is_default' => true]);
                }
            }

            return $deleted;
        });
    }

    public function replaceVariantAttributes(ProductVariant $variant, array $attributes): ProductVariant
    {
        return DB::transaction(function () use ($variant, $attributes) {

            $variant->attributes()->delete();

            if ($attributes !== []) {
                $variant->attributes()->createMany(
                    collect($attributes)->map(fn (array $attribute) => [
                        'attribute_name' => $attribute['attribute_name'],
                        'attribute_value' => $attribute['attribute_value'],
                        'sort_order' => $attribute['sort_order'] ?? 0,
                        'created_at' => now(),
                    ])->all()
                );
            }

            return $this->loadVariant($variant->fresh());
        });
    }

    public function loadVariant(ProductVariant $variant): ProductVariant
    {
        return $variant->load('attributes');
    }

    public function addImages(Product $product, array $images): Collection
    {
        return DB::transaction(function () use ($product, $images) {
            $createdImages = collect();
            $existingImageCount = $product->images()->count();
            $currentMaxSortOrder = $product->images()->max('sort_order') ?? -1;

            foreach ($images as $index => $image) {
                $path = $image['file']->store("products/{$product->id}/images", 'public');
                $shouldBePrimary = (bool) ($image['is_primary'] ?? false);

                if ($shouldBePrimary) {
                    $product->images()->update(['is_primary' => false]);
                }

                if (! $shouldBePrimary && $existingImageCount === 0 && $index === 0) {
                    $shouldBePrimary = true;
                }

                $createdImages->push(
                    $product->images()->create([
                        'image_url' => $path,
                        'sort_order' => $image['sort_order'] ?? ($currentMaxSortOrder + $index + 1),
                        'is_primary' => $shouldBePrimary,
                        'created_at' => now(),
                    ])
                );
            }

            return $createdImages;
        });
    }

    public function deleteImage(Product $product, ProductImage $image): bool
    {
        return DB::transaction(function () use ($product, $image) {
            $wasPrimary = $image->is_primary;
            $deleted = (bool) $image->delete();

            if ($deleted && $wasPrimary) {
                $fallback = $product->images()->orderBy('sort_order')->orderBy('id')->first();

                if ($fallback) {
                    $fallback->update(['is_primary' => true]);
                }
            }

            return $deleted;
        });
    }

    public function reorderImages(Product $product, array $images): Collection
    {
        return DB::transaction(function () use ($product, $images) {
            $imageIds = collect($images)->pluck('id')->all();
            $existingIds = $product->images()->pluck('id')->all();

            sort($imageIds);
            sort($existingIds);

            if ($imageIds !== $existingIds) {
                throw new ModelNotFoundException('Image reorder payload must include every product image exactly once.');
            }

            foreach ($images as $image) {
                $product->images()->whereKey($image['id'])->update([
                    'sort_order' => $image['sort_order'],
                ]);
            }

            return $product->images()->get();
        });
    }

    public function setPrimaryImage(Product $product, ProductImage $image): ProductImage
    {
        return DB::transaction(function () use ($product, $image) {
            $product->images()->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);

            return $image->fresh();
        });
    }

    public function sellerPaginate(Store $store, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->with($this->sellerRelations())
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $this->applySearch($query, $filters['search'])
            )
            ->when(
                array_key_exists('is_featured', $filters) && $filters['is_featured'] !== null,
                fn (Builder $query) => $query->where('is_featured', $this->normalizeBoolean($filters['is_featured']))
            )
            ->latest()
            ->paginate($perPage);
    }

    public function adminPaginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with($this->adminRelations())
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['approval_status'] ?? null),
                fn (Builder $query) => $query->where('approval_status', $filters['approval_status'])
            )
            ->when(
                filled($filters['store_id'] ?? null),
                fn (Builder $query) => $query->where('store_id', $filters['store_id'])
            )
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $this->applySearch($query, $filters['search'])
            )
            ->latest()
            ->paginate($perPage);
    }

    public function publicPaginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->active()
            ->with($this->publicRelations())
            ->when(
                filled($filters['category'] ?? null),
                fn (Builder $query) => $query->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereKey($filters['category']))
            )
            ->when(
                filled($filters['search'] ?? null),
                fn (Builder $query) => $this->applySearch($query, $filters['search'])
            )
            ->when(
                array_key_exists('featured', $filters) && $filters['featured'] !== null,
                fn (Builder $query) => $query->where('is_featured', $this->normalizeBoolean($filters['featured']))
            )
            ->latest()
            ->paginate($perPage);
    }

    public function publicCategoryProductsPaginate(Category $category, int $perPage = 15): LengthAwarePaginator
    {
        return $category->products()
            ->where('status', ProductStatus::ACTIVE->value)
            ->where('approval_status', ProductApprovalStatus::APPROVED->value)
            ->with($this->publicRelations())
            ->latest()
            ->paginate($perPage);
    }

    public function publicCategories(): Collection
    {
        return Category::query()
            ->active()
            ->withCount([
                'products' => fn ($query) => $query
                    ->where('status', ProductStatus::ACTIVE->value)
                    ->where('approval_status', ProductApprovalStatus::APPROVED->value),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function loadSellerProduct(Product $product): Product
    {
        return $product->load($this->sellerRelations());
    }

    public function loadAdminProduct(Product $product): Product
    {
        return $product->load($this->adminRelations());
    }

    public function loadPublicProduct(Product $product): Product
    {
        return $product->load($this->publicRelations());
    }

    public function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function sellerRelations(): array
    {
        return [
            'store',
            'categories',
            'images',
            'variants.attributes',
            'offer.targets',
            'pendingRevision',
        ];
    }

    public function publicRelations(): array
    {
        return [
            'store',
            'categories' => fn ($query) => $query->active(),
            'images',
            'variants' => fn ($query) => $query->where('is_active', true)->with('attributes'),
            'offer.targets',
        ];
    }

    public function adminRelations(): array
    {
        return [
            'store',
            'categories',
            'images',
            'variants.attributes',
            'offer.targets',
            'pendingRevision',
        ];
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $nestedQuery) use ($search) {
            $nestedQuery
                ->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('short_description', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    private function normalizeBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function clearDefaultVariant(Product $product, ?string $exceptVariantId = null): void
    {
        $query = $product->variants()->where('is_default', true);

        if ($exceptVariantId !== null) {
            $query->whereKeyNot($exceptVariantId);
        }

        $query->update(['is_default' => false]);
    }
}
