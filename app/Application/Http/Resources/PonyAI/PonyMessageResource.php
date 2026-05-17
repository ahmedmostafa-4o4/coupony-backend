<?php

namespace App\Application\Http\Resources\PonyAI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/**
 * @mixin \App\Domain\PonyAI\Models\PonyMessage
 */
class PonyMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role?->value,
            'content' => $this->content,
            'attachments' => $this->safeAttachments(),
            'metadata' => $this->safeMetadata(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Never echo the raw storage path of an uploaded image. Instead, mint a
     * Laravel temporary signed URL pointing at the pony.customer.images.show
     * route - it expires after pony.image_url_ttl_minutes (default 30 min) and
     * cannot be tampered with.
     *
     * @return array<string, mixed>|null
     */
    private function safeAttachments(): ?array
    {
        $attachments = $this->attachments;

        if (! is_array($attachments) || $attachments === []) {
            return null;
        }

        $sanitized = $attachments;

        if (array_key_exists('image', $sanitized)) {
            $sanitized['has_image'] = true;
            $sanitized['image_url'] = $this->buildSignedImageUrl();
            unset($sanitized['image']);
        }

        return $sanitized === [] ? null : $sanitized;
    }

    private function buildSignedImageUrl(): string
    {
        $ttl = max(1, (int) config('pony.image_url_ttl_minutes', 30));

        return URL::temporarySignedRoute(
            'pony.customer.images.show',
            now()->addMinutes($ttl),
            ['message' => $this->id],
        );
    }

    /**
     * Strip metadata keys that are useful for debugging but shouldn't leak to the client
     * (e.g. dropped_product_ids reveals model behavior). Keep grounded ids only.
     *
     * @return array<string, mixed>|null
     */
    private function safeMetadata(): ?array
    {
        $metadata = $this->metadata;

        if (! is_array($metadata) || $metadata === []) {
            return null;
        }

        return [
            'product_ids' => array_values((array) ($metadata['product_ids'] ?? [])),
            'offer_ids' => array_values((array) ($metadata['offer_ids'] ?? [])),
        ];
    }
}
