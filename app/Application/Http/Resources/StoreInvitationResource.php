<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'address_id' => $this->address_id,
            'role' => $this->role,
            'permissions' => $this->permissions,
            'status' => $this->status,
            'message' => $this->message,
            'expires_at' => $this->expires_at,
            'accepted_at' => $this->accepted_at,
            'declined_at' => $this->declined_at,
            'created_at' => $this->created_at,
            'store' => new StoreResource($this->whenLoaded('store')),
            'address' => new AddressResource($this->whenLoaded('address')),
            'invited_by' => new UserResource($this->whenLoaded('invitedBy')),
            'invitee' => new UserResource($this->whenLoaded('invitee')),
        ];
    }
}
