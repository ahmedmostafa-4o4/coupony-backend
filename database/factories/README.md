# Database Factories

This directory contains all model factories for the Coupony application. Factories are used to generate fake data for testing and seeding.

## Available Factories

### User & Profile Factories

#### UserFactory
Creates user instances with realistic data.

```php
User::factory()->create();
User::factory()->count(10)->create();

// With specific attributes
User::factory()->create([
    'email' => 'test@example.com',
    'status' => 'active',
]);

// States
User::factory()->verified()->create();
User::factory()->unverified()->create();
User::factory()->suspended()->create();
```

#### ProfileFactory
Creates user profile instances.

```php
Profile::factory()->create();
Profile::factory()->for($user)->create();
```

#### UserPreferenceFactory
Creates user preference instances.

```php
UserPreference::factory()->create();
UserPreference::factory()->allNotificationsEnabled()->create();
UserPreference::factory()->allNotificationsDisabled()->create();
```

### Store Factories

#### StoreFactory
Creates store instances with different statuses.

```php
Store::factory()->create();

// States
Store::factory()->active()->create();
Store::factory()->pending()->create();
Store::factory()->rejected()->create();
Store::factory()->suspended()->create();
```

#### StoreCategoryFactory
Creates store category instances.

```php
StoreCategory::factory()->create();
StoreCategory::factory()->count(5)->create();
```

#### StoreHoursFactory
Creates store hours instances.

```php
StoreHours::factory()->create();
StoreHours::factory()->for($store)->create();

// States
StoreHours::factory()->closed()->create();
StoreHours::factory()->weekend()->create();
```

#### StoreVerificationFactory
Creates store verification document instances.

```php
StoreVerification::factory()->create();

// States
StoreVerification::factory()->approved()->create();
StoreVerification::factory()->rejected()->create();
```

### Address Factory

#### AddressFactory
Creates address instances with coordinates.

```php
Address::factory()->create();
Address::factory()->count(3)->create();
```

### Notification & OTP Factories

#### NotificationFactory
Creates notification instances.

```php
Notification::factory()->create();

// States
Notification::factory()->sent()->create();
Notification::factory()->read()->create();
Notification::factory()->failed()->create();
```

#### OtpFactory
Creates OTP instances.

```php
Otp::factory()->create();

// States
Otp::factory()->verified()->create();
Otp::factory()->expired()->create();
Otp::factory()->blocked()->create();
```

## Usage Examples

### Creating a Complete User with Relations

```php
$user = User::factory()
    ->has(Profile::factory())
    ->has(UserPreference::factory())
    ->create();
```

### Creating a Store with All Relations

```php
$store = Store::factory()
    ->for(User::factory()->create())
    ->has(StoreHours::factory()->count(7))
    ->has(StoreVerification::factory()->count(4))
    ->hasAttached(StoreCategory::factory()->count(3))
    ->create();
```

### Creating Multiple Users with Different Roles

```php
// Admin
$admin = User::factory()->create();
$admin->assignRole('admin');

// Sellers
$sellers = User::factory()->count(5)->create()->each(function ($user) {
    $user->assignRole('seller');
});

// Customers
$customers = User::factory()->count(20)->create()->each(function ($user) {
    $user->assignRole('customer');
});
```

### Creating Stores with Different Statuses

```php
// Active stores
Store::factory()->active()->count(10)->create();

// Pending stores
Store::factory()->pending()->count(5)->create();

// Rejected stores
Store::factory()->rejected()->count(2)->create();
```

## Factory States

### User States
- `verified()` - Email and phone verified
- `unverified()` - Not verified
- `suspended()` - Account suspended

### Store States
- `active()` - Approved and active
- `pending()` - Awaiting approval
- `rejected()` - Rejected by admin
- `suspended()` - Suspended by admin

### StoreHours States
- `closed()` - Store is closed
- `weekend()` - Weekend hours (Saturday/Sunday)

### StoreVerification States
- `approved()` - Document approved
- `rejected()` - Document rejected

### Notification States
- `sent()` - Notification sent
- `read()` - Notification read by user
- `failed()` - Notification failed to send

### Otp States
- `verified()` - OTP verified
- `expired()` - OTP expired
- `blocked()` - OTP blocked due to max attempts

## Testing with Factories

### In Feature Tests

```php
public function test_user_can_create_store()
{
    $user = User::factory()->create();
    $category = StoreCategory::factory()->create();
    
    $response = $this->actingAs($user)
        ->postJson('/api/v1/store/create', [
            'name' => 'Test Store',
            'categories' => [$category->id],
            // ... other data
        ]);
    
    $response->assertStatus(201);
}
```

### In Unit Tests

```php
public function test_store_can_be_approved()
{
    $store = Store::factory()->pending()->create();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $store->approve($admin);
    
    $this->assertEquals('active', $store->status);
}
```

## Best Practices

1. **Use Factories in Tests**: Always use factories instead of manually creating models in tests
2. **Use States**: Leverage factory states for different scenarios
3. **Relationships**: Use `for()` and `has()` methods to create related models
4. **Realistic Data**: Factories use Faker to generate realistic data
5. **Minimal Data**: Only specify required attributes, let factory handle the rest

## Faker Methods Used

Common Faker methods used in factories:

- `fake()->name()` - Full name
- `fake()->firstName()` - First name
- `fake()->lastName()` - Last name
- `fake()->email()` - Email address
- `fake()->phoneNumber()` - Phone number
- `fake()->company()` - Company name
- `fake()->address()` - Full address
- `fake()->city()` - City name
- `fake()->country()` - Country name
- `fake()->latitude()` - Latitude coordinate
- `fake()->longitude()` - Longitude coordinate
- `fake()->paragraph()` - Paragraph of text
- `fake()->sentence()` - Sentence
- `fake()->boolean()` - Random boolean
- `fake()->randomElement([])` - Random element from array
- `fake()->numberBetween(min, max)` - Random number
- `fake()->randomFloat(decimals, min, max)` - Random float

## Adding New Factories

To create a new factory:

```bash
php artisan make:factory ModelNameFactory
```

Then implement the `definition()` method and add any states needed.

## Related Documentation

- [Laravel Factories Documentation](https://laravel.com/docs/eloquent-factories)
- [Faker Documentation](https://fakerphp.github.io/)
- See `database/seeders/README.md` for seeder documentation
