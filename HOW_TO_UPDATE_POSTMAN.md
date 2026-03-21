# How to Update Postman Collection

## Quick Summary

You need to add 4 new password reset endpoints and update 2 store endpoints.

---

## Option 1: Manual Update (Recommended)

### Step 1: Add Password Reset Endpoints

1. Open Postman
2. Navigate to your "Coupony API" collection
3. Find the "Authentication" folder
4. Add these 4 new requests:

#### A. Forgot Password
- **Name:** Forgot Password
- **Method:** POST
- **URL:** `{{baseUrl}}/api/v1/auth/password/forgot`
- **Headers:**
  - Accept: application/json
  - Content-Type: application/json
- **Body (raw JSON):**
```json
{
  "email": "user@example.com"
}
```

#### B. Verify Password Reset OTP
- **Name:** Verify Password Reset OTP
- **Method:** POST
- **URL:** `{{baseUrl}}/api/v1/auth/password/verify-otp`
- **Headers:**
  - Accept: application/json
  - Content-Type: application/json
- **Body (raw JSON):**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```
- **Tests Tab:** Add this script to auto-save the reset token:
```javascript
if (pm.response.code === 200) {
    var jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.reset_token) {
        pm.collectionVariables.set('reset_token', jsonData.data.reset_token);
    }
}
```

#### C. Reset Password
- **Name:** Reset Password
- **Method:** POST
- **URL:** `{{baseUrl}}/api/v1/auth/password/reset`
- **Headers:**
  - Accept: application/json
  - Content-Type: application/json
- **Body (raw JSON):**
```json
{
  "reset_token": "{{reset_token}}",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

#### D. Resend Password Reset OTP
- **Name:** Resend Password Reset OTP
- **Method:** POST
- **URL:** `{{baseUrl}}/api/v1/auth/password/resend-otp`
- **Headers:**
  - Accept: application/json
  - Content-Type: application/json
- **Body (raw JSON):**
```json
{
  "email": "user@example.com"
}
```

### Step 2: Update Store Endpoints

Find these endpoints in the "Stores" folder and update them:

#### A. Create Store
- **OLD URL:** `{{baseUrl}}/api/v1/store/create`
- **NEW URL:** `{{baseUrl}}/api/v1/stores`
- Keep everything else the same

#### B. Get My Stores
- **OLD URL:** `{{baseUrl}}/api/v1/stores/my-stores`
- **NEW URL:** `{{baseUrl}}/api/v1/stores`
- Optionally rename to "List Stores" or "Get My Stores (Index)"

### Step 3: Add Collection Variable

1. Click on your collection name
2. Go to "Variables" tab
3. Add a new variable:
   - **Variable:** reset_token
   - **Initial Value:** (leave empty)
   - **Current Value:** (leave empty)
4. Save

---

## Option 2: Import JSON Snippet

### For Password Reset Endpoints:

1. Open the file `postman_password_reset_endpoints.json`
2. Copy the entire content
3. In Postman, right-click on "Authentication" folder
4. Select "Add Request"
5. For each endpoint in the JSON:
   - Create a new request
   - Manually copy the details from the JSON

---

## Option 3: Re-import Entire Collection

⚠️ **Warning:** This will replace your entire collection. Make sure to export a backup first!

1. In Postman, right-click on "Coupony API" collection
2. Select "Export"
3. Save as backup
4. Delete the old collection
5. Import the updated `postman_collection.json` file

---

## Testing the Updates

### Test Password Reset Flow:

1. **Forgot Password**
   ```
   POST {{baseUrl}}/api/v1/auth/password/forgot
   Body: { "email": "seller1@example.com" }
   ```
   Expected: Success message, check email for code

2. **Verify OTP**
   ```
   POST {{baseUrl}}/api/v1/auth/password/verify-otp
   Body: { "email": "seller1@example.com", "code": "123456" }
   ```
   Expected: Success with reset_token (auto-saved)

3. **Reset Password**
   ```
   POST {{baseUrl}}/api/v1/auth/password/reset
   Body: { 
     "reset_token": "{{reset_token}}", 
     "password": "NewPassword123!",
     "password_confirmation": "NewPassword123!"
   }
   ```
   Expected: Success message

4. **Login with New Password**
   ```
   POST {{baseUrl}}/api/v1/auth/login
   Body: { "email": "seller1@example.com", "password": "NewPassword123!" }
   ```
   Expected: Login successful with new token

### Test Updated Store Endpoints:

1. **Create Store**
   ```
   POST {{baseUrl}}/api/v1/stores
   (with all required fields)
   ```
   Expected: Store created successfully

2. **List Stores**
   ```
   GET {{baseUrl}}/api/v1/stores
   ```
   Expected: List of user's stores

---

## Verification Checklist

After updating, verify these items:

- [ ] All 4 password reset endpoints are in Authentication folder
- [ ] Create Store URL changed to `/api/v1/stores`
- [ ] Get My Stores URL changed to `/api/v1/stores`
- [ ] Collection variable `reset_token` exists
- [ ] Test script on Verify OTP endpoint saves reset_token
- [ ] All endpoints use `{{baseUrl}}` variable
- [ ] All endpoints have proper headers
- [ ] Body content is set to "raw" and "JSON"

---

## Troubleshooting

### Issue: "reset_token not found"
**Solution:** Make sure you ran the "Verify Password Reset OTP" endpoint first and it returned success. Check the Tests tab has the script to save the token.

### Issue: "Invalid reset token"
**Solution:** Reset tokens expire after 15 minutes. Request a new password reset and verify OTP again.

### Issue: "Store endpoints return 404"
**Solution:** Make sure you updated the URLs correctly. The new URLs don't have `/store/create` or `/my-stores`.

### Issue: "Password validation failed"
**Solution:** Password must be at least 8 characters with uppercase, lowercase, numbers, and symbols.

---

## Quick Reference

### New Endpoints:
```
POST /api/v1/auth/password/forgot
POST /api/v1/auth/password/verify-otp
POST /api/v1/auth/password/reset
POST /api/v1/auth/password/resend-otp
```

### Updated Endpoints:
```
POST /api/v1/stores (was /api/v1/store/create)
GET /api/v1/stores (was /api/v1/stores/my-stores)
```

### New Variable:
```
reset_token (auto-populated from verify OTP response)
```

---

## Need Help?

If you encounter issues:
1. Check the server is running: `php artisan serve`
2. Verify routes exist: `php artisan route:list --path=password`
3. Check logs: `storage/logs/laravel.log`
4. Ensure database is seeded with test users
5. Verify email service is configured (for OTP delivery)

---

## Next Steps

After updating Postman:
1. Test the complete password reset flow
2. Test the updated store endpoints
3. Update any API documentation
4. Share the updated collection with your team
5. Update frontend integration if needed
