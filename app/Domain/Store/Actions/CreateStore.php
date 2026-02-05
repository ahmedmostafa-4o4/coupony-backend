<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Events\StoreCreated;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Repositories\StoreRepository;
use App\Domain\User\Models\User;
use DB;

class CreateStore
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private StoreRepository $stores,
    ) {

    }

    public function execute(User $owner, StoreData $data): Store
    {
        return DB::transaction(function () use ($data) {
            $store = $this->stores->create([
                'owner_user_id' => $data->ownerUserId,
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'banner_url' => $data->banner_url,
                'logo_url' => $data->logo_url,
                'description' => $data->description,
                'status' => StoreStatus::PENDING
            ]);

            $docs = [
                'commercial_register' => $data->commercial_register,
                'tax_card' => $data->tax_card,
                'id_card' => $data->id_card,
            ];

            foreach ($docs as $type => $path) {
                if ($path) {
                    $store->verifications()->create([
                        'store_id' => $store->id,
                        'document_type' => $type,
                        'document_path' => $path,
                        'status' => 'pending',
                    ]);
                }
            }



            // $store->userRoles()->create([
            //     'user_id' => $data->ownerUserId,
            //     'role' => 'seller',
            //     'store_id' => $store->id,
            // ]);

            $this->createDefaultStoreHours($store);

            event(new StoreCreated($store));
            return $store;
        });
    }
    private function createDefaultStoreHours(Store $store): void
    {
        $defaultHours = [
            ['day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => 2, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => 3, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => 4, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => 5, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => false],
            ['day_of_week' => 6, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => true],
            ['day_of_week' => 0, 'open_time' => '09:00', 'close_time' => '17:00', 'is_closed' => true],
        ];

        $store->hours()->createMany($defaultHours);
    }

}
