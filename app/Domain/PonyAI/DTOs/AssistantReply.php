<?php

namespace App\Domain\PonyAI\DTOs;

use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Models\PonyMessage;
use Illuminate\Support\Collection;

final class AssistantReply
{
    /**
     * @param  Collection<int, \App\Domain\Product\Models\Product>  $groundedProducts
     * @param  array<int, string>  $groundedOfferIds
     * @param  array<int, string>  $droppedProductIds  IDs the model produced that we filtered out
     */
    public function __construct(
        public readonly PonyConversation $conversation,
        public readonly PonyMessage $userMessage,
        public readonly PonyMessage $assistantMessage,
        public readonly string $message,
        public readonly Collection $groundedProducts,
        public readonly array $groundedOfferIds = [],
        public readonly array $droppedProductIds = [],
    ) {}
}
