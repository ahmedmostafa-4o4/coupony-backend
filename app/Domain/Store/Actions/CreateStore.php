<?php

namespace App\Domain\Store\Actions;

use App\Application\Http\Resources\StoreResource;
use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Events\StoreCreated;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Repositories\StoreRepository;
use App\Domain\User\Models\User;
use DB;
use Log;

class CreateStore
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private StoreRepository $stores,
    ) {

    }

    public function execute(User $owner, StoreData $data): StoreResource
    {
        return DB::transaction(function () use ($data, $owner) {
            $store = $this->stores->create([
                'owner_user_id' => $data->ownerUserId,
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'status' => StoreStatus::PENDING
            ]);

            $docs = [
                'commercial_register' => $data->commercial_register,
                'tax_card' => $data->tax_card,
                'id_card_front' => $data->id_card_front,
                'id_card_back' => $data->id_card_back,
            ];

            $store->categories()->sync($data->categories);

            $address = $store->addresses()->create([
                'address_line1' => $data->address_line1,
                'city' => $data->city,
                'address_line2' => $data->address_line2,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
            ]);

            $store->addresses()->syncWithoutDetaching([
                $address->id => ['label' => 'branch']
            ]);

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
            if (!$owner->hasRole('seller')) {
                $owner->assignRole('seller');
            }

            $store->userRoles()->create([
                'user_id' => $data->ownerUserId,
                'role_id' => $owner->roles()->where('name', 'seller')->first()->id,
                'role' => 'seller',
                'store_id' => $store->id,
            ]);

            $this->createDefaultStoreHours($store);

            event(new StoreCreated($store));
            Log::info('StoreAddressResource');

            // 🔹 reload relationships before returning
            $store->load(['addresses', 'categories', 'verifications']);

            return new StoreResource($store);
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
