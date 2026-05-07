<?php

namespace App\Domain\Store\Enums;

enum StorePermission: string
{
    case PRODUCTS_MANAGE = 'store.products.manage';
    case PRODUCTS_VIEW = 'store.products.view';
    case ORDERS_MANAGE = 'store.orders.manage';
    case ORDERS_VIEW = 'store.orders.view';
    case CLAIMS_MANAGE = 'store.claims.manage';
    case CLAIMS_VIEW = 'store.claims.view';
    case EMPLOYEES_MANAGE = 'store.employees.manage';
    case SETTINGS_MANAGE = 'store.settings.manage';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
