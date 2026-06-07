<?php

namespace App\Domain\Notification\Jobs;

use App\Domain\Notification\Models\NotificationBroadcast;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotificationBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout for large broadcasts

    public function __construct(public NotificationBroadcast $broadcast)
    {
    }

    public function handle(NotificationService $notificationService): void
    {
        $this->broadcast->update(['status' => 'processing']);

        $query = User::query();

        if (!empty($this->broadcast->target_user_ids)) {
            $query->whereIn('id', $this->broadcast->target_user_ids);
        } elseif (!empty($this->broadcast->target_roles)) {
            if (!in_array('all', $this->broadcast->target_roles)) {
                $query->role($this->broadcast->target_roles);
            }
        } else {
            // Nothing targeted
            $this->broadcast->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            return;
        }

        $totalSent = 0;
        $totalFailed = 0;

        $query->chunk(100, function ($users) use ($notificationService, &$totalSent, &$totalFailed) {
            foreach ($this->broadcast->channels as $channel) {
                // The sendBulk method handles looping through users, sending notifications, and catching errors
                $result = $notificationService->sendBulk(
                    users: $users,
                    type: 'admin_broadcast',
                    title: $this->broadcast->title,
                    message: $this->broadcast->message,
                    channel: $channel,
                    data: ['broadcast_id' => $this->broadcast->id]
                );

                $totalSent += $result['sent'] ?? 0;
                $totalFailed += $result['failed'] ?? 0;
            }
            
            // Update counts progressively
            $this->broadcast->update([
                'total_sent' => $totalSent,
                'total_failed' => $totalFailed,
            ]);
        });

        $this->broadcast->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->broadcast->update([
            'status' => 'failed',
        ]);

        Log::error("Broadcast Job Failed", [
            'broadcast_id' => $this->broadcast->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
