# Employee Workplaces API Documentation

This document provides details on the new endpoints and modifications made to support the **Employee Workplaces Separation** and **Dynamic Dashboard** feature.

## 1. User Profile Modifications

### `GET /api/v1/me`
Retrieves the authenticated user's profile. A new boolean field `is_employee` has been added.

**Headers:**
- `Authorization`: `Bearer {token}`

**Response (Partial snippet):**
```json
{
  "data": {
    "id": "32c77cd7-...",
    "email": "m62173511@gmail.com",
    "roles": ["customer", "store_employee"],
    "is_store_owner": false,
    "is_employee": true,
    ...
  }
}
```

*Note:* `is_employee` evaluates to `true` if the user is registered as an employee in any store (has a record in `store_employees`). This can be used by the frontend to immediately show the "Workspace" button without filtering through the user's roles.

---

## 2. Workplaces Endpoint

### `GET /api/v1/me/workplaces`
Fetches all the workplaces (stores) where the authenticated user is currently employed, along with their roles and permissions in each store.

**Headers:**
- `Authorization`: `Bearer {token}`
- `Accept-Language`: `ar` (Optional, for localized responses)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "store_id": "071a0f04-8ef1-46c9-8b10-d5e2d66d7679",
      "store_name": "Alex Style House",
      "logo_url": "https://api.coupony.shop/...",
      "role": "cashier",
      "permissions": [
        "store.claims.view",
        "store.claims.redeem"
      ],
      "address_id": null,
      "joined_at": "2026-06-02T19:56:16.000000Z"
    }
  ]
}
```

**Fields Description:**
- `store_id`: The UUID of the store. Required to be passed when making subsequent API requests that concern this specific store (e.g., scanning a QR code or managing orders).
- `store_name`: The name of the store.
- `logo_url`: The URL to the store's logo.
- `role`: The employee's role inside the store.
- `permissions`: An array of permissions the employee has. Used to draw the frontend dashboard dynamically.
- `address_id`: Nullable integer referencing a specific address, if applicable.
- `joined_at`: ISO-8601 formatted timestamp of when the employee joined the store.
