<?php

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Store\Models\Store;
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
                'price' => $variantData['price'],
                'compare_at_price' => $variantData['compare_at_price'],
                'currency' => $variantData['currency'],
                'sort_order' => $variantData['sort_order'],
                'is_default' => $variantData['is_default'],
                'is_active' => $variantData['is_active'],
            ]);

            if (($variantData['attributes'] ?? []) !== []) {
                $variant->attributes()->createMany(
                    collect($variantData['attributes'])
                        ->map(fn(array $attribute) => [
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

    public function createVariant(Product $product, array $attributes): ProductVariant
    {
        $shouldBeDefault = (bool) ($attributes['is_default'] ?? false) || !$product->variants()->exists();

        if ($shouldBeDefault) {
            $this->clearDefaultVariant($product);
        }

        $variant = $product->variants()->create([
            'title' => $attributes['title'],
            'option_summary' => $attributes['option_summary'] ?? null,
            'sku' => $attributes['sku'] ?? null,
            'barcode' => $attributes['barcode'] ?? null,
            'price' => $attributes['price'] ?? null,
            'compare_at_price' => $attributes['compare_at_price'] ?? null,
            'currency' => $attributes['currency'],
            'sort_order' => $attributes['sort_order'] ?? (($product->variants()->max('sort_order') ?? -1) + 1),
            'is_default' => $shouldBeDefault,
            'is_active' => (bool) ($attributes['is_active'] ?? true),
        ]);

        if (($attributes['attributes'] ?? []) !== []) {
            $this->replaceVariantAttributes($variant, $attributes['attributes']);
        }

        return $this->loadVariant($variant);
    }

    public function updateVariant(Product $product, ProductVariant $variant, array $attributes): ProductVariant
    {
        if (($attributes['is_default'] ?? false) === true) {
            $this->clearDefaultVariant($product, $variant->id);
        }

        $variant->update($attributes);

        if (!$product->variants()->where('is_default', true)->exists()) {
            $variant->update(['is_default' => true]);
        }

        return $this->loadVariant($variant->fresh());
    }

    public function deleteVariant(Product $product, ProductVariant $variant): bool
    {
        $wasDefault = $variant->is_default;
        $deleted = (bool) $variant->delete();

        if ($deleted && $wasDefault) {
            $fallback = $product->variants()->orderBy('sort_order')->orderBy('id')->first();

            if ($fallback) {
                $fallback->update(['is_default' => true]);
            }
        }

        return $deleted;
    }

    public function replaceVariantAttributes(ProductVariant $variant, array $attributes): ProductVariant
    {
        $variant->attributes()->delete();

        if ($attributes !== []) {
            $variant->attributes()->createMany(
                collect($attributes)->map(fn(array $attribute) => [
                    'attribute_name' => $attribute['attribute_name'],
                    'attribute_value' => $attribute['attribute_value'],
                    'sort_order' => $attribute['sort_order'] ?? 0,
                    'created_at' => now(),
                ])->all()
            );
        }

        return $this->loadVariant($variant->fresh());
    }

    public function loadVariant(ProductVariant $variant): ProductVariant
    {
        return $variant->load('attributes');
    }

    public function addImages(Product $product, array $images): Collection
    {
        $createdImages = collect();
        $existingImageCount = $product->images()->count();
        $currentMaxSortOrder = $product->images()->max('sort_order') ?? -1;

        foreach ($images as $index => $image) {
            $path = $image['file']->store("products/{$product->id}/images", 'public');
            $shouldBePrimary = (bool) ($image['is_primary'] ?? false);

            if ($shouldBePrimary) {
                $product->images()->update(['is_primary' => false]);
            }

            if (!$shouldBePrimary && $existingImageCount === 0 && $index === 0) {
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
    }

    public function deleteImage(Product $product, ProductImage $image): bool
    {
        $wasPrimary = $image->is_primary;
        $deleted = (bool) $image->delete();

        if ($deleted && $wasPrimary) {
            $fallback = $product->images()->orderBy('sort_order')->orderBy('id')->first();

            if ($fallback) {
                $fallback->update(['is_primary' => true]);
            }
        }

        return $deleted;
    }

    public function reorderImages(Product $product, array $images): Collection
    {
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
    }

    public function setPrimaryImage(Product $product, ProductImage $image): ProductImage
    {
        $product->images()->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return $image->fresh();
    }

    public function sellerPaginate(Store $store, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->where('store_id', $store->id)
            ->with($this->sellerRelations())
            ->when(
                filled($filters['status'] ?? null),
                fn(Builder $query) => $query->where('status', $filters['status'])
            )
            ->when(
                filled($filters['search'] ?? null),
                fn(Builder $query) => $this->applySearch($query, $filters['search'])
            )
            ->when(
                array_key_exists('is_featured', $filters) && $filters['is_featured'] !== null,
                fn(Builder $query) => $query->where('is_featured', $this->normalizeBoolean($filters['is_featured']))
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
                fn(Builder $query) => $query->whereHas('categories', fn(Builder $categoryQuery) => $categoryQuery->whereKey($filters['category']))
            )
            ->when(
                filled($filters['search'] ?? null),
                fn(Builder $query) => $this->applySearch($query, $filters['search'])
            )
            ->when(
                array_key_exists('featured', $filters) && $filters['featured'] !== null,
                fn(Builder $query) => $query->where('is_featured', $this->normalizeBoolean($filters['featured']))
            )
            ->latest()
            ->paginate($perPage);
    }

    public function publicCategoryProductsPaginate(Category $category, int $perPage = 15): LengthAwarePaginator
    {
        return $category->products()
            ->where('status', ProductStatus::ACTIVE->value)
            ->with($this->publicRelations())
            ->latest()
            ->paginate($perPage);
    }

    public function publicCategories(): Collection
    {
        return Category::query()
            ->active()
            ->withCount([
                'products' => fn($query) => $query->where('status', ProductStatus::ACTIVE->value),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function loadSellerProduct(Product $product): Product
    {
        return $product->load($this->sellerRelations());
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
        ];
    }

    public function publicRelations(): array
    {
        return [
            'store',
            'categories' => fn($query) => $query->active(),
            'images',
            'variants' => fn($query) => $query->where('is_active', true)->with('attributes'),
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
