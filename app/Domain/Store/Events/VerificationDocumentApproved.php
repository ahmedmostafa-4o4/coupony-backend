<?php

namespace App\Domain\Store\Events;

use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VerificationDocumentApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public StoreVerification $verification,
        public User $admin,
        public ?string $notes = null
    ) {
    }
}
