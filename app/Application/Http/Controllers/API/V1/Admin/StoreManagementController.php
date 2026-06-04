<?php

namespace App\Application\Http\Controllers\API\V1\Admin;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Resources\StoreResource;
use App\Domain\Store\Actions\ApproveStore;
use App\Domain\Store\Actions\ApproveVerificationDocument;
use App\Domain\Store\Actions\CloseStore;
use App\Domain\Store\Actions\RejectStore;
use App\Domain\Store\Actions\RejectVerificationDocument;
use App\Domain\Store\Actions\SuspendStore;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Enums\VerificationDocumentType;
use App\Domain\Store\Enums\VerificationStatus;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\Store\Models\StoreVerification;
use App\Application\Http\Requests\Admin\StoreManagementUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreManagementController extends Controller
{
    public function __construct(private ApproveStore $approveStore, private RejectStore $rejectStore, private SuspendStore $suspendStore, private CloseStore $closeStore, private ApproveVerificationDocument $approveVerificationDocument, private RejectVerificationDocument $rejectVerificationDocument
    ) {}

    /**
     * Update store information
     */
    public function update(StoreManagementUpdateRequest $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $store->update(array_filter([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'tax_id' => $request->input('tax_id'),
                'subscription_tier' => $request->input('subscription_tier'),
                'commission_rate' => $request->input('commission_rate'),
            ], fn($value) => !is_null($value)));

            if ($request->has('description') && is_null($request->input('description'))) {
                $store->update(['description' => null]);
            }
            if ($request->has('tax_id') && is_null($request->input('tax_id'))) {
                $store->update(['tax_id' => null]);
            }
            if ($request->has('subscription_tier') && is_null($request->input('subscription_tier'))) {
                $store->update(['subscription_tier' => null]);
            }
            if ($request->has('commission_rate') && is_null($request->input('commission_rate'))) {
                $store->update(['commission_rate' => null]);
            }

            $store->load(['owner.profile', 'categories', 'verifications', 'addresses', 'hours']);

            return $this->localizedJson([
                'message' => __('api.admin.stores.updated', ['default' => 'Store updated successfully.']),
                'data' => new StoreResource($store),
            ]);
        } catch (\Exception $e) {
            Log::error('Store update failed', [
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.update_failed', ['default' => 'Failed to update store.']),
            ], 500);
        }
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
     * Get all suspended stores
     */
    public function suspended(Request $request): JsonResponse
    {
        return $this->listByStatus(
            $request,
            StoreStatus::SUSPENDED,
            'Failed to retrieve suspended stores'
        );
    }

    /**
     * Get all closed stores
     */
    public function closed(Request $request): JsonResponse
    {
        return $this->listByStatus(
            $request,
            StoreStatus::CLOSED,
            'Failed to retrieve closed stores'
        );
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
                $query->where('name', 'like', '%'.$request->input('search').'%');
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
            $store->load(['owner.profile', 'categories', 'verifications', 'addresses', 'hours', 'points']);

            return $this->localizedJson([
                'message' => __('api.admin.stores.details_retrieved'),
                'data' => new StoreResource($store),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store details', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.details_failed'),
            ], 500);
        }
    }

    /**
     * Get store reviews (comments)
     */
    public function reviews(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $reviews = $store->comments()
                ->with(['user.profile'])
                ->latest()
                ->paginate($request->input('per_page', 15));

            return $this->localizedJson([
                'message' => __('api.admin.stores.reviews_retrieved', ['default' => 'Store reviews retrieved successfully.']),
                'data' => $reviews->items(),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store reviews', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.reviews_failed', ['default' => 'Failed to retrieve store reviews.']),
            ], 500);
        }
    }

    /**
     * Get store billing profile (subscription)
     */
    public function billing(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $subscription = $store->subscription()->with(['plan'])->first();

            return $this->localizedJson([
                'message' => __('api.admin.stores.billing_retrieved', ['default' => 'Store billing profile retrieved successfully.']),
                'data' => $subscription,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store billing profile', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.billing_failed', ['default' => 'Failed to retrieve store billing profile.']),
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
            $approvedStore = $this->approveStore->execute($store, $request->user(), $validated['notes'] ?? null);

            return $this->localizedJson([
                'message' => __('api.admin.stores.approved'),
                'data' => new StoreResource($approvedStore),
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
            $rejectedStore = $this->rejectStore->execute($store, $request->user(), $validated['reason']);

            return $this->localizedJson([
                'message' => __('api.admin.stores.rejected'),
                'data' => new StoreResource($rejectedStore),
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
     * Suspend store
     */
    public function suspend(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($store->status === StoreStatus::SUSPENDED) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.already_suspended'),
            ], 400);
        }

        try {
            $this->suspendStore->execute($store, $request->user(), $validated['reason'] ?? null);

            return $this->localizedJson([
                'message' => __('api.admin.stores.suspended'),
                'data' => new StoreResource($store->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Store suspend failed', [
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.suspend_failed'),
            ], 500);
        }
    }

    /**
     * Close store
     */
    public function close(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if ($store->status === StoreStatus::CLOSED) {
            return $this->localizedJson([
                'message' => __('api.admin.stores.already_closed'),
            ], 400);
        }

        try {
            $this->closeStore->execute($store, $request->user(), $validated['reason'] ?? null);

            return $this->localizedJson([
                'message' => __('api.admin.stores.closed'),
                'data' => new StoreResource($store->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Store close failed', [
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.close_failed'),
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
                'closed' => Store::where('status', StoreStatus::CLOSED)->count(),
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
     * Upload a new verification document for a store (Admin action).
     */
    public function uploadVerificationDocument(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $request->validate([
            'document_type' => ['required', Rule::enum(VerificationDocumentType::class)],
            'document' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240', // Max 10MB
        ]);

        try {
            $documentType = $request->input('document_type');
            $file = $request->file('document');

            $docPath = $file->store("stores/{$store->id}/verifications/{$documentType}", 'public');

            $verification = $store->verifications()->updateOrCreate(
                ['document_type' => $documentType],
                [
                    'document_path' => $docPath,
                    'status' => VerificationStatus::PENDING->value,
                ]
            );

            return $this->localizedJson([
                'message' => __('api.admin.stores.document_uploaded', ['default' => 'Verification document uploaded successfully.']),
                'data' => $verification,
            ]);
        } catch (\Throwable $e) {
            Log::error('Admin verification document upload failed', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.document_upload_failed', ['default' => 'Failed to upload verification document.']),
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
                'error' => $e->getMessage(),
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

    public function attachCategory(Request $request, Store $store): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $request->validate([
            'category_id' => 'required|exists:store_categories,id',
        ]);

        $store->categories()->syncWithoutDetaching([$request->input('category_id')]);

        $store->load(['owner.profile', 'categories', 'verifications', 'addresses', 'hours']);

        return $this->localizedJson([
            'message' => __('api.admin.stores.category_attached', ['default' => 'Category attached successfully.']),
            'data' => new StoreResource($store),
        ]);
    }

    public function detachCategory(Request $request, Store $store, StoreCategory $category): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        $store->categories()->detach($category->id);

        $store->load(['owner.profile', 'categories', 'verifications', 'addresses', 'hours']);

        return $this->localizedJson([
            'message' => __('api.admin.stores.category_detached', ['default' => 'Category detached successfully.']),
            'data' => new StoreResource($store),
        ]);
    }

    private function listByStatus(Request $request, StoreStatus $status, string $logMessage): JsonResponse
    {
        $this->applyAuthenticatedLocale($request);

        try {
            $stores = Store::with(['owner', 'categories', 'verifications', 'addresses'])
                ->where('status', $status)
                ->latest()
                ->paginate($request->input('per_page', 15));

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
            Log::error($logMessage, ['error' => $e->getMessage()]);

            return $this->localizedJson([
                'message' => __('api.admin.stores.retrieve_failed'),
            ], 500);
        }
    }
}
