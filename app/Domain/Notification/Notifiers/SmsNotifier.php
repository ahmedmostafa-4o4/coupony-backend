<?php

namespace App\Domain\Notification\Notifiers;

use App\Domain\Notification\Contracts\NotifierInterface;
use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Http;

class SmsNotifier implements NotifierInterface
{
    public function send(Notification $notification, User $user): void
    {
        if (! $user->phone_number) {
            throw new \Exception('User has no phone number');
        }

        // Using Twilio (example)
        $this->sendViaTwilio($notification, $user);
    }

    private function sendViaTwilio(Notification $notification, User $user): void
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $fromNumber = config('services.twilio.from_number');

        $message = $this->formatSmsMessage($notification);

        Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $fromNumber,
                'To' => $user->phone_number,
                'Body' => $message,
            ])
            ->throw();
    }

    private function formatSmsMessage(Notification $notification): string
    {
        $appName = config('app.name');

        return "{$appName}: {$notification->title}. {$notification->message}";
    }
}
