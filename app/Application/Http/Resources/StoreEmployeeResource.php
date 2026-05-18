<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreEmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'address_id' => $this->address_id,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'user' => $this->whenLoaded('user', fn () => new UserResource($this->user)),
            'address' => $this->whenLoaded('address', fn () => $this->address ? new AddressResource($this->address) : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
