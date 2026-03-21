<?php

namespace App\Domain\Store\Actions;

use App\Domain\Store\Events\VerificationDocumentRejected;
use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\User;

class RejectVerificationDocument
{
    public function execute(StoreVerification $verification, User $admin, string $reason): StoreVerification
    {
        // Check if already rejected
        if ($verification->status === 'rejected') {
            throw new \Exception('This document is already rejected.');
        }

        // Update verification document
        $verification->update([
            'status' => 'rejected',
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);

        // Mark store as not verified
        $store = $verification->store;
        $store->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);

        // Dispatch event
        event(new VerificationDocumentRejected($verification, $admin, $reason));

        return $verification->fresh();
    }
}
