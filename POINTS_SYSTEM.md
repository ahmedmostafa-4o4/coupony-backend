# Points System

## Overview

The points system tracks customer/user balances, store/seller balances, and immutable transaction history for every balance change. Admin mutations are audited with the acting admin, reason, note, and before/after balances. Successful offer redemption automatically awards configured points to both the claiming user and the store.

Default redemption awards are configured in `config/points.php`:

```php
return [
    'offer_redeemed_user' => 20,
    'offer_redeemed_store' => 10,
];
```

## Admin Endpoints

All admin endpoints require `auth:sanctum` and `role:admin`.

### User Points

```http
GET /api/v1/admin/users/{user}/points
GET /api/v1/admin/users/{user}/points/transactions?per_page=15
POST /api/v1/admin/users/{user}/points/add
POST /api/v1/admin/users/{user}/points/deduct
POST /api/v1/admin/users/{user}/points/set
```

### Store Points

```http
GET /api/v1/admin/stores/{store}/points
GET /api/v1/admin/stores/{store}/points/transactions?per_page=15
POST /api/v1/admin/stores/{store}/points/add
POST /api/v1/admin/stores/{store}/points/deduct
POST /api/v1/admin/stores/{store}/points/set
```

### Add/Deduct Request

```json
{
  "points": 25,
  "reason": "manual_bonus",
  "note": "Support adjustment",
  "meta": {
    "ticket": "SUP-123"
  }
}
```

### Set Request

```json
{
  "points": 100,
  "reason": "admin_correction",
  "note": "Corrected migrated balance",
  "meta": {
    "source": "backoffice"
  }
}
```

### Points Response

```json
{
  "message": "User points retrieved successfully.",
  "data": {
    "current_balance": 100,
    "lifetime_earned": 125,
    "lifetime_spent": 25,
    "updated_at": "2026-05-18T12:00:00+00:00"
  }
}
```

### Transaction List Response

```json
{
  "message": "User point transactions retrieved successfully.",
  "data": [
    {
      "id": 1,
      "type": "earn",
      "points": 25,
      "balance_before": 75,
      "balance_after": 100,
      "reason": "manual_bonus",
      "note": "Support adjustment",
      "meta": {
        "ticket": "SUP-123"
      },
      "admin_user_id": "admin-uuid",
      "user_id": "user-uuid",
      "store_id": null,
      "offer_claim_id": null,
      "created_at": "2026-05-18T12:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

## Customer Endpoints

Authenticated users can view only their own points.

```http
GET /api/v1/me/points
GET /api/v1/me/points/transactions?per_page=15
```

## Store Endpoints

Authenticated users can view store points only when authorized by the store policy.

```http
GET /api/v1/stores/{store}/points
GET /api/v1/stores/{store}/points/transactions?per_page=15
```

Store transaction responses use the same shape as user transactions, with `store_id` as the primary subject and nullable `user_id` for related customer context.
