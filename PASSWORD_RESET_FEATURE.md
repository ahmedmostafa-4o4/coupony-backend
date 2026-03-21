# Password Reset with OTP Feature

## Overview
Implemented a secure password reset flow using OTP (One-Time Password) verification sent via email.

---

## Flow

### 1. Request Password Reset
User requests a password reset by providing their email address.

**Endpoint:** `POST /api/v1/auth/password/forgot`

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response (Success):**
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

**Security Note:** The endpoint returns success even if the email doesn't exist to prevent email enumeration attacks.

---

### 2. Verify OTP Code
User enters the 6-digit code received via email.

**Endpoint:** `POST /api/v1/auth/password/verify-otp`

**Request:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Verification code confirmed. You can now reset your password.",
  "data": {
    "reset_token": "a1b2c3d4e5f6...",
    "expires_in_minutes": 15
  }
}
```

**Response (Failed - Invalid Code):**
```json
{
  "success": false,
  "message": "Invalid or incorrect verification code.",
  "error_code": "OTP_VERIFICATION_FAILED",
  "remaining_attempts": 2
}
```

**Response (Failed - Expired):**
```json
{
  "success": false,
  "message": "Verification code has expired.",
  "error_code": "OTP_EXPIRED"
}
```

**Response (Failed - Too Many Attempts):**
```json
{
  "success": false,
  "message": "Too many failed attempts. Please request a new code.",
  "error_code": "OTP_BLOCKED",
  "retry_after": 300
}
```

---

### 3. Reset Password
User sets a new password using the reset token.

**Endpoint:** `POST /api/v1/auth/password/reset`

**Request:**
```json
{
  "reset_token": "a1b2c3d4e5f6...",
  "password": "NewSecurePassword123!",
  "password_confirmation": "NewSecurePassword123!"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Password reset successfully. You can now login with your new password."
}
```

**Response (Failed - Invalid Token):**
```json
{
  "success": false,
  "message": "Invalid or expired reset token. Please request a new password reset."
}
```

---

### 4. Resend OTP (Optional)
If the user didn't receive the code or it expired.

**Endpoint:** `POST /api/v1/auth/password/resend-otp`

**Request:**
```json
{
  "email": "user@example.com"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Password reset code resent successfully.",
  "data": {
    "expires_at": "2024-03-06T18:40:00.000000Z"
  }
}
```

**Response (Failed - Rate Limited):**
```json
{
  "success": false,
  "message": "Please wait before requesting another code.",
  "error_code": "RATE_LIMITED",
  "retry_after": 60
}
```

---

## Security Features

### 1. **OTP Expiration**
- OTPs expire after 10 minutes
- Reset tokens expire after 15 minutes

### 2. **Rate Limiting**
- Maximum 3 verification attempts per OTP
- Cooldown period after failed attempts
- Resend throttling to prevent spam

### 3. **Token Invalidation**
- All existing authentication tokens are revoked after password reset
- Reset tokens are single-use and deleted after use

### 4. **Email Enumeration Protection**
- Forgot password endpoint doesn't reveal if email exists
- Consistent response times regardless of email existence

### 5. **Password Requirements**
- Minimum 8 characters
- Must contain uppercase and lowercase letters
- Must contain numbers
- Must contain symbols
- Checked against compromised password database

### 6. **Secure Token Generation**
- Reset tokens use cryptographically secure random bytes
- Tokens are 64 characters long (32 bytes hex-encoded)

---

## Files Created

### Controllers
- `app/Application/Http/Controllers/API/V1/Auth/PasswordResetController.php`

### Form Requests
- `app/Application/Http/Requests/ForgotPasswordRequest.php`
- `app/Application/Http/Requests/VerifyPasswordResetOtpRequest.php`
- `app/Application/Http/Requests/ResetPasswordRequest.php`

### Routes
Added to `routes/api.php`:
```php
Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
Route::post('/password/verify-otp', [PasswordResetController::class, 'verifyOtp']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
Route::post('/password/resend-otp', [PasswordResetController::class, 'resendOtp']);
```

---

## Database Requirements

### Existing Tables Used
- `users` - User accounts
- `otps` - OTP storage and verification

### Cache Usage
- Reset tokens stored in cache with 15-minute TTL
- Key format: `password_reset:{token}`

---

## Email Template

The OTP is sent using the existing `OtpService` which should have an email template for password reset. Ensure the email template includes:

- Clear subject line: "Password Reset Code"
- 6-digit OTP code prominently displayed
- Expiration time (10 minutes)
- Security warning about not sharing the code
- Link to support if user didn't request reset

---

## Testing Checklist

- [ ] Request password reset with valid email
- [ ] Request password reset with invalid email (should not reveal)
- [ ] Verify OTP with correct code
- [ ] Verify OTP with incorrect code (check remaining attempts)
- [ ] Verify OTP with expired code
- [ ] Verify OTP after max attempts (should be blocked)
- [ ] Reset password with valid token
- [ ] Reset password with expired token
- [ ] Reset password with invalid token
- [ ] Verify old tokens are invalidated after reset
- [ ] Test password validation rules
- [ ] Resend OTP functionality
- [ ] Rate limiting on resend
- [ ] Email delivery

---

## Frontend Integration

### Step 1: Forgot Password Form
```javascript
const forgotPassword = async (email) => {
  const response = await fetch('/api/v1/auth/password/forgot', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email })
  });
  return response.json();
};
```

### Step 2: Verify OTP Form
```javascript
const verifyOtp = async (email, code) => {
  const response = await fetch('/api/v1/auth/password/verify-otp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code })
  });
  const data = await response.json();
  if (data.success) {
    // Store reset_token for next step
    localStorage.setItem('reset_token', data.data.reset_token);
  }
  return data;
};
```

### Step 3: Reset Password Form
```javascript
const resetPassword = async (password, passwordConfirmation) => {
  const resetToken = localStorage.getItem('reset_token');
  const response = await fetch('/api/v1/auth/password/reset', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      reset_token: resetToken,
      password,
      password_confirmation: passwordConfirmation
    })
  });
  const data = await response.json();
  if (data.success) {
    localStorage.removeItem('reset_token');
    // Redirect to login
  }
  return data;
};
```

---

## Error Codes

| Code | Description | HTTP Status |
|------|-------------|-------------|
| `OTP_VERIFICATION_FAILED` | Invalid or incorrect code | 422 |
| `OTP_EXPIRED` | Code has expired | 410 |
| `OTP_BLOCKED` | Too many failed attempts | 429 |
| `RATE_LIMITED` | Too many resend requests | 429 |

---

## Logging

All password reset operations are logged with:
- User ID and email
- Timestamp
- Action (sent, verified, reset, failed)
- IP address (from request)

Logs can be found in `storage/logs/laravel.log`

---

## Configuration

### OTP Settings
Configure in `config/otp.php` (if exists) or in the `OtpService`:
- OTP length: 6 digits
- OTP expiration: 10 minutes
- Max attempts: 3
- Cooldown period: 5 minutes

### Reset Token Settings
- Token length: 64 characters
- Token expiration: 15 minutes
- Storage: Cache (Redis/Memcached recommended for production)

---

## Production Considerations

1. **Email Delivery**: Ensure email service is properly configured (SMTP, SendGrid, etc.)
2. **Cache Driver**: Use Redis or Memcached for reset token storage
3. **Rate Limiting**: Configure appropriate rate limits in production
4. **Monitoring**: Set up alerts for failed reset attempts
5. **Audit Logging**: Log all password reset activities
6. **HTTPS**: Ensure all endpoints are served over HTTPS
7. **CORS**: Configure CORS properly for frontend integration

---

## Support

For issues or questions:
1. Check logs in `storage/logs/laravel.log`
2. Verify email service is working
3. Check cache driver is properly configured
4. Ensure OTP service is functioning correctly
