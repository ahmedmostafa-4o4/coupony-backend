<?php

namespace App\Application\Http\Controllers\API\V1\PonyAI;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\PonyAI\SellerChatPromptRequest;
use App\Application\Http\Resources\PonyAI\ConversationResource;
use App\Application\Http\Resources\PonyAI\SellerAssistantReplyResource;
use App\Domain\PonyAI\DTOs\AiQuotaReservation;
use App\Domain\PonyAI\Exceptions\AiDailyLimitReachedException;
use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\AiMessageQuotaService;
use App\Domain\PonyAI\Services\SellerAssistantStrategy;
use App\Domain\Store\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

class SellerChatController extends Controller
{
    public function __construct(
        private readonly SellerAssistantStrategy $strategy,
        private readonly ConversationRepository $conversations,
        private readonly AiMessageQuotaService $quotas,
    ) {}

    public function store(SellerChatPromptRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $user = $request->user();

        if (! Gate::forUser($user)->allows('pony-ai-seller-chat', $store)) {
            return $this->errorResponse(__('api.common.unauthorized'), 403);
        }

        $conversation = null;
        if ($request->conversationId() !== null) {
            $conversation = $this->conversations->findSellerConversationForStore(
                $request->conversationId(),
                $user,
                $store,
            );

            if ($conversation === null) {
                return $this->errorResponse(__('api.pony.conversation_not_found'), 404);
            }
        }

        $reservation = null;

        try {
            $reservation = $this->quotas->reserveStore($store);
            $reply = $this->strategy->handle($user, $store, $request->message(), $conversation);
        } catch (AiDailyLimitReachedException $exception) {
            return $this->dailyLimitResponse($exception->quota);
        } catch (PonyAIException $exception) {
            $this->releaseReservation($reservation);

            return $this->errorResponse($exception->getMessage(), 422);
        } catch (Throwable) {
            $this->releaseReservation($reservation);

            return $this->errorResponse(__('api.pony.chat_failed'), 500);
        }

        return $this->successResponse(
            $this->withQuota((new SellerAssistantReplyResource($reply))->resolve($request), $reservation),
            __('api.pony.reply_generated'),
        );
    }

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $user = $request->user();

        if (! Gate::forUser($user)->allows('pony-ai-seller-chat', $store)) {
            return $this->errorResponse(__('api.common.unauthorized'), 403);
        }

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        /** @var LengthAwarePaginator $page */
        $page = $this->conversations->paginateSeller(
            $user,
            $store,
            (int) ($validated['per_page'] ?? 20),
        );

        return $this->paginatedResponse(
            ConversationResource::collection($page->getCollection())->resolve($request),
            __('api.pony.conversations_retrieved'),
            $page,
        );
    }

    public function show(Request $request, Store $store, PonyConversation $conversation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $user = $request->user();

        if (! Gate::forUser($user)->allows('pony-ai-seller-chat', $store)) {
            return $this->errorResponse(__('api.common.unauthorized'), 403);
        }

        $owned = $this->conversations->findSellerConversationForStore(
            $conversation->id,
            $user,
            $store,
        );

        if ($owned === null) {
            return $this->errorResponse(__('api.pony.conversation_not_found'), 404);
        }

        $owned->load('messages');

        return $this->successResponse(
            (new ConversationResource($owned))->resolve($request),
            __('api.pony.conversation_retrieved'),
        );
    }

    public function destroy(Request $request, Store $store, PonyConversation $conversation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $user = $request->user();

        if (! Gate::forUser($user)->allows('pony-ai-seller-chat', $store)) {
            return $this->errorResponse(__('api.common.unauthorized'), 403);
        }

        $owned = $this->conversations->findSellerConversationForStore(
            $conversation->id,
            $user,
            $store,
        );

        if ($owned === null) {
            return $this->errorResponse(__('api.pony.conversation_not_found'), 404);
        }

        $this->conversations->delete($owned);

        return $this->successResponse(null, __('api.pony.conversation_deleted'));
    }

    private function successResponse(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function errorResponse(string $message, int $status = 400): JsonResponse
    {
        return $this->localizedJson([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * @param  array{limit: ?int, used: int, remaining: ?int, resets_at: ?string}  $quota
     */
    private function dailyLimitResponse(array $quota): JsonResponse
    {
        return $this->localizedJson([
            'success' => false,
            'message' => __('api.pony.daily_limit_reached'),
            'error_code' => 'AI_DAILY_LIMIT_REACHED',
            'quota' => $quota,
        ], 429);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withQuota(array $data, AiQuotaReservation $reservation): array
    {
        $data['quota'] = $reservation->quota;

        return $data;
    }

    private function releaseReservation(?AiQuotaReservation $reservation): void
    {
        if ($reservation !== null) {
            $this->quotas->release($reservation);
        }
    }

    private function paginatedResponse(mixed $data, string $message, LengthAwarePaginator $paginator): JsonResponse
    {
        return $this->localizedJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
