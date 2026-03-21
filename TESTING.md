# Testing Guide

## Quick Start

Run all tests:

```bash
php artisan test
```

Run with coverage:

```bash
php artisan test --coverage
```

## Test Suites Created

### Unit Tests (tests/Unit/)

1. **AuthenticationServiceTest** - Tests authentication logic
    - Login validation
    - Token generation
    - Logout functionality
    - Token refresh

2. **OtpServiceTest** - Tests OTP functionality
    - OTP generation and sending
    - Code verification
    - Expiration handling
    - Rate limiting
    - Recipient masking

3. **RegisterUserTest** - Tests user registration
    - User creation
    - Profile creation
    - Role assignment
    - Password hashing

4. **CreateStoreTest** - Tests store creation
    - Store creation with valid data
    - Category assignment
    - Address creation
    - Verification documents
    - Default hours creation

5. **NotificationServiceTest** - Tests notification system
    - Single notifications
    - Bulk notifications
    - Channel-specific sending

### Feature Tests (tests/Feature/)

1. **AuthenticationTest** - API authentication endpoints
    - POST /api/v1/auth/login
    - POST /api/v1/auth/logout
    - GET /api/v1/auth/me
    - POST /api/v1/auth/refresh

2. **RegistrationTest** - API registration endpoints
    - POST /api/v1/auth/register
    - Validation rules
    - Profile creation

3. **OtpTest** - API OTP endpoints
    - POST /api/v1/auth/otp/send
    - POST /api/v1/auth/otp/verify
    - POST /api/v1/auth/otp/resend

4. **StoreTest** - API store endpoints
    - POST /api/v1/stores
    - Validation rules
    - File uploads

5. **UserJourneyTest** - End-to-end user flows
    - Complete customer registration journey
    - Complete seller registration and store creation
    - Password reset flow
    - Token refresh flow

## Running Specific Tests

### By Suite

```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature
```

### By File

```bash
php artisan test tests/Unit/AuthenticationServiceTest.php
php artisan test tests/Feature/UserJourneyTest.php
```

### By Method

```bash
php artisan test --filter test_login_with_valid_credentials
php artisan test --filter test_complete_customer_registration_and_login_journey
```

## Test Statistics

Total Tests Created: **50+**

Coverage Areas:

- ✅ Authentication (Login, Logout, Token Management)
- ✅ Registration (User Creation, Validation)
- ✅ OTP (Generation, Verification, Resend)
- ✅ Store Management (Creation, Validation)
- ✅ Notifications (Single, Bulk, Channels)
- ✅ User Journeys (End-to-end flows)

## Configuration

Tests use in-memory SQLite database for speed:

- No database setup required
- Fast execution
- Isolated test environment

Configuration in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Best Practices Implemented

1. ✅ Database refresh between tests
2. ✅ Factory usage for test data
3. ✅ Mocking external services
4. ✅ Descriptive test names
5. ✅ Comprehensive assertions
6. ✅ Edge case coverage
7. ✅ Authentication helpers
8. ✅ Isolated test cases

## Continuous Integration Ready

All tests are ready for CI/CD pipelines:

- Fast execution (in-memory database)
- No external dependencies
- Comprehensive coverage
- Clear failure messages

## Next Steps

To run tests in your CI/CD pipeline, add:

```yaml
# GitHub Actions example
- name: Run Tests
  run: php artisan test --coverage
```

```yaml
# GitLab CI example
test:
    script:
        - php artisan test --coverage
```

## Troubleshooting

### Tests failing with database errors

Ensure you have SQLite extension installed:

```bash
php -m | grep sqlite
```

### Tests failing with permission errors

Check storage permissions:

```bash
chmod -R 775 storage bootstrap/cache
```

### Mock errors

Clear cache before running tests:

```bash
php artisan config:clear
php artisan cache:clear
```

## Documentation

For detailed test documentation, see `tests/README.md`
