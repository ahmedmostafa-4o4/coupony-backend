<?php

namespace App\Domain\Product\Actions\Admin;

use App\Domain\Product\DTOs\Admin\CategoryDTO;
use App\Domain\Product\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateCategoryAction
{
    public function execute(Category $category, CategoryDTO $dto): Category
    {
        $dataToUpdate = [];

        if ($dto->has('name_en')) {
            $dataToUpdate['name_en'] = $dto->nameEn;
            $dataToUpdate['name'] = $dto->nameEn;
        }

        if ($dto->has('name_ar')) {
            $dataToUpdate['name_ar'] = $dto->nameAr;
            if (!isset($dataToUpdate['name']) && blank($category->name_en)) {
                $dataToUpdate['name'] = $dto->nameAr;
            }
        }

        if ($dto->has('description')) {
            $dataToUpdate['description'] = $dto->description;
        }

        if ($dto->has('parent_id')) {
            $dataToUpdate['parent_id'] = $dto->parentId;
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
            $newIconPath = $dto->icon->store("categories/{$category->id}/icon", 'public');
            $category->update(['icon_url' => $newIconPath]);
            $this->deleteStoredIconIfExists($oldIconPath);
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
