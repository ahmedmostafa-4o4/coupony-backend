<?php

namespace App\Domain\Store\Actions\Admin;

use App\Domain\Store\Models\StoreCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteStoreCategoryAction
{
    public function execute(StoreCategory $category): void
    {
        if ($category->stores()->exists()) {
            throw ValidationException::withMessages([
                'category' => __('api.store_categories.delete_blocked')
            ]);
        }

        $this->deleteStoredIconIfExists($category->icon_url);
        $this->deleteStoredIconIfExists($category->image_category);
        
        $category->delete();
    }

    private function deleteStoredIconIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
