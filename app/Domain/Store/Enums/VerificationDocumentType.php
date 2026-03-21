<?php

namespace App\Domain\Store\Enums;

enum VerificationDocumentType: string
{
    case COMMERCIAL_REGISTER = 'commercial_register';
    case TAX_CARD = 'tax_card';
    case ID_CARD_FRONT = 'id_card_front';
    case ID_CARD_BACK = 'id_card_back';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
