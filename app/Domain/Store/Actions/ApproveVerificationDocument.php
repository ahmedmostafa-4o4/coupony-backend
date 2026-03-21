<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Events\VerificationDocumentApproved;
use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\User;

class ApproveVerificationDocument
{
    public function execute(StoreVerification $verification, User $admin, ?string $notes = null): StoreVerification
    {
        // Check if already approved
        if ($verification->status === 'approved') {
            throw new \Exception('This document is already approved.');
        }

        // Update verification document
        $verification->update([
            'status' => 'approved',
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        // Check if all documents are approved
        $store = $verification->store;
        $allDocumentsApproved = $store->verifications()
            ->where('status', '!=', 'approved')
            ->doesntExist();

        // If all documents are approved, mark store as verified
        if ($allDocumentsApproved) {
            $store->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }

        // Dispatch event
        event(new VerificationDocumentApproved($verification, $admin, $notes));

        return $verification->fresh();
    }
}
