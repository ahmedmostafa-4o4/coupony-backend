<?php

namespace App\Domain\Store\Enums;

enum StorePermission: string
{
    case DASHBOARD_VIEW = 'store.dashboard.view';

    case PRODUCTS_VIEW = 'store.products.view';
    case PRODUCTS_CREATE = 'store.products.create';
    case PRODUCTS_UPDATE = 'store.products.update';
    case PRODUCTS_DELETE = 'store.products.delete';
    case PRODUCTS_MANAGE = 'store.products.manage';

    case OFFERS_VIEW = 'store.offers.view';
    case OFFERS_CREATE = 'store.offers.create';
    case OFFERS_UPDATE = 'store.offers.update';
    case OFFERS_DELETE = 'store.offers.delete';
    case OFFERS_MANAGE = 'store.offers.manage';

    case CLAIMS_VIEW = 'store.claims.view';
    case CLAIMS_REDEEM = 'store.claims.redeem';
    case CLAIMS_CANCEL = 'store.claims.cancel';
    case CLAIMS_EXPORT = 'store.claims.export';
    case CLAIMS_MANAGE = 'store.claims.manage';

    case ORDERS_VIEW = 'store.orders.view';
    case ORDERS_UPDATE = 'store.orders.update';
    case ORDERS_CANCEL = 'store.orders.cancel';
    case ORDERS_REFUND = 'store.orders.refund';
    case ORDERS_MANAGE = 'store.orders.manage';

    case EMPLOYEES_VIEW = 'store.employees.view';
    case EMPLOYEES_INVITE = 'store.employees.invite';
    case EMPLOYEES_UPDATE = 'store.employees.update';
    case EMPLOYEES_REMOVE = 'store.employees.remove';
    case EMPLOYEES_MANAGE = 'store.employees.manage';

    case BRANCHES_VIEW = 'store.branches.view';
    case BRANCHES_CREATE = 'store.branches.create';
    case BRANCHES_UPDATE = 'store.branches.update';
    case BRANCHES_DELETE = 'store.branches.delete';
    case BRANCHES_MANAGE = 'store.branches.manage';

    case SETTINGS_VIEW = 'store.settings.view';
    case SETTINGS_UPDATE = 'store.settings.update';
    case SETTINGS_MANAGE = 'store.settings.manage';

    case ANALYTICS_VIEW = 'store.analytics.view';
    case REVIEWS_VIEW = 'store.reviews.view';
    case REVIEWS_MODERATE = 'store.reviews.moderate';
    case NOTIFICATIONS_MANAGE = 'store.notifications.manage';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function grouped(): array
    {
        return [
            'dashboard' => [
                self::DASHBOARD_VIEW->value,
            ],
            'products' => [
                self::PRODUCTS_VIEW->value,
                self::PRODUCTS_CREATE->value,
                self::PRODUCTS_UPDATE->value,
                self::PRODUCTS_DELETE->value,
                self::PRODUCTS_MANAGE->value,
            ],
            'offers' => [
                self::OFFERS_VIEW->value,
                self::OFFERS_CREATE->value,
                self::OFFERS_UPDATE->value,
                self::OFFERS_DELETE->value,
                self::OFFERS_MANAGE->value,
            ],
            'claims' => [
                self::CLAIMS_VIEW->value,
                self::CLAIMS_REDEEM->value,
                self::CLAIMS_CANCEL->value,
                self::CLAIMS_EXPORT->value,
                self::CLAIMS_MANAGE->value,
            ],
            'orders' => [
                self::ORDERS_VIEW->value,
                self::ORDERS_UPDATE->value,
                self::ORDERS_CANCEL->value,
                self::ORDERS_REFUND->value,
                self::ORDERS_MANAGE->value,
            ],
            'employees' => [
                self::EMPLOYEES_VIEW->value,
                self::EMPLOYEES_INVITE->value,
                self::EMPLOYEES_UPDATE->value,
                self::EMPLOYEES_REMOVE->value,
                self::EMPLOYEES_MANAGE->value,
            ],
            'branches' => [
                self::BRANCHES_VIEW->value,
                self::BRANCHES_CREATE->value,
                self::BRANCHES_UPDATE->value,
                self::BRANCHES_DELETE->value,
                self::BRANCHES_MANAGE->value,
            ],
            'settings' => [
                self::SETTINGS_VIEW->value,
                self::SETTINGS_UPDATE->value,
                self::SETTINGS_MANAGE->value,
            ],
            'analytics' => [
                self::ANALYTICS_VIEW->value,
            ],
            'reviews' => [
                self::REVIEWS_VIEW->value,
                self::REVIEWS_MODERATE->value,
            ],
            'notifications' => [
                self::NOTIFICATIONS_MANAGE->value,
            ],
        ];
    }

    public static function labels(): array
    {
        return [
            self::DASHBOARD_VIEW->value => 'View dashboard',

            self::PRODUCTS_VIEW->value => 'View products',
            self::PRODUCTS_CREATE->value => 'Create products',
            self::PRODUCTS_UPDATE->value => 'Update products',
            self::PRODUCTS_DELETE->value => 'Delete products',
            self::PRODUCTS_MANAGE->value => 'Manage products',

            self::OFFERS_VIEW->value => 'View offers',
            self::OFFERS_CREATE->value => 'Create offers',
            self::OFFERS_UPDATE->value => 'Update offers',
            self::OFFERS_DELETE->value => 'Delete offers',
            self::OFFERS_MANAGE->value => 'Manage offers',

            self::CLAIMS_VIEW->value => 'View claims',
            self::CLAIMS_REDEEM->value => 'Redeem claims',
            self::CLAIMS_CANCEL->value => 'Cancel claims',
            self::CLAIMS_EXPORT->value => 'Export claims',
            self::CLAIMS_MANAGE->value => 'Manage claims',

            self::ORDERS_VIEW->value => 'View orders',
            self::ORDERS_UPDATE->value => 'Update orders',
            self::ORDERS_CANCEL->value => 'Cancel orders',
            self::ORDERS_REFUND->value => 'Refund orders',
            self::ORDERS_MANAGE->value => 'Manage orders',

            self::EMPLOYEES_VIEW->value => 'View employees',
            self::EMPLOYEES_INVITE->value => 'Invite employees',
            self::EMPLOYEES_UPDATE->value => 'Update employees',
            self::EMPLOYEES_REMOVE->value => 'Remove employees',
            self::EMPLOYEES_MANAGE->value => 'Manage employees',

            self::BRANCHES_VIEW->value => 'View branches',
            self::BRANCHES_CREATE->value => 'Create branches',
            self::BRANCHES_UPDATE->value => 'Update branches',
            self::BRANCHES_DELETE->value => 'Delete branches',
            self::BRANCHES_MANAGE->value => 'Manage branches',

            self::SETTINGS_VIEW->value => 'View settings',
            self::SETTINGS_UPDATE->value => 'Update settings',
            self::SETTINGS_MANAGE->value => 'Manage settings',

            self::ANALYTICS_VIEW->value => 'View analytics',
            self::REVIEWS_VIEW->value => 'View reviews',
            self::REVIEWS_MODERATE->value => 'Moderate reviews',
            self::NOTIFICATIONS_MANAGE->value => 'Manage notifications',
        ];
    }
}
