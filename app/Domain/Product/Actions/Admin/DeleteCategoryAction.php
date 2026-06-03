<?php

namespace App\Domain\Product\Actions\Admin;

use App\Domain\Product\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteCategoryAction
{
    public function execute(Category $category): void
    {
        if ($category->products()->exists() || $category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => __('api.categories.delete_blocked')
            ]);
        }

        $this->deleteStoredIconIfExists($category->icon_url);
        
        $category->delete();
    }

    private function deleteStoredIconIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
