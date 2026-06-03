<?php

namespace App\Domain\Store\Actions\Admin;

use App\Domain\Store\DTOs\Admin\StoreCategoryDTO;
use App\Domain\Store\Models\StoreCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateStoreCategoryAction
{
    public function execute(StoreCategory $category, StoreCategoryDTO $dto): StoreCategory
    {
        $dataToUpdate = [];

        if ($dto->has('name_en')) {
            $dataToUpdate['name_en'] = $dto->nameEn;
        }

        if ($dto->has('name_ar')) {
            $dataToUpdate['name_ar'] = $dto->nameAr;
        }

        if ($dto->has('sort_order')) {
            $dataToUpdate['sort_order'] = $dto->sortOrder;
        }

        if ($dto->has('is_active')) {
            $dataToUpdate['is_active'] = $dto->isActive;
        }

        if ($dto->has('slug')) {
            $dataToUpdate['slug'] = $dto->slug;
        } elseif (!isset($dataToUpdate['slug']) && blank($category->slug)) {
            $slugSource = $dataToUpdate['name_en'] ?? $dataToUpdate['name_ar'] ?? null;
            if (filled($slugSource)) {
                $dataToUpdate['slug'] = Str::slug($slugSource);
            }
        }

        if (!empty($dataToUpdate)) {
            $category->update($dataToUpdate);
        }

        if ($dto->has('icon') && $dto->icon) {
            $oldIconPath = $category->icon_url;
            $newIconPath = $dto->icon->store("store-categories/{$category->id}/icon", 'public');
            $category->update(['icon_url' => $newIconPath]);
            $this->deleteStoredIconIfExists($oldIconPath);
        }

        if ($dto->has('image_category') && $dto->imageCategory) {
            $oldImageCategoryPath = $category->image_category;
            $newImageCategoryPath = $dto->imageCategory->store("store-categories/{$category->id}/image", 'public');
            $category->update(['image_category' => $newImageCategoryPath]);
            $this->deleteStoredIconIfExists($oldImageCategoryPath);
        }

        return $category->fresh();
    }

    private function deleteStoredIconIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
