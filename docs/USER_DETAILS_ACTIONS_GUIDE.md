# User Details Actions Guide (Frontend Integration)

This document outlines how the frontend dashboard should implement the primary User Management actions available on a User Details page.

All endpoints require the user to be authenticated with an Admin Bearer Token.

---

## 1. Edit User Details (Including Role)

Updates a user's core profile, contact info, language preferences, and/or their role.

- **Method:** `PATCH` (or `PUT`)
- **Path:** `/api/v1/admin/users/{user_id}`

**Payload Example:**
You can send any combination of these fields. They are all optional, meaning you only need to send what is actually changing.
```json
{
    "first_name": "Jane",
    "last_name": "Doe",
    "email": "jane@example.com",
    "phone_number": "+1234567890",
    "role": "seller",
    "status": "active",
    "language": "en",
    "timezone": "Africa/Cairo",
    "gender": "female",
    "date_of_birth": "1995-05-15",
    "bio": "A passionate seller."
}
```

---

## 2. Force Change Password

Allows an Admin to forcefully update/reset a user's password. 
**Security Note:** Calling this endpoint will automatically log the user out of all their active devices immediately.

- **Method:** `PATCH`
- **Path:** `/api/v1/admin/users/{user_id}/password`

**Payload Example:**
```json
{
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
}
```
*Note: `password` must be at least 8 characters long and match `password_confirmation`.*

---

## 3. Delete Account

Soft deletes the user and irreversibly destroys their active sessions and personal access tokens. If the user is a `seller`, it will also recursively delete their stores, products, and verified documents to clean up the platform.

- **Method:** `DELETE`
- **Path:** `/api/v1/admin/users/{user_id}`

*No JSON payload is required.*

**Response Example:**
```json
{
    "message": "User deleted successfully."
}
```
*Note: An admin cannot delete their own account (returns `400 Bad Request`).*
