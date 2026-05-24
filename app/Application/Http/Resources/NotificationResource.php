<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'image_url' => $this->image_url,
            'badge_status' => $this->badge_status ?? 'none',
            'channel' => $this->channel,
            'status' => $this->status,
            'is_read' => $this->is_read,

            'reference' => [
                'type' => $this->reference_type,
                'id' => $this->reference_id,
            ],

            'sent_at' => $this->sent_at?->toIso8601String(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
