<?php

namespace App\Application\Http\Resources\PonyAI;

use App\Application\Http\Resources\PublicProductCollection;
use App\Domain\PonyAI\DTOs\SellerAssistantReply;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerAssistantReplyResource extends JsonResource
{
    public function __construct(SellerAssistantReply $reply)
    {
        parent::__construct($reply);
    }

    public function toArray(Request $request): array
    {
        /** @var SellerAssistantReply $reply */
        $reply = $this->resource;

        return [
            'conversation' => (new ConversationResource($reply->conversation))->resolve($request),
            'message' => $reply->message,
            'assistant_message' => (new PonyMessageResource($reply->assistantMessage))->resolve($request),
            'user_message' => (new PonyMessageResource($reply->userMessage))->resolve($request),
            'products' => (new PublicProductCollection($reply->groundedProducts))->resolve($request),
            'offer_ids' => array_values($reply->groundedOfferIds),
            'suggestions' => $reply->suggestions,
            'insights' => (new SellerInsightsResource($reply->snapshot))->resolve($request),
        ];
    }
}
