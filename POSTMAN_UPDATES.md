# Postman Collection Updates

## New Endpoints Added

### Password Reset Flow

Add these endpoints to the "Authentication" folder in your Postman collection:

#### 1. Forgot Password
```
POST {{baseUrl}}/api/v1/auth/password/forgot

Headers:
- Accept: application/json
- Content-Type: application/json

Body (raw JSON):
{
  "email": "user@example.com"
}
```

#### 2. Verify Password Reset OTP
```
POST {{baseUrl}}/api/v1/auth/password/verify-otp

Headers:
- Accept: application/json
- Content-Type: application/json

Body (raw JSON):
{
  "email": "user@example.com",
  "code": "123456"
}

Test Script (to save reset_token):
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.reset_token) {
        pm.collectionVariables.set('reset_token', jsonData.data.reset_token);
    }
}
```

#### 3. Reset Password
```
POST {{baseUrl}}/api/v1/auth/password/reset

Headers:
- Accept: application/json
- Content-Type: application/json

Body (raw JSON):
{
  "reset_token": "{{reset_token}}",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

#### 4. Resend Password Reset OTP
```
POST {{baseUrl}}/api/v1/auth/password/resend-otp

Headers:
- Accept: application/json
- Content-Type: application/json

Body (raw JSON):
{
  "email": "user@example.com"
}
```

---

## Updated Endpoints

### Store Endpoints

#### Update: Create Store
**OLD:** `POST {{baseUrl}}/api/v1/store/create`  
**NEW:** `POST {{baseUrl}}/api/v1/stores`

#### Update: Get My Stores
**OLD:** `GET {{baseUrl}}/api/v1/stores/my-stores`  
**NEW:** `GET {{baseUrl}}/api/v1/stores`

Rename this endpoint to "Get My Stores (Index)" or just "List Stores"

---

## Collection Variables

Add this new variable to your collection:

| Variable | Initial Value | Current Value |
|----------|---------------|---------------|
| reset_token | (empty) | (empty) |

This variable will be automatically populated when you verify the OTP in step 2.

---

## Complete Password Reset Test Flow

1. **Forgot Password**
   - Send request with email
   - Check email for 6-digit code
   - Note the expiration time

2. **Verify OTP**
   - Send request with email and code
   - If successful, `reset_token` is saved automatically
   - Token is valid for 15 minutes

3. **Reset Password**
   - Send request with `{{reset_token}}` and new password
   - Password must meet requirements:
     - Minimum 8 characters
     - Mixed case (upper and lower)
     - Numbers
     - Symbols

4. **Login with New Password**
   - Use the Login endpoint with new credentials
   - Old tokens are invalidated

---

## Response Examples

### Forgot Password - Success
```json
{
  "success": true,
  "message": "Password reset code sent to your email.",
  "data": {
    "expires_at": "2024-03-06T18:30:00.000000Z",
    "expires_in_minutes": 10
  }
}
```

### Verify OTP - Success
```json
{
  "success": true,
  "message": "Verification code confirmed. You can now reset your password.",
  "data": {
    "reset_token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2",
    "expires_in_minutes": 15
  }
}
```

### Verify OTP - Failed (Invalid Code)
```json
{
  "success": false,
  "message": "Invalid or incorrect verification code.",
  "error_code": "OTP_VERIFICATION_FAILED",
  "remaining_attempts": 2
}
```

### Verify OTP - Failed (Expired)
```json
{
  "success": false,
  "message": "Verification code has expired.",
  "error_code": "OTP_EXPIRED"
}
```

### Reset Password - Success
```json
{
  "success": true,
  "message": "Password reset successfully. You can now login with your new password."
}
```

### Reset Password - Failed (Invalid Token)
```json
{
  "success": false,
  "message": "Invalid or expired reset token. Please request a new password reset."
}
```

---

## Testing Checklist

- [ ] Forgot Password with valid email
- [ ] Forgot Password with invalid email (should still return success)
- [ ] Verify OTP with correct code
- [ ] Verify OTP with incorrect code (check remaining attempts)
- [ ] Verify OTP with expired code
- [ ] Reset Password with valid token
- [ ] Reset Password with expired token
- [ ] Reset Password with weak password (should fail validation)
- [ ] Resend OTP
- [ ] Login with new password after reset
- [ ] Verify old tokens are invalidated

---

## Import Instructions

### Manual Import

1. Open Postman
2. Go to your "Coupony API" collection
3. Find the "Authentication" folder
4. Click "Add Request" for each new endpoint
5. Copy the details from above

### Automated Import (if using JSON)

1. Export your current collection as backup
2. Use the provided PowerShell script or manually edit the JSON
3. Import the updated collection
4. Verify all endpoints are present

---

## Notes

- All password reset endpoints are public (no authentication required)
- The `reset_token` is automatically captured from the verify OTP response
- Reset tokens expire after 15 minutes
- OTP codes expire after 10 minutes
- Maximum 3 verification attempts per OTP
- All existing user sessions are invalidated after password reset
