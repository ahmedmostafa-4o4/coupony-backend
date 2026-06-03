<?php

namespace App\Domain\Store\Actions\Admin;

use App\Domain\Store\DTOs\Admin\StoreCategoryDTO;
use App\Domain\Store\Models\StoreCategory;
use Illuminate\Support\Str;

class CreateStoreCategoryAction
{
    public function execute(StoreCategoryDTO $dto): StoreCategory
    {
        $category = StoreCategory::create([
            'name_ar' => $dto->nameAr,
            'name_en' => $dto->nameEn,
            'slug' => $dto->slug ?? Str::slug($dto->nameEn ?? $dto->nameAr),
            'sort_order' => $dto->sortOrder ?? 0,
            'is_active' => $dto->isActive ?? true,
        ]);

        if ($dto->icon) {
            $category->update([
                'icon_url' => $dto->icon->store("store-categories/{$category->id}/icon", 'public'),
            ]);
        }

        if ($dto->imageCategory) {
            $category->update([
                'image_category' => $dto->imageCategory->store("store-categories/{$category->id}/image", 'public'),
            ]);
        }

        return $category->fresh();
    }
}
