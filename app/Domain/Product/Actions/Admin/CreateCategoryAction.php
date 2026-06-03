<?php

namespace App\Domain\Product\Actions\Admin;

use App\Domain\Product\DTOs\Admin\CategoryDTO;
use App\Domain\Product\Models\Category;
use Illuminate\Support\Str;

class CreateCategoryAction
{
    public function execute(CategoryDTO $dto): Category
    {
        $category = Category::create([
            'name' => $dto->nameEn,
            'name_ar' => $dto->nameAr,
            'name_en' => $dto->nameEn,
            'slug' => $dto->slug ?? Str::slug($dto->nameEn),
            'description' => $dto->description,
            'parent_id' => $dto->parentId,
            'sort_order' => $dto->sortOrder ?? 0,
            'is_active' => $dto->isActive ?? true,
        ]);

        if ($dto->icon) {
            $category->update([
                'icon_url' => $dto->icon->store("categories/{$category->id}/icon", 'public'),
            ]);
        }

        return $category->fresh();
    }
}
