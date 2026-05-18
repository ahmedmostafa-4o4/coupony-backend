<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\SendInvitationRequest;
use App\Application\Http\Resources\StoreInvitationResource;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreInvitation;
use App\Domain\Store\Services\StoreInvitationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreInvitationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private StoreInvitationService $invitationService) {}

    public function store(SendInvitationRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $invitation = $this->invitationService->sendInvitation(
            $store,
            $request->user(),
            $request->validated()
        );

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.invitation.sent'),
            'data' => new StoreInvitationResource($invitation),
        ], 201);
    }

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('manageInvitations', $store);

        $filters = $request->only(['status', 'role', 'search']);
        $perPage = (int) $request->query('per_page', 15);

        $invitations = $this->invitationService->listStoreInvitations($store, $filters, $perPage);

        return $this->localizedJson([
            'success' => true,
            'data' => StoreInvitationResource::collection($invitations->items()),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ],
        ]);
    }

    public function destroy(Request $request, Store $store, StoreInvitation $invitation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('manageInvitations', $store);

        if ($invitation->store_id !== $store->id) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $this->invitationService->cancelInvitation($invitation);

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.invitation.cancelled'),
        ]);
    }

    public function resend(Request $request, Store $store, StoreInvitation $invitation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('manageInvitations', $store);

        if ($invitation->store_id !== $store->id) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.common.unauthorized'),
            ], 403);
        }

        $resentInvitation = $this->invitationService->resendInvitation($invitation);

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.invitation.resent'),
            'data' => new StoreInvitationResource($resentInvitation),
        ]);
    }

    public function accept(Request $request, StoreInvitation $invitation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $this->invitationService->acceptInvitation($request->user(), $invitation);

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.invitation.accepted'),
        ]);
    }

    public function decline(Request $request, StoreInvitation $invitation): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $this->invitationService->declineInvitation($request->user(), $invitation);

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.invitation.declined'),
        ]);
    }

    public function myInvitations(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        // Keep 'status' as default pending if not present in request.
        // We use $request->all() combined with the default if status is missing.
        $filters = $request->only(['status', 'role', 'store_id']);
        if (! $request->has('status')) {
            $filters['status'] = 'pending';
        }

        $perPage = (int) $request->query('per_page', 15);

        $invitations = $this->invitationService->listUserInvitations($request->user(), $filters, $perPage);

        return $this->localizedJson([
            'success' => true,
            'data' => StoreInvitationResource::collection($invitations->items()),
            'meta' => [
                'current_page' => $invitations->currentPage(),
                'last_page' => $invitations->lastPage(),
                'per_page' => $invitations->perPage(),
                'total' => $invitations->total(),
            ],
        ]);
    }
}
