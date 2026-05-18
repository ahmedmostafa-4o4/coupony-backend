<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\SendInvitationRequest;
use App\Application\Http\Resources\StoreEmployeeResource;
use App\Application\Http\Resources\StoreInvitationResource;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\Store\Models\StoreInvitation;
use App\Domain\Store\Services\StoreInvitationService;
use App\Domain\User\Models\User;
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

    public function employees(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('manageEmployees', $store);

        $query = StoreEmployee::with('user.profile')
            ->where('store_id', $store->id);

        if ($request->has('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->has('address_id')) {
            $query->where('address_id', $request->query('address_id'));
        }

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('email', 'like', '%'.$search.'%')
                    ->orWhereHas('profile', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%');
                    });
            });
        }

        $employees = $query->paginate((int) $request->query('per_page', 15));

        return $this->localizedJson([
            'success' => true,
            'data' => StoreEmployeeResource::collection($employees->items()),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    public function removeEmployee(Request $request, Store $store, User $user): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);
        $this->authorize('manageEmployees', $store);

        if ($store->owner_user_id === $user->id) {
            return $this->localizedJson([
                'success' => false,
                'message' => __('api.invitation.cannot_remove_owner'),
            ], 422);
        }

        StoreEmployee::where('store_id', $store->id)->where('user_id', $user->id)->delete();

        return $this->localizedJson([
            'success' => true,
            'message' => __('api.invitation.employee_removed'),
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
