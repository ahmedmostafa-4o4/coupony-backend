<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateStoreProfile
{
    public function execute(
        Store $store,
        array $data,
        ?UploadedFile $logoFile = null,
        ?UploadedFile $bannerFile = null
    ): Store {
        $store->fill(
            collect($data)->only(['description', 'email', 'phone'])->all()
        );

        if ($store->isDirty()) {
            $store->save();
        }

        if (array_key_exists('socials', $data)) {
            $this->syncSocials($store, $data['socials'] ?? []);
        }

        if (array_key_exists('hours', $data)) {
            $this->syncHours($store, $data['hours'] ?? []);
        }

        if ($logoFile) {
            $this->replaceFile($store, $logoFile, 'logo_url', 'logo');
        }

        if ($bannerFile) {
            $this->replaceFile($store, $bannerFile, 'banner_url', 'banner');
        }

        return $store->fresh([
            'owner',
            'categories',
            'addresses',
            'verifications',
            'hours',
            'socials.social',
        ]);
    }

    private function syncSocials(Store $store, array $socials): void
    {
        $incomingSocialIds = collect($socials)
            ->pluck('social_id')
            ->all();

        $store->socials()
            ->whereNotIn('social_id', $incomingSocialIds)
            ->delete();

        foreach ($socials as $social) {
            $store->socials()->updateOrCreate(
                ['social_id' => $social['social_id']],
                ['link' => $social['link']]
            );
        }
    }

    private function replaceFile(Store $store, UploadedFile $file, string $attribute, string $folder): void
    {
        $oldPath = $store->{$attribute};
        $newPath = $file->store("stores/{$store->id}/{$folder}", 'public');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $store->forceFill([$attribute => $newPath])->save();
    }

    private function syncHours(Store $store, array $hours): void
    {
        foreach ($hours as $hour) {
            $isClosed = (bool) $hour['is_closed'];

            $store->hours()->updateOrCreate(
                ['day_of_week' => $hour['day_of_week']],
                [
                    'open_time' => $isClosed ? '00:00' : $hour['open_time'],
                    'close_time' => $isClosed ? '00:00' : $hour['close_time'],
                    'is_closed' => $isClosed,
                ]
            );
        }
    }
}
