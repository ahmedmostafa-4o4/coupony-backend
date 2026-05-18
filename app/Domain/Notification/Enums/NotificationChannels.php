<?php

namespace App\Domain\Notification\Enums;

enum NotificationChannels: string
{
    case IN_APP = 'in_app';
    case EMAIL = 'email';
    case SMS = 'sms';
    case PUSH = 'push';
}
