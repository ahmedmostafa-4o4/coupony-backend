<?php

namespace App\Application\Http\Controllers\API\V1;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\CreateStoreRequest;
use App\Application\Http\Requests\UpdateStoreRequest;
use App\Application\Http\Requests\UpdateVerificationDocumentRequest;
use App\Application\Http\Resources\StoreCollection;
use App\Application\Http\Resources\StoreResource;
use App\Domain\Store\Actions\CreateStore;
use App\Domain\Store\Actions\UpdateStore;
use App\Domain\Store\Actions\UpdateVerificationDocument;
use App\Domain\Store\DTOs\StoreData;
use App\Domain\Store\Enums\VerificationDocumentType;
use App\Domain\Store\Enums\VerificationStatus;
use App\Domain\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StoreController extends Controller
{
    public function __construct(
        private readonly CreateStore $createStoreAction,
        private readonly UpdateStore $updateStoreAction,
        private readonly UpdateVerificationDocument $updateVerificationDocumentAction
    ) {
    }

    /**
     * Create a new store.
     */
    public function store(CreateStoreRequest $request): JsonResponse
    {
        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $user) {
                // Create store
                $store = $this->createStoreAction->execute(
                    $user,
                    StoreData::fromRequest($request)
                );

                // Handle file uploads after store creation
                $this->handleStoreFileUploads($request, $store);
                $this->handleVerificationDocuments($request, $store);

                // Load relationships
                $store->load($this->storeRelations());

                Log::info('Store created successfully', [
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                ]);

                return $this->successResponse(
                    new StoreResource($store),
                    'Store created successfully. Pending approval.',
                    201
                );
            });
        } catch (Throwable $e) {
            Log::error('Store creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to create store. Please try again later.',
                500
            );
        }
    }

    /**
     * Update store information.
     */
    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        Gate::authorize('update', $store);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $store, $user) {
                $updatedStore = $this->updateStoreAction->execute(
                    $store,
                    $user,
                    StoreData::fromUpdateRequest($request, $store->id)
                );

                // Load relationships
                $updatedStore->load($this->storeRelations());

                Log::info('Store updated successfully', [
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                ]);

                return $this->successResponse(
                    new StoreResource($updatedStore),
                    'Store updated successfully.'
                );
            });
        } catch (Throwable $e) {
            Log::error('Store update failed', [
                'store_id' => $store->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to update store. Please try again later.',
                500
            );
        }
    }

    /**
     * Get authenticated user's stores.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $stores = $request->user()
                ->stores()
                ->with($this->storeRelations())
                ->latest()
                ->get();

            return $this->successResponse(
                new StoreCollection($stores),
                'Stores retrieved successfully.'
            );
        } catch (Throwable $e) {
            Log::error('Failed to retrieve user stores', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve stores. Please try again later.',
                500
            );
        }
    }

    /**
     * Update verification document.
     */
    public function updateVerificationDocument(
        UpdateVerificationDocumentRequest $request,
        Store $store
    ): JsonResponse {
        Gate::authorize('updateVerificationDocuments', $store);

        $user = $request->user();

        try {
            return DB::transaction(function () use ($request, $store, $user) {
                $documentType = $request->input('document_type');
                $file = $request->file('document');

                // Upload new file
                $path = $this->uploadVerificationDocument($file, $store->id, $documentType);

                // Update verification document
                $verification = $this->updateVerificationDocumentAction->execute(
                    $store,
                    $user,
                    $documentType,
                    $path
                );

                Log::info('Verification document updated', [
                    'store_id' => $store->id,
                    'document_type' => $documentType,
                    'user_id' => $user->id,
                ]);

                return $this->successResponse(
                    $verification,
                    'Verification document updated successfully. It will be reviewed by our team.'
                );
            });
        } catch (Throwable $e) {
            Log::error('Verification document update failed', [
                'store_id' => $store->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'Failed to update verification document. Please try again later.',
                500
            );
        }
    }

    /**
     * Handle store file uploads (logo and banner).
     */
    private function handleStoreFileUploads(CreateStoreRequest $request, Store $store): void
    {
        if ($request->hasFile('logo_url')) {
            $logoPath = $this->uploadFile(
                $request->file('logo_url'),
                $store->id,
                'logo'
            );
            $store->update(['logo_url' => $logoPath]);
        }

        if ($request->hasFile('banner_url')) {
            $bannerPath = $this->uploadFile(
                $request->file('banner_url'),
                $store->id,
                'banner'
            );
            $store->update(['banner_url' => $bannerPath]);
        }
    }

    /**
     * Handle verification documents upload.
     */
    private function handleVerificationDocuments(CreateStoreRequest $request, Store $store): void
    {
        foreach (VerificationDocumentType::cases() as $docType) {
            $requestKey = "verification_docs.{$docType->value}";

            if ($request->hasFile($requestKey)) {
                $docPath = $this->uploadVerificationDocument(
                    $request->file($requestKey),
                    $store->id,
                    $docType->value
                );

                $store->verifications()->updateOrCreate(
                    ['document_type' => $docType->value],
                    [
                        'document_path' => $docPath,
                        'status' => VerificationStatus::PENDING->value,
                    ]
                );
            }
        }
    }

    /**
     * Upload a file to storage.
     */
    private function uploadFile($file, string $storeId, string $folder): string
    {
        return $file->store("stores/{$storeId}/{$folder}", 'public');
    }

    /**
     * Upload a verification document.
     */
    private function uploadVerificationDocument($file, string $storeId, string $documentType): string
    {
        return $file->store("stores/{$storeId}/verifications/{$documentType}", 'public');
    }

    /**
     * Delete a file from storage if it exists.
     */
    private function deleteFileIfExists(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Get standard store relationships to load.
     */
    private function storeRelations(): array
    {
        return ['owner', 'categories', 'addresses', 'verifications', 'hours'];
    }

    /**
     * Return a success JSON response.
     */
    private function successResponse($data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error JSON response.
     */
    private function errorResponse(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
