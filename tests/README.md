# Test Suite Documentation

This directory contains comprehensive unit and feature tests for the application.

## Test Structure

```
tests/
├── Feature/           # Integration/API tests
│   ├── AuthenticationTest.php
│   ├── RegistrationTest.php
│   ├── OtpTest.php
│   └── StoreTest.php
├── Unit/             # Unit tests for services and actions
│   ├── AuthenticationServiceTest.php
│   ├── OtpServiceTest.php
│   ├── RegisterUserTest.php
│   ├── CreateStoreTest.php
│   └── NotificationServiceTest.php
└── TestCase.php      # Base test case with helper methods
```

## Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature
```

### Run Specific Test File

```bash
php artisan test tests/Unit/AuthenticationServiceTest.php
```

### Run Specific Test Method

```bash
php artisan test --filter test_login_with_valid_credentials
```

### Run Tests with Coverage

```bash
php artisan test --coverage
```

## Test Coverage

### Authentication Tests

- ✅ Login with valid credentials
- ✅ Login with invalid credentials
- ✅ Login with suspended account
- ✅ Login sends OTP for unverified users
- ✅ Logout functionality
- ✅ Token refresh
- ✅ User profile retrieval

### Registration Tests

- ✅ User registration with valid data
- ✅ Email validation
- ✅ Password confirmation
- ✅ Duplicate email prevention
- ✅ Profile creation
- ✅ Role assignment

### OTP Tests

- ✅ OTP generation and sending
- ✅ OTP verification with correct code
- ✅ OTP verification with incorrect code
- ✅ OTP expiration
- ✅ OTP resend functionality
- ✅ Rate limiting
- ✅ Email/phone masking

### Store Tests

- ✅ Store creation with valid data
- ✅ Authentication requirement
- ✅ Required field validation
- ✅ Category assignment
- ✅ Address creation
- ✅ Verification document upload
- ✅ Default hours creation
- ✅ Role assignment

### Notification Tests

- ✅ Notification creation
- ✅ Bulk notifications
- ✅ Channel-specific sending
- ✅ Reference tracking
- ✅ Additional data storage

## Test Configuration

### Environment Setup

Tests use a separate database configuration. Ensure your `.env.testing` file is configured:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

### Test Data

- Uses Laravel factories for generating test data
- Mocks external services (email, SMS, etc.)
- Uses in-memory SQLite for fast test execution

## Helper Methods

The base `TestCase` class provides helper methods:

### `authenticateUser($user = null)`

Creates and returns a bearer token for authentication.

```php
$token = $this->authenticateUser();
```

### `authenticatedJson($method, $uri, $data = [], $user = null)`

Makes an authenticated JSON request.

```php
$response = $this->authenticatedJson('POST', '/api/v1/stores', $data);
```

## Best Practices

1. **Use Factories**: Always use factories for creating test data
2. **Mock External Services**: Mock email, SMS, and other external services
3. **Test Edge Cases**: Include tests for validation, errors, and edge cases
4. **Keep Tests Isolated**: Each test should be independent
5. **Use Descriptive Names**: Test method names should clearly describe what they test
6. **Clean Up**: Use `RefreshDatabase` trait to reset database between tests

## Adding New Tests

When adding new features, ensure you add corresponding tests:

1. Create unit tests for services and actions in `tests/Unit/`
2. Create feature tests for API endpoints in `tests/Feature/`
3. Update this README with new test coverage information

## Continuous Integration

Tests are automatically run on:

- Pull requests
- Commits to main branch
- Before deployment

Ensure all tests pass before merging code.
