<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Events\VerificationDocumentUpdated;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Storage;

class UpdateVerificationDocument
{
    public function execute(Store $store, User $user, string $documentType, string $documentPath): StoreVerification
    {
        // Check if user owns the store
        if ($store->owner_user_id !== $user->id) {
            throw new \Exception('You are not authorized to update this store\'s documents.');
        }

        // Check if store is approved (cannot update approved store documents)
        if ($store->status === 'active') {
            throw new \Exception('Cannot update documents for an approved store. Please contact support.');
        }

        // Find existing verification document
        $verification = $store->verifications()
            ->where('document_type', $documentType)
            ->first();

        if (!$verification) {
            // Create new verification document
            $verification = $store->verifications()->create([
                'document_type' => $documentType,
                'document_path' => $documentPath,
                'status' => 'pending',
            ]);
        } else {
            // Delete old document file if exists
            if ($verification->document_path && Storage::disk('public')->exists($verification->document_path)) {
                Storage::disk('public')->delete($verification->document_path);
            }

            // Update existing document
            $verification->update([
                'document_path' => $documentPath,
                'status' => 'pending',
                'verified_by' => null,
                'verified_at' => null,
                'rejection_reason' => null,
            ]);
        }

        // If store was rejected, reset to pending
        if ($store->status === 'rejected') {
            $store->update([
                'status' => 'pending',
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);
        }

        // Dispatch event
        event(new VerificationDocumentUpdated($verification, $user));

        return $verification->fresh();
    }
}
