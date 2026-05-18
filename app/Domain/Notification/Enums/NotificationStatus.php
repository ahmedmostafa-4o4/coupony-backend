<?php

namespace App\Domain\Notification\Enums;

enum NotificationStatus: string
{
    case READ = 'read';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case SENT = 'sent';
}
