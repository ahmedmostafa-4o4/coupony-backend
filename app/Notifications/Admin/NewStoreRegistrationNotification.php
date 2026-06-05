<?php

namespace App\Notifications\Admin;

use App\Domain\Store\Models\Store;

class NewStoreRegistrationNotification extends AdminNotification
{
    public function __construct(public Store $store) {}

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'New Store Registration',
            'message' => "Store '{$this->store->name}' has just registered and requires approval.",
            'reference_type' => Store::class,
            'reference_id' => $this->store->id,
            'data' => [
                'store_id' => $this->store->id,
                'store_name' => $this->store->name,
                'owner_id' => $this->store->owner_user_id,
            ]
        ];
    }
}
