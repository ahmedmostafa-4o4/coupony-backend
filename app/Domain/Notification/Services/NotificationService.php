<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Contracts\NotifierInterface;
use App\Domain\Notification\Events\NotificationSent;
use App\Domain\Notification\Mail\notifyMe;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    private array $notifiers = [];

    public function __construct()
    {
        $this->registerNotifiers();
    }

    /**
     * Send notification to user.
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        string $channel = 'in_app',
        array $data = [],
        ?string $referenceType = null,
        ?string $referenceId = null
        ): Notification
    {
        // Create notification record
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channel' => $channel,
            'status' => 'pending',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);

        // Send via channel
        try {
            $this->sendViaChannel($notification, $user, $channel);
            $notification->markAsSent();

            event(new NotificationSent($notification, $user));
        }
        catch (\Exception $e) {
            Log::error('Notification Send Failed', [
                'notification_id' => $notification->id,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);

            $notification->markAsFailed($e->getMessage());
        }

        return $notification;
    }

    /**
     * Send bulk notifications.
     */
    public function sendBulk(
        Collection $users,
        string $type,
        string $title,
        string $message,
        string $channel = 'in_app',
        array $data = []
        ): array
    {
        $sent = [];
        $failed = [];

        foreach ($users as $user) {
            try {
                $notification = $this->send(
                    $user,
                    $type,
                    $title,
                    $message,
                    $channel,
                    $data
                );
                $sent[] = $notification->id;
            }
            catch (\Exception $e) {
                $failed[] = [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'sent' => count($sent),
            'failed' => count($failed),
            'sent_ids' => $sent,
            'failed_users' => $failed,
        ];
    }

    /**
     * Send to admin users.
     */
    public function notifyAdmins(
        string $type,
        string $title,
        string $message,
        array $data = [],
        string $channel = 'in_app'
        ): void
    {
        $admins = User::role('admin')->get();

        $this->sendBulk($admins, $type, $title, $message, $channel, $data);
    }

    /**
     * Send via specific channel.
     */
    private function sendViaChannel(
        Notification $notification,
        User $user,
        string $channel
        ): void
    {
        // Check user preferences
        if (!$this->canSendToChannel($user, $channel)) {
            throw new \Exception("User has disabled {$channel} notifications");
        }

        $notifier = $this->getNotifier($channel);

        if (!$notifier) {
            throw new \Exception("No notifier found for channel: {$channel}");
        }

        $notifier->send($notification, $user);
    }

    /**
     * Check if user allows notifications on channel.
     */
    private function canSendToChannel(User $user, string $channel): bool
    {
        if ($channel === 'in_app') {
            return true; // Always allow in-app
        }

        $preferences = $user->preferences;

        if (!$preferences) {
            return true; // Default: allow if no preferences set
        }

        return match ($channel) {
                'email' => $preferences->email_order_updates ?? true,
                'sms' => $preferences->sms_notifications ?? false,
                'push' => $preferences->push_notifications ?? true,
                default => false,
            };
    }

    /**
     * Register all notifiers.
     */
    private function registerNotifiers(): void
    {
        $this->notifiers = [
            'in_app' => app(\App\Domain\Notification\Notifiers\InAppNotifier::class),
            'email' => app(\App\Domain\Notification\Notifiers\EmailNotifier::class),
            'sms' => app(\App\Domain\Notification\Notifiers\SmsNotifier::class),
            'push' => app(\App\Domain\Notification\Notifiers\PushNotifier::class),
        ];
    }

    /**
     * Get notifier for channel.
     */
    private function getNotifier(string $channel): ?NotifierInterface
    {
        return $this->notifiers[$channel] ?? null;
    }

    public function notifyAll(array $data)
    {
        $emails = DB::table('notify_me')->pluck('email');

        Mail::to($emails)->send(new notifyMe($data['subject'], $data['message']));
    }
}
