<?php

namespace App\Domain\PonyAI\DTOs;

use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Models\PonyMessage;
use Illuminate\Support\Collection;

final class SellerAssistantReply
{
    /**
     * @param  Collection<int, \App\Domain\Product\Models\Product>  $groundedProducts
     * @param  array<int, string>  $groundedOfferIds
     * @param  array<int, string>  $droppedProductIds
     * @param  array<int, array{topic: string, text: string, product_ids: array<int, string>}>  $suggestions
     */
    public function __construct(
        public readonly PonyConversation $conversation,
        public readonly PonyMessage $userMessage,
        public readonly PonyMessage $assistantMessage,
        public readonly string $message,
        public readonly Collection $groundedProducts,
        public readonly array $groundedOfferIds,
        public readonly array $droppedProductIds,
        public readonly array $suggestions,
        public readonly StoreInsightsSnapshot $snapshot,
    ) {}
}
