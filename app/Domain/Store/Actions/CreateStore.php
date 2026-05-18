<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Events\StoreCreated;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Repositories\StoreRepository;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserRoles;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateStore
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private StoreRepository $stores,
    ) {}

    public function execute(User $owner, StoreData $data): Store
    {
        return DB::transaction(function () use ($data, $owner) {
            $store = $this->stores->create([
                'owner_user_id' => $owner->id,
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'description' => $data->description,
                'status' => StoreStatus::PENDING,
            ]);

            $store->categories()->sync($data->categories);

            $store->addBranchAddress([
                'address_line1' => $data->address_line1,
                'city' => $data->city,
                'address_line2' => $data->address_line2,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
            ]);

            if (! empty($data->socials)) {
                $store->socials()->createMany(
                    collect($data->socials)
                        ->map(fn (array $social) => [
                            'social_id' => $social['social_id'],
                            'link' => $social['link'],
                        ])
                        ->all()
                );
            }

            $docs = [
                'commercial_register' => $data->commercial_register,
                'tax_card' => $data->tax_card,
                'id_card_front' => $data->id_card_front,
                'id_card_back' => $data->id_card_back,
            ];

            foreach ($docs as $type => $path) {
                if (! empty($path)) {
                    $store->verifications()->create([
                        'document_type' => $type,
                        'document_path' => $path,
                        'status' => 'pending',
                    ]);
                }
            }

            $sellerPendingRoleId = Role::where('name', 'seller_pending')->value('id');
            $sellerRoleId = Role::where('name', 'seller')->value('id');

            if ($sellerPendingRoleId === null || $sellerRoleId === null) {
                throw new \RuntimeException('Seller roles are not configured.');
            }

            if (! $owner->hasRole('seller_pending') && ! $owner->hasRole('seller')) {
                $owner->assignRole('seller_pending');
                UserRoles::firstOrCreate([
                    'user_id' => $owner->id,
                    'role_id' => $sellerPendingRoleId,
                    'store_id' => null,
                ], [
                    'granted_at' => now(),
                ]);
            }

            $store->userRoles()->firstOrCreate([
                'user_id' => $owner->id,
                'role_id' => $sellerRoleId,
                'store_id' => $store->id,
            ], [
                'granted_at' => now(),
            ]);

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
