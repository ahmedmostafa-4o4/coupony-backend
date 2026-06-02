# Offer Duration-Based Dates

## Overview

This change replaces the absolute `starts_at` / `ends_at` date input from sellers with a **duration-based** approach. Sellers now specify how long an offer should last (in days and/or hours), and the system calculates the actual dates upon admin approval.

---

## How It Works

### Seller Creates a Product

The seller provides `duration_days` and/or `duration_hours` instead of `starts_at` / `ends_at`.

```json
{
  "offer": {
    "type": "fixed",
    "fixed_amount": 15,
    "duration_days": 7,
    "duration_hours": 12,
    "claim_expiration_minutes": 1440
  }
}
```

At this point, the offer is stored with:
- `starts_at = NULL`
- `ends_at = NULL`
- `duration_days = 7`
- `duration_hours = 12`

The product is **not visible** to customers (pending approval).

### Admin Approves the Product (First Time)

On first approval, the system automatically sets:
- `starts_at = NOW()`
- `ends_at = NOW() + duration_days + duration_hours`

For the example above: if approved at `2026-05-26 10:00:00`, then:
- `starts_at = 2026-05-26 10:00:00`
- `ends_at = 2026-06-02 22:00:00` (7 days + 12 hours later)

### Re-Approval (After Product Update)

When a product is updated and re-approved, the dates are **NOT reset**. The original `starts_at` and `ends_at` remain unchanged.

### Seller Updates Duration (Approved Product)

If the seller updates `duration_days` or `duration_hours` on an already-approved product, this is treated as a **direct update** — no admin review required. The system immediately recalculates:

```
ends_at = starts_at + new_duration_days + new_duration_hours
```

This allows sellers to extend or shorten their offer without waiting for admin approval.

---

## API Changes

### Seller Endpoints

#### Create Product (`POST /api/v1/stores/{storeId}/products`)

**Removed fields:**
- `offer.starts_at`
- `offer.ends_at`

**New fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `offer.duration_days` | integer (min: 1) | At least one required | Offer duration in days |
| `offer.duration_hours` | integer (min: 1) | At least one required | Offer duration in hours |

At least one of `duration_days` or `duration_hours` must be provided.

#### Update Product (`PUT /api/v1/stores/{storeId}/products/{productId}`)

Same changes as create. Duration updates on approved products apply immediately without revision review.

### Admin Endpoints

Admin endpoints (`POST /api/v1/admin/products`, `PUT /api/v1/admin/products/{productId}`) retain `starts_at` and `ends_at` for direct date control, and also accept `duration_days` / `duration_hours`.

---

## Database Changes

### Migration

```
2026_05_26_000001_add_duration_to_product_offers_table.php
```

Adds to `product_offers` table:
| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| `duration_days` | unsigned integer | Yes | Seller-specified duration in days |
| `duration_hours` | unsigned integer | Yes | Seller-specified duration in hours |

Run: `php artisan migrate`

---

## Files Modified

| File | Change |
|------|--------|
| `database/migrations/2026_05_26_000001_add_duration_to_product_offers_table.php` | New migration |
| `app/Domain/Product/Models/ProductOffer.php` | Added `duration_days`, `duration_hours` to fillable and casts |
| `app/Application/Http/Requests/CreateProductRequest.php` | Replaced `starts_at`/`ends_at` with `duration_days`/`duration_hours` validation |
| `app/Application/Http/Requests/UpdateProductRequest.php` | Same as above |
| `app/Application/Http/Requests/AdminStoreProductRequest.php` | Added `duration_days`/`duration_hours` (kept `starts_at`/`ends_at`) |
| `app/Application/Http/Requests/AdminUpdateProductRequest.php` | Same as above |
| `app/Domain/Product/DTOs/ProductData.php` | Maps both duration and date fields from request |
| `app/Domain/Product/Repositories/ProductRepository.php` | `syncOffer()` stores duration; `snapshotPayload()` includes duration |
| `app/Domain/Product/Actions/ApproveProductRevision.php` | Sets `starts_at`/`ends_at` on first approval |
| `app/Domain/Product/Actions/UpdateProduct.php` | Duration changes bypass review; recalculates `ends_at` |
| `app/Domain/Product/Actions/UpdateAdminProduct.php` | Updated offer fallback data to use duration fields |
| `app/Domain/Product/Support/ProductRequestedChangeFields.php` | Removed `starts_at`/`ends_at` from reviewable fields |

---

## Behavior Matrix

| Scenario | `starts_at` | `ends_at` | Review Required |
|----------|-------------|-----------|-----------------|
| Product created (pending) | `NULL` | `NULL` | — |
| First admin approval | `now()` | `now() + duration` | — |
| Re-approval after update | Unchanged | Unchanged | — |
| Seller updates duration (approved) | Unchanged | Recalculated | **No** |
| Seller updates offer type/amount (approved) | Unchanged | Unchanged | **Yes** |

---

## Flutter Integration Notes

### Creating an Offer

```dart
// Before (removed)
"offer": {
  "starts_at": "2026-06-01T00:00:00Z",
  "ends_at": "2026-06-08T00:00:00Z"
}

// After (new)
"offer": {
  "duration_days": 7,
  "duration_hours": 0
}
```

### Extending an Offer (Approved Product)

Send only the duration fields in the update — no need to resend the full offer:

```dart
"offer": {
  "type": "fixed",           // required with offer
  "fixed_amount": 15,        // keep existing values
  "duration_days": 14,       // extend to 14 days
  "duration_hours": 0
}
```

This applies immediately without admin review.

### Reading Offer Dates

The response still includes `starts_at` and `ends_at` (computed by the system). Use these for display:

```dart
// Response
"offer": {
  "starts_at": "2026-05-26T10:00:00Z",
  "ends_at": "2026-06-09T10:00:00Z",
  "duration_days": 14,
  "duration_hours": 0
}
```

---

## Important Notes

1. **Existing offers** with `starts_at`/`ends_at` already set will continue to work — the explore/claim logic reads these computed dates from the database unchanged.
2. **Admins** can still override dates directly via admin endpoints.
3. **Duration is additive** — if both `duration_days: 7` and `duration_hours: 12` are set, the offer lasts 7 days and 12 hours from approval.
4. **NULL duration** — at least one of `duration_days` or `duration_hours` must be provided by the seller.
