<?php

namespace App\Application\Http\Resources\PonyAI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\PonyAI\Models\PonyConversation
 */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'persona' => $this->persona?->value,
            'store_id' => $this->store_id,
            'title' => $this->title,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'messages' => $this->whenLoaded(
                'messages',
                fn () => PonyMessageResource::collection($this->messages),
            ),
        ];
    }
}
