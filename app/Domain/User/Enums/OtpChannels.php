<?php

namespace App\Domain\User\Enums;

enum OtpChannels: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case WHATSAPP = 'whatsapp';
}
