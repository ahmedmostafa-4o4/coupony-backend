<?php

namespace App\Domain\Store\Events;

use App\Domain\Store\Models\Store;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StaffAssignedToStore
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Store $store;

    public User $staff;

    public User $assignedBy;

    public string $roleName;

    /**
     * Create a new class instance.
     */
    public function __construct(Store $store, User $staff, string $roleName, ?User $assignedBy = null)
    {
        $this->store = $store;
        $this->staff = $staff;
        $this->roleName = $roleName;
        $this->assignedBy = $assignedBy;
    }
}
