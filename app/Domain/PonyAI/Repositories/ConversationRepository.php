<?php

namespace App\Domain\PonyAI\Repositories;

use App\Domain\PonyAI\Enums\AssistantPersona;
use App\Domain\PonyAI\Enums\PonyMessageRole;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Models\PonyMessage;
use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ConversationRepository
{
    public function startCustomer(User $user, ?string $title = null): PonyConversation
    {
        return PonyConversation::create([
            'user_id' => $user->id,
            'persona' => AssistantPersona::CUSTOMER,
            'store_id' => null,
            'title' => $title,
        ]);
    }

    public function startSeller(User $user, Store $store, ?string $title = null): PonyConversation
    {
        return PonyConversation::create([
            'user_id' => $user->id,
            'persona' => AssistantPersona::SELLER,
            'store_id' => $store->id,
            'title' => $title,
        ]);
    }

    public function findForUser(string $conversationId, User $user): ?PonyConversation
    {
        return PonyConversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->first();
    }

    public function findSellerConversationForStore(string $conversationId, User $user, Store $store): ?PonyConversation
    {
        return PonyConversation::query()
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->where('persona', AssistantPersona::SELLER)
            ->first();
    }

    public function paginateCustomer(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return PonyConversation::query()
            ->where('user_id', $user->id)
            ->where('persona', AssistantPersona::CUSTOMER)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function paginateSeller(User $user, Store $store, int $perPage = 20): LengthAwarePaginator
    {
        return PonyConversation::query()
            ->where('user_id', $user->id)
            ->where('store_id', $store->id)
            ->where('persona', AssistantPersona::SELLER)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function appendUserMessage(PonyConversation $conversation, string $content, array $attachments = []): PonyMessage
    {
        return $this->appendMessage($conversation, PonyMessageRole::USER, $content, $attachments);
    }

    public function appendAssistantMessage(PonyConversation $conversation, string $content, array $metadata = []): PonyMessage
    {
        return $this->appendMessage($conversation, PonyMessageRole::ASSISTANT, $content, [], $metadata);
    }

    public function appendMessage(
        PonyConversation $conversation,
        PonyMessageRole $role,
        string $content,
        array $attachments = [],
        array $metadata = [],
    ): PonyMessage {
        return DB::transaction(function () use ($conversation, $role, $content, $attachments, $metadata) {
            $message = $conversation->messages()->create([
                'role' => $role,
                'content' => $content,
                'attachments' => $attachments !== [] ? $attachments : null,
                'metadata' => $metadata !== [] ? $metadata : null,
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message;
        });
    }

    public function delete(PonyConversation $conversation): bool
    {
        return (bool) $conversation->delete();
    }
}
