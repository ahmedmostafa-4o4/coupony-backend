# API Routes Documentation

## Base URL
```
/api/v1
```

---

## Authentication Routes
**Prefix:** `/auth`

### Public Routes

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | auth.register | Register new user |
| POST | `/auth/login` | auth.login | User login |
| POST | `/auth/refresh` | auth.refresh | Refresh access token |
| POST | `/auth/otp/send` | otp.send | Send OTP code |
| POST | `/auth/otp/verify` | otp.verify | Verify OTP code |
| POST | `/auth/otp/resend` | otp.resend | Resend OTP code |

### Protected Routes (Requires Authentication)

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| POST | `/auth/logout` | auth.logout | User logout |
| GET | `/auth/me` | auth.me | Get current user profile |

---

## Store Routes

### Public Routes

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/store-categories` | userStoreCategory.index | Get all store categories |

### Protected Routes (Requires Authentication)

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| POST | `/store/create` | store.create | Create new store |

---

## Onboarding Routes
**Requires Authentication**

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| POST | `/on-boarding/customer` | onBoarding.customer | Complete customer onboarding |

---

## Contact Us & Notify Me Routes

### Public Routes

| Method | Endpoint | Name | Description | Middleware |
|--------|----------|------|-------------|------------|
| POST | `/contact-us/seller` | contactUs.seller | Submit seller contact form | ContactUsThrottle |
| POST | `/contact-us/customer` | contactUs.customer | Submit customer contact form | ContactUsThrottle |
| POST | `/notify-me/submit` | notifyMe.submit | Submit notify me request | ContactUsThrottle |

---

## Admin Routes
**Prefix:** `/admin`  
**Middleware:** `auth:sanctum`, `role:admin`

### Dashboard & Analytics

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/admin/dashboard/overview` | dashboard.overview | Get aggregated platform KPIs, financial stats, and pending operational tasks |

### Admin Registration

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| POST | `/admin/register` | admin.register | Register new admin (Public) |

### User Management
**Prefix:** `/admin/users`

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/admin/users` | users.index | List all users (with filters) |
| GET | `/admin/users/statistics` | users.statistics | Get user counts and statistics |
| GET | `/admin/users/{user}` | users.show | Get user details (including sessions) |
| PUT | `/admin/users/{user}` | users.update | Update user profile and roles |
| PATCH | `/admin/users/{user}/status` | users.status | Update user status (active/suspended) |
| DELETE | `/admin/users/{user}` | users.destroy | Delete user and all associated data |
| DELETE | `/admin/users/{user}/sessions` | users.sessions.revoke_all | Revoke all active sessions and tokens for user |
| DELETE | `/admin/users/{user}/sessions/{session}` | users.sessions.revoke | Revoke a specific active session or token |

### Store Categories Management
**Prefix:** `/admin/store-category`

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/admin/store-category` | storeCategory.index | List all store categories |
| POST | `/admin/store-category` | storeCategory.store | Create new store category |
| PUT | `/admin/store-category/{category}` | storeCategory.update | Update store category |
| DELETE | `/admin/store-category/{category}` | storeCategory.destroy | Delete store category |

### Store Management
**Prefix:** `/admin/stores`

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/admin/stores` | admin.stores.index | List all stores with filters |
| GET | `/admin/stores/pending` | admin.stores.pending | List pending stores |
| GET | `/admin/stores/statistics` | admin.stores.statistics | Get store statistics |
| GET | `/admin/stores/{store}` | admin.stores.show | Get store details |
| POST | `/admin/stores/{store}/approve` | admin.stores.approve | Approve store |
| POST | `/admin/stores/{store}/reject` | admin.stores.reject | Reject store |

### Contact Us Management
**Prefix:** `/admin/contact-us`

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/admin/contact-us/customers` | contactUs.get.customers | List customer contact submissions |
| GET | `/admin/contact-us/sellers` | contactUs.get.sellers | List seller contact submissions |

### Notify Me Management
**Prefix:** `/admin/notify-me`

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/admin/notify-me/list` | notifyMe.list | List notify me requests |
| POST | `/admin/notify-me/notify-all` | notifyMe.notifyAll | Send notifications to all |

---

## Development/Testing Routes
**Note:** Only available in `local` and `development` environments

| Method | Endpoint | Name | Description |
|--------|----------|------|-------------|
| GET | `/test-mail` | test.mail | Test email sending |
| GET | `/mail-check` | test.mailCheck | Check mail configuration |

---

## Route Organization

The routes are organized into the following groups:

1. **Authentication Routes** - User registration, login, OTP management
2. **Store Routes** - Store creation and categories
3. **Onboarding Routes** - Customer onboarding process
4. **Contact & Notify Routes** - Contact forms and notification requests
5. **Admin Routes** - Administrative functions (requires admin role)
   - Store Categories Management
   - Store Management (approval/rejection)
   - Contact Us Management
   - Notify Me Management
6. **Development Routes** - Testing and debugging (only in dev environments)

---

## Middleware

- `auth:sanctum` - Requires authentication via Laravel Sanctum
- `role:admin` - Requires admin role (uses Spatie Permission package)
- `ContactUsThrottle` - Rate limiting for contact forms

---

## Response Format

All API responses follow a consistent format:

### Success Response
```json
{
    "message": "Success message",
    "data": {
        // Response data
    }
}
```

### Error Response
```json
{
    "message": "Error message",
    "error": "Error details",
    "error_code": "ERROR_CODE"
}
```

### Validation Error Response
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": [
            "Error message"
        ]
    }
}
```

---

## Authentication

Most protected routes require a Bearer token in the Authorization header:

```
Authorization: Bearer {access_token}
```

Tokens are obtained through the login endpoint and can be refreshed using the refresh endpoint.
