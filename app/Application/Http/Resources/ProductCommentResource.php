<?php

namespace App\Application\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCommentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user() ?? $request->attributes->get('resolved_user');

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'parent_id' => $this->parent_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user?->id,
                    'full_name' => $this->user?->full_name,
                    'avatar_url' => $this->user?->avatar,
                ];
            }),
            'rating' => $this->rating,
            'body' => $this->body,
            'status' => $this->status,
            'likes_count' => (int) ($this->likes_count ?? 0),
            'is_liked' => $user
                ? $this->likes()->where('user_id', $user->id)->exists()
                : false,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'replies' => ProductCommentResource::collection($this->whenLoaded('visibleReplies')),
        ];
    }
}
