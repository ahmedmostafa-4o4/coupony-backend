# Database Seeders

This directory contains all database seeders for the Coupony application.

## Available Seeders

### 1. RoleAndPermissionSeeder
Creates all roles and permissions for the application using Spatie Permission package.

**Roles Created:**
- `admin` - Full system access
- `seller` - Approved store owners
- `seller_pending` - Pending store owners
- `customer` - Regular customers
- `store_manager` - Store managers
- `store_staff` - Store staff members

**Permissions Created:**
- User management (view, create, edit, delete)
- Store management (view, create, edit, delete, approve, reject)
- Category management (view, create, edit, delete)
- Order management (view, create, edit, delete)
- Report management (view, generate)

### 2. UserSeeder
Creates test users with different roles.

**Users Created:**
- 1 Admin user: `admin@coupony.com` / `password`
- 5 Seller users: `seller1-5@example.com` / `password`
  - 3 approved sellers (role: seller)
  - 2 pending sellers (role: seller_pending)
- 20 Customer users (randomly generated)

Each user includes:
- Profile (first name, last name, date of birth, gender)
- User preferences (language, currency, notifications)

### 3. StoreCategorySeeder
Creates 15 predefined store categories.

**Categories:**
- Electronics
- Fashion & Clothing
- Food & Beverages
- Home & Garden
- Beauty & Health
- Sports & Outdoors
- Books & Media
- Toys & Games
- Automotive
- Jewelry & Accessories
- Pet Supplies
- Office Supplies
- Baby & Kids
- Furniture
- Grocery

### 4. StoreSeeder
Creates stores for sellers with different statuses.

**Stores Created:**
- Active stores for approved sellers (3 stores)
- Pending stores for pending sellers (2 stores)
- Rejected stores (2 stores)

Each store includes:
- Store information (name, description, contact)
- Random categories (1-3 categories)
- Address with coordinates
- Store hours (7 days)
- Verification documents (4 documents)

### 5. NotificationSeeder
Creates sample notifications for users.

**Notifications Created:**
- Welcome notification for each user
- 2-5 random notifications per user (first 10 users)

Notification types:
- Welcome
- Info
- Success
- Promotion
- Reminder

## Running Seeders

### Run All Seeders
```bash
php artisan db:seed
```

### Run Specific Seeder
```bash
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=StoreCategorySeeder
php artisan db:seed --class=StoreSeeder
php artisan db:seed --class=NotificationSeeder
```

### Fresh Migration with Seeding
```bash
php artisan migrate:fresh --seed
```

## Seeding Order

The seeders must be run in the following order due to dependencies:

1. **RoleAndPermissionSeeder** - Creates roles first
2. **UserSeeder** - Creates users and assigns roles
3. **StoreCategorySeeder** - Creates categories
4. **StoreSeeder** - Creates stores (requires users and categories)
5. **NotificationSeeder** - Creates notifications (requires users)

This order is automatically handled by `DatabaseSeeder.php`.

## Test Credentials

After seeding, you can use these credentials for testing:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@coupony.com | password |
| Seller (Approved) | seller1@example.com | password |
| Seller (Approved) | seller2@example.com | password |
| Seller (Approved) | seller3@example.com | password |
| Seller (Pending) | seller4@example.com | password |
| Seller (Pending) | seller5@example.com | password |

## Database Statistics After Seeding

- **Users:** 26 (1 admin + 5 sellers + 20 customers)
- **Roles:** 6
- **Permissions:** ~20
- **Store Categories:** 15
- **Stores:** 7 (3 active + 2 pending + 2 rejected)
- **Store Hours:** 49 (7 per store)
- **Store Verifications:** 28 (4 per store)
- **Notifications:** ~40-50 (varies)

## Notes

- All passwords are hashed using bcrypt
- Faker is used to generate realistic test data
- UUIDs are automatically generated for applicable models
- Timestamps are set to realistic values
- All seeders include progress messages for better visibility

## Customization

You can customize the number of records created by modifying the seeder files:

```php
// In UserSeeder.php
User::factory()->count(20)->create() // Change 20 to desired number

// In StoreSeeder.php
foreach ($sellers as $seller) // Modify loop logic
```

## Troubleshooting

### "Please run UserSeeder first!"
This means you're trying to run a seeder that depends on users. Run seeders in order or use `php artisan db:seed` to run all.

### Foreign Key Constraint Errors
Make sure migrations are run before seeding:
```bash
php artisan migrate:fresh --seed
```

### Duplicate Entry Errors
If you see duplicate entry errors, the database might already have data. Use:
```bash
php artisan migrate:fresh --seed
```
