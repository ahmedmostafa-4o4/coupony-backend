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
        try {
            $stores = Store::with(['owner', 'categories', 'verifications', 'addresses'])
                ->where('status', StoreStatus::PENDING)
                ->latest()
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'message' => 'Pending stores retrieved successfully.',
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
            
            return response()->json([
                'message' => 'Unable to retrieve pending stores. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get all stores with filters
     */
    public function index(Request $request): JsonResponse
    {
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

            return response()->json([
                'message' => 'Stores retrieved successfully.',
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
            
            return response()->json([
                'message' => 'Unable to retrieve stores. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get single store details
     */
    public function show(Store $store): JsonResponse
    {
        try {
            $store->load(['owner.profile', 'categories', 'verifications', 'addresses', 'hours']);

            return response()->json([
                'message' => 'Store details retrieved successfully.',
                'data' => new StoreResource($store),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store details', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Unable to retrieve store details. Please try again later.',
            ], 500);
        }
    }

    /**
     * Approve store registration
     */
    public function approve(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if store is already approved
        if ($store->status === StoreStatus::ACTIVE) {
            return response()->json([
                'message' => 'This store is already approved.',
            ], 400);
        }

        // Check if store is not pending
        if ($store->status !== StoreStatus::PENDING) {
            return response()->json([
                'message' => 'Only pending stores can be approved.',
            ], 400);
        }

        try {
            $this->approveStore->execute($store, $request->user(), $validated['notes'] ?? null);

            return response()->json([
                'message' => 'Store approved successfully.',
                'data' => new StoreResource($store->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Store approval failed', [
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to approve store. Please try again later.',
            ], 500);
        }
    }

    /**
     * Reject store registration
     */
    public function reject(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Check if store is already rejected
        if ($store->status === StoreStatus::REJECTED) {
            return response()->json([
                'message' => 'This store is already rejected.',
            ], 400);
        }

        // Check if store is not pending
        if ($store->status !== StoreStatus::PENDING) {
            return response()->json([
                'message' => 'Only pending stores can be rejected.',
            ], 400);
        }

        try {
            $this->rejectStore->execute($store, $request->user(), $validated['reason']);

            return response()->json([
                'message' => 'Store rejected successfully.',
                'data' => new StoreResource($store->fresh()),
            ]);
        } catch (\Exception $e) {
            Log::error('Store rejection failed', [
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to reject store. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get store statistics
     */
    public function statistics(): JsonResponse
    {
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

            return response()->json([
                'message' => 'Store statistics retrieved successfully.',
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve store statistics', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => 'Unable to retrieve store statistics. Please try again later.',
            ], 500);
        }
    }

    /**
     * Get store verification documents
     */
    public function verificationDocuments(Store $store): JsonResponse
    {
        try {
            $verifications = $store->verifications()->get();

            return response()->json([
                'message' => 'Verification documents retrieved successfully.',
                'data' => $verifications,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve verification documents', [
                'store_id' => $store->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Unable to retrieve verification documents. Please try again later.',
            ], 500);
        }
    }

    /**
     * Approve a verification document
     */
    public function approveDocument(Request $request, Store $store, StoreVerification $verification): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if verification belongs to store
        if ($verification->store_id !== $store->id) {
            return response()->json([
                'message' => 'Verification document does not belong to this store.',
            ], 400);
        }

        // Check if already approved
        if ($verification->status === 'approved') {
            return response()->json([
                'message' => 'This document is already approved.',
            ], 400);
        }

        try {
            $this->approveVerificationDocument->execute(
                $verification,
                $request->user(),
                $validated['notes'] ?? null
            );

            return response()->json([
                'message' => 'Verification document approved successfully.',
                'data' => $verification->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Document approval failed', [
                'verification_id' => $verification->id,
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to approve document. Please try again later.',
            ], 500);
        }
    }

    /**
     * Reject a verification document
     */
    public function rejectDocument(Request $request, Store $store, StoreVerification $verification): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        // Check if verification belongs to store
        if ($verification->store_id !== $store->id) {
            return response()->json([
                'message' => 'Verification document does not belong to this store.',
            ], 400);
        }

        // Check if already rejected
        if ($verification->status === 'rejected') {
            return response()->json([
                'message' => 'This document is already rejected.',
            ], 400);
        }

        try {
            $this->rejectVerificationDocument->execute(
                $verification,
                $request->user(),
                $validated['reason']
            );

            return response()->json([
                'message' => 'Verification document rejected successfully.',
                'data' => $verification->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Document rejection failed', [
                'verification_id' => $verification->id,
                'store_id' => $store->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to reject document. Please try again later.',
            ], 500);
        }
    }
}
