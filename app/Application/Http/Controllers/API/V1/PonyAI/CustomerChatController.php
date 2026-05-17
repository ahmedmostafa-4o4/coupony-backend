<?php

namespace App\Application\Http\Controllers\API\V1\PonyAI;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\PonyAI\ChatPromptRequest;
use App\Application\Http\Requests\PonyAI\ImageSearchRequest;
use App\Application\Http\Resources\PonyAI\AssistantReplyResource;
use App\Application\Http\Resources\PonyAI\ConversationResource;
use App\Domain\PonyAI\Exceptions\PonyAIException;
use App\Domain\PonyAI\Models\PonyConversation;
use App\Domain\PonyAI\Repositories\ConversationRepository;
use App\Domain\PonyAI\Services\CustomerAssistantStrategy;
use App\Domain\PonyAI\Services\CustomerImageSearchStrategy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CustomerChatController extends Controller
{
    public function __construct(
        private readonly CustomerAssistantStrategy $strategy,
        private readonly CustomerImageSearchStrategy $imageStrategy,
        private readonly ConversationRepository $conversations,
    ) {
    }

    public function store(ChatPromptRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $user = $request->user();

        $conversation = null;
        if ($request->conversationId() !== null) {
            $conversation = $this->conversations->findForUser($request->conversationId(), $user);

            if ($conversation === null) {
                return $this->errorResponse(__('api.pony.conversation_not_found'), 404);
            }
        }

        try {
            $reply = $this->strategy->handle($user, $request->message(), $conversation);
        } catch (PonyAIException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        } catch (Throwable) {
            return $this->errorResponse(__('api.pony.chat_failed'), 500);
        }

        return $this->successResponse(
            (new AssistantReplyResource($reply))->resolve($request),
            __('api.pony.reply_generated'),
        );
    }

    public function imageSearch(ImageSearchRequest $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $user = $request->user();

        $conversation = null;
        if ($request->conversationId() !== null) {
            $conversation = $this->conversations->findForUser($request->conversationId(), $user);

            if ($conversation === null) {
                return $this->errorResponse(__('api.pony.conversation_not_found'), 404);
            }
        }

        try {
            $reply = $this->imageStrategy->handle(
                $user,
                $request->uploadedImage(),
                $request->extraMessage(),
                $conversation,
            );
        } catch (PonyAIException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        } catch (Throwable) {
            return $this->errorResponse(__('api.pony.chat_failed'), 500);
        }

        return $this->successResponse(
            (new AssistantReplyResource($reply))->resolve($request),
            __('api.pony.reply_generated'),
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        /** @var LengthAwarePaginator $page */
        $page = $this->conversations->paginateCustomer(
            $request->user(),
            (int) ($validated['per_page'] ?? 20),
        );

        return $this->paginatedResponse(
            ConversationResource::collection($page->getCollection())->resolve($request),
            __('api.pony.conversations_retrieved'),
            $page,
        );
    }

    public function show(Request $request, PonyConversation $conversation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $owned = $this->conversations->findForUser($conversation->id, $request->user());

        if ($owned === null || $owned->persona->value !== 'customer') {
            return $this->errorResponse(__('api.pony.conversation_not_found'), 404);
        }

        $owned->load('messages');

        return $this->successResponse(
            (new ConversationResource($owned))->resolve($request),
            __('api.pony.conversation_retrieved'),
        );
    }

    public function destroy(Request $request, PonyConversation $conversation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $owned = $this->conversations->findForUser($conversation->id, $request->user());

        if ($owned === null || $owned->persona->value !== 'customer') {
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
