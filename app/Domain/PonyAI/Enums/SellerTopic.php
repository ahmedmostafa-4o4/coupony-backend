<?php

namespace App\Domain\PonyAI\Enums;

enum SellerTopic: string
{
    case UNDERPERFORMING_PRODUCTS = 'underperforming_products';
    case OFFER_SUGGESTION = 'offer_suggestion';
    case CAMPAIGN_IDEA = 'campaign_idea';
    case INVENTORY_WARNING = 'inventory_warning';
    case FREE_FORM = 'free_form';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromString(?string $value): self
    {
        if ($value === null) {
            return self::FREE_FORM;
        }

        foreach (self::cases() as $case) {
            if ($case->value === $value) {
                return $case;
            }
        }

        return self::FREE_FORM;
    }
}
