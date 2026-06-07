<?php

namespace App\Domain\Banner\Actions;

use App\Domain\Banner\Models\Banner;
use Illuminate\Support\Facades\Storage;

class DeleteBanner
{
    public function execute(Banner $banner): bool
    {
        // Delete banner image if it's stored locally
        if ($banner->image_url && !str_starts_with($banner->image_url, 'http')) {
             if (Storage::disk('public')->exists($banner->image_url)) {
                 Storage::disk('public')->delete($banner->image_url);
             }
        }

        return $banner->delete();
    }
}
