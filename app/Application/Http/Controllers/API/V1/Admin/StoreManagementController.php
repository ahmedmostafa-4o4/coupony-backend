<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\StoreResource;
use App\Domain\Store\Actions\ApproveStore;
use App\Domain\Store\Actions\ApproveVerificationDocument;
use App\Domain\Store\Actions\RejectStore;
use App\Domain\Store\Actions\RejectVerificationDocument;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreVerification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StoreManagementController extends Controller
{
    public function __construct(private
        ApproveStore $approveStore, private
        RejectStore $rejectStore, private
        ApproveVerificationDocument $approveVerificationDocument, private
        RejectVerificationDocument $rejectVerificationDocument
        )
    {
    }

    /**
     * Get all pending store registrations
     */
    public function pending(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $stores = Store::with(['owner', 'categories', 'verifications', 'addresses'])
                ->where('status', StoreStatus::PENDING)
                ->latest()
                ->paginate($request->input('per_page', 15));

            return $this->localizedJson([
                'message' => __('api.admin.stores.pending_retrieved'),
                'data' => StoreResource::collection($stores),
                'meta' => [
                    'current_page' => $stores->currentPage(),
                    'last_page' => $stores->lastPage(),
                    'per_page' => $stores->perPage(),
                    'total' => $stores->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve pending stores', ['error' => $e->getMessage()]);
            
            return $this->localizedJson([
                'message' => __('api.admin.stores.pending_failed'),
            ], 500);
        }
    }

    /**
     * Get all stores with filters
     */
    public function index(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $query = Store::with(['owner', 'categories', 'verifications', 'addresses']);

            // Filter by status
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            // Search by name
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->input('search') . '%');
            }

            // Filter by date range
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->input('from_date'));
            }

            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->input('to_date'));
            }

            $stores = $query->latest()->paginate($request->input('per_page', 15));

            return $this->localizedJson([
                'message' => __('api.admin.stores.retrieved'),
                'data' => StoreResource::collection($stores),
                'meta' => [
                    'current_page' => $stores->currentPage(),
                    'last_page' => $stores->lastPage(),
                    'per_page' => $stores->perPage(),
                    'total' => $stores->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve stores', ['error' => $e->getMessage()]);
            
            return $this->localizedJson([
                'message' => __('api.admin.stores.retrieve_failed'),
            ], 500);
        }
    }

    /**
     * Get single store details
     */
    public function show(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $store->load(['owner.profile', 'categories', 'verifications', 'addresses', 'hours']);

            return $this->localizedJson([
                'message' => __('api.admin.stores.details_retrieved'),
                'data' => new StoreResource($store),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store details', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->localizedJson([
                'message' => __('api.admin.stores.details_failed'),
            ], 500);
        }
    }

    /**
     * Approve store registration
     */
    public function approve(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if store is already approved
        if ($store->status === StoreStatus::ACTIVE) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.already_approved'),
            ], 400);
        }

        // Check if store is not pending
        if ($store->status !== StoreStatus::PENDING) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.only_pending_approve'),
            ], 400);
        }

        try {
            $this->approveStore->execute($store, $request->user(), $validated['notes'] ?? null);

            return $this->localizedJson([
                'message' => __('api.admin.stores.approved'),
                'data' => new StoreResource($store->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Store approval failed', [
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.approve_failed'),
            ], 500);
        }
    }

    /**
     * Reject store registration
     */
    public function reject(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Check if store is already rejected
        if ($store->status === StoreStatus::REJECTED) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.already_rejected'),
            ], 400);
        }

        // Check if store is not pending
        if ($store->status !== StoreStatus::PENDING) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.only_pending_reject'),
            ], 400);
        }

        try {
            $this->rejectStore->execute($store, $request->user(), $validated['reason']);

            return $this->localizedJson([
                'message' => __('api.admin.stores.rejected'),
                'data' => new StoreResource($store->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Store rejection failed', [
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.reject_failed'),
            ], 500);
        }
    }

    /**
     * Get store statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $stats = [
                'total' => Store::count(),
                'pending' => Store::where('status', StoreStatus::PENDING)->count(),
                'active' => Store::where('status', StoreStatus::ACTIVE)->count(),
                'rejected' => Store::where('status', StoreStatus::REJECTED)->count(),
                'suspended' => Store::where('status', StoreStatus::SUSPENDED)->count(),
                'recent_pending' => Store::where('status', StoreStatus::PENDING)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ];

            return $this->localizedJson([
                'message' => __('api.admin.stores.statistics_retrieved'),
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store statistics', ['error' => $e->getMessage()]);
            
            return $this->localizedJson([
                'message' => __('api.admin.stores.statistics_failed'),
            ], 500);
        }
    }

    /**
     * Get store verification documents
     */
    public function verificationDocuments(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $verifications = $store->verifications()->get();

            return $this->localizedJson([
                'message' => __('api.admin.stores.documents_retrieved'),
                'data' => $verifications,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve verification documents', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->localizedJson([
                'message' => __('api.admin.stores.documents_failed'),
            ], 500);
        }
    }

    /**
     * Approve a verification document
     */
    public function approveDocument(Request $request, Store $store, StoreVerification $verification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if verification belongs to store
        if ($verification->store_id !== $store->id) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.document_wrong_store'),
            ], 400);
        }

        // Check if already approved
        if ($verification->status === 'approved') {
            return $this->localizedJson([
                'message' => __('api.admin.stores.document_already_approved'),
            ], 400);
        }

        try {
            $this->approveVerificationDocument->execute(
                $verification,
                $request->user(),
                $validated['notes'] ?? null
            );

            return $this->localizedJson([
                'message' => __('api.admin.stores.document_approved'),
                'data' => $verification->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Document approval failed', [
                'verification_id' => $verification->id,
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.document_approve_failed'),
            ], 500);
        }
    }

    /**
     * Reject a verification document
     */
    public function rejectDocument(Request $request, Store $store, StoreVerification $verification): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Check if verification belongs to store
        if ($verification->store_id !== $store->id) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.document_wrong_store'),
            ], 400);
        }

        // Check if already rejected
        if ($verification->status === 'rejected') {
            return $this->localizedJson([
                'message' => __('api.admin.stores.document_already_rejected'),
            ], 400);
        }

        try {
            $this->rejectVerificationDocument->execute(
                $verification,
                $request->user(),
                $validated['reason']
            );

            return $this->localizedJson([
                'message' => __('api.admin.stores.document_rejected'),
                'data' => $verification->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Document rejection failed', [
                'verification_id' => $verification->id,
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.document_reject_failed'),
            ], 500);
        }
    }
}
