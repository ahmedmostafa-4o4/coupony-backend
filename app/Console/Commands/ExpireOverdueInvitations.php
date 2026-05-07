<?php

namespace App\Console\Commands;

use App\Domain\Store\Services\StoreInvitationService;
use Illuminate\Console\Command;

class ExpireOverdueInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire overdue store invitations';

    /**
     * Execute the console command.
     */
    public function handle(StoreInvitationService $service)
    {
        $this->info('Starting to expire overdue invitations...');
        
        $count = $service->expireOverdueInvitations();
        
        $this->info("Successfully expired {$count} overdue invitations.");
    }
}
