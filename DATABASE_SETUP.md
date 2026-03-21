# Database Setup Guide

Complete guide for setting up the Coupony database with migrations, factories, and seeders.

## Quick Start

```bash
# Fresh installation with sample data
php artisan migrate:fresh --seed
```

## What Gets Created

### Users (26 total)
- **1 Admin**: `admin@coupony.com` / `password`
- **5 Sellers**: `seller1-5@example.com` / `password`
  - 3 approved sellers (role: `seller`)
  - 2 pending sellers (role: `seller_pending`)
- **20 Customers**: Randomly generated

### Roles & Permissions
- 6 roles (admin, seller, seller_pending, customer, store_manager, store_staff)
- ~20 permissions for various operations

### Store Categories (15)
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

### Stores (7 total)
- **3 Active stores** (approved, with full data)
- **2 Pending stores** (awaiting approval)
- **2 Rejected stores** (rejected with reasons)

Each store includes:
- Store information and contact details
- 1-3 random categories
- Physical address with GPS coordinates
- 7 days of operating hours
- 4 verification documents

### Notifications (~40-50)
- Welcome notifications for users
- Random notifications (info, success, promotion, reminder)

## Step-by-Step Setup

### 1. Configure Database

Edit `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coupony
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 2. Create Database

```bash
mysql -u root -p
CREATE DATABASE coupony CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

### 3. Run Migrations

```bash
# Run all migrations
php artisan migrate

# Or fresh start (drops all tables)
php artisan migrate:fresh
```

### 4. Run Seeders

```bash
# Run all seeders
php artisan db:seed

# Or run specific seeder
php artisan db:seed --class=RoleAndPermissionSeeder
php artisan db:seed --class=UserSeeder
php artisan db:seed --class=StoreCategorySeeder
php artisan db:seed --class=StoreSeeder
php artisan db:seed --class=NotificationSeeder
```

### 5. Verify Setup

```bash
# Check database tables
php artisan db:show

# Check specific table
php artisan db:table users

# Test login
php artisan tinker
>>> $user = User::where('email', 'admin@coupony.com')->first();
>>> $user->email
```

## Available Factories

All models have factories for testing and development:

- `UserFactory` - Create users with profiles
- `ProfileFactory` - Create user profiles
- `UserPreferenceFactory` - Create user preferences
- `StoreFactory` - Create stores (active, pending, rejected, suspended)
- `StoreCategoryFactory` - Create store categories
- `StoreHoursFactory` - Create store hours
- `StoreVerificationFactory` - Create verification documents
- `AddressFactory` - Create addresses with GPS
- `NotificationFactory` - Create notifications
- `OtpFactory` - Create OTP codes

See `database/factories/README.md` for detailed usage.

## Available Seeders

- `RoleAndPermissionSeeder` - Creates roles and permissions
- `UserSeeder` - Creates admin, sellers, and customers
- `StoreCategorySeeder` - Creates 15 store categories
- `StoreSeeder` - Creates stores with all relations
- `NotificationSeeder` - Creates sample notifications

See `database/seeders/README.md` for detailed information.

## Testing Credentials

### Admin Access
```
Email: admin@coupony.com
Password: password
Role: admin
```

### Seller Access (Approved)
```
Email: seller1@example.com (or seller2, seller3)
Password: password
Role: seller
```

### Seller Access (Pending)
```
Email: seller4@example.com (or seller5)
Password: password
Role: seller_pending
```

### Customer Access
Use any of the 20 randomly generated customer accounts.

## Database Statistics

After running seeders:

| Entity | Count |
|--------|-------|
| Users | 26 |
| Roles | 6 |
| Permissions | ~20 |
| Store Categories | 15 |
| Stores | 7 |
| Store Hours | 49 |
| Store Verifications | 28 |
| Addresses | 7 |
| Notifications | 40-50 |
| User Profiles | 26 |
| User Preferences | 26 |

## Common Commands

```bash
# Fresh start with seeding
php artisan migrate:fresh --seed

# Rollback last migration
php artisan migrate:rollback

# Check migration status
php artisan migrate:status

# Seed specific seeder
php artisan db:seed --class=UserSeeder

# Clear all data and reseed
php artisan migrate:fresh --seed

# Run tests
php artisan test

# Run specific test
php artisan test --filter=StoreManagementTest
```

## Troubleshooting

### "Access denied for user"
Check your `.env` database credentials.

### "Database does not exist"
Create the database first:
```bash
mysql -u root -p -e "CREATE DATABASE coupony"
```

### "Foreign key constraint fails"
Run migrations in order or use:
```bash
php artisan migrate:fresh --seed
```

### "Class not found"
Clear cache and regenerate autoload:
```bash
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

### "Duplicate entry"
Database already has data. Use fresh migration:
```bash
php artisan migrate:fresh --seed
```

## Production Considerations

⚠️ **Important**: The seeders create test data with default passwords.

For production:

1. **Don't run seeders** - They create test accounts
2. **Change default passwords** - Never use `password` in production
3. **Create admin manually** - Use a secure method
4. **Disable test routes** - Remove development routes
5. **Use strong passwords** - Enforce password policies

## Next Steps

After database setup:

1. ✅ Test admin login at `/api/v1/auth/login`
2. ✅ Verify store management at `/api/v1/admin/stores`
3. ✅ Check API routes at `/api/v1/*`
4. ✅ Run test suite: `php artisan test`
5. ✅ Review API documentation in `API_ROUTES.md`

## Related Documentation

- `database/factories/README.md` - Factory usage guide
- `database/seeders/README.md` - Seeder documentation
- `API_ROUTES.md` - API endpoints documentation
- `tests/README.md` - Testing guide (if exists)

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode: `APP_DEBUG=true` in `.env`
3. Check database connection: `php artisan db:show`
4. Verify migrations: `php artisan migrate:status`
