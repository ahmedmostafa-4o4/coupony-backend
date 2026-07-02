# Database Seeders

The default `php artisan db:seed` path is production-safe reference seeding. It does not delete application data and does not create demo sellers, customers, stores, products, claims, or notifications.

## Default Production Seeders

`DatabaseSeeder` runs these seeders:

- `RoleAndPermissionSeeder` - syncs canonical roles and store permissions.
- `AdminUserSeeder` - creates or updates one admin account from environment values.
- `StoreCategorySeeder` - creates the predefined store categories.
- `ProductCategorySeeder` - creates the product category tree.
- `SocialSeeder` - creates supported social platforms.
- `SubscriptionPlanSeeder` - creates the active subscription plans.

Set `ADMIN_PASSWORD` in the environment before running the default seeder when the admin account does not already exist:

```dotenv
ADMIN_EMAIL=admin@coupony.com
ADMIN_PASSWORD=change-me
```

Optional admin environment values:

- `ADMIN_EMAIL`, default `admin@coupony.com`
- `ADMIN_PHONE`
- `ADMIN_FIRST_NAME`, default `Admin`
- `ADMIN_LAST_NAME`, default `User`

Run the production reference seed set:

```bash
php artisan db:seed
```

## Specific Seeders

Run one reference seeder when you only need to refresh a single lookup set:

```bash
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=StoreCategorySeeder
php artisan db:seed --class=ProductCategorySeeder
php artisan db:seed --class=SocialSeeder
php artisan db:seed --class=SubscriptionPlanSeeder
```

## Demo Seeders

Demo seeders are still available for local development, but they are not part of `DatabaseSeeder`.

- `UserSeeder`
- `StoreSeeder`
- `ProductSeeder`
- `NotificationSeeder`
- `LargeDemoSeeder`

Do not run demo seeders against production data.
