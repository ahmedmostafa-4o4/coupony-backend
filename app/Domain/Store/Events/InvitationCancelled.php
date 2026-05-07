<?php

namespace App\Domain\Store\Events;

use App\Domain\Store\Models\StoreInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvitationCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public StoreInvitation $invitation;

    public function __construct(StoreInvitation $invitation)
    {
        $this->invitation = $invitation;
    }
}
