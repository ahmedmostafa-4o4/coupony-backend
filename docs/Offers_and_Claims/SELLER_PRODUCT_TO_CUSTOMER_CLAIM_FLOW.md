# Seller Product to Customer Claim Flow

This document explains the backend flow from seller product creation to customer claiming, store redemption, automatic revenue capture, and analytics reporting.

Base path: `/api/v1`

All authenticated endpoints require:

```http
Authorization: Bearer {sanctum_token}
Accept: application/json
Accept-Language: en
```

Use `Accept-Language: ar` to select localized offer terms and other localized fields where supported.

---

## 1. Seller Creates Product With Offer

Endpoint:

```http
POST /api/v1/stores/{store}/products
```

Auth:

- Sanctum required.
- Seller/store access required.
- `subscription:products` middleware applies.

Purpose:

- Creates the product.
- Creates at least one variant.
- Creates the attached product offer.
- Product starts in the normal product lifecycle and must be active and approved before customers can claim it.

### Request

Standard fixed discount example:

```json
{
  "title": "Blue Chanel Perfume",
  "slug": "blue-chanel-perfume",
  "short_description": "Fresh perfume offer",
  "description": "Long product description",
  "currency": "EGP",
  "sku": "BLUE-CHANEL-001",
  "category_ids": [1, 2],
  "variants": [
    {
      "title": "100ml",
      "sku": "BLUE-CHANEL-100",
      "original_price": 700,
      "currency": "EGP",
      "is_default": true,
      "is_active": true,
      "inventory_mode": "tracked",
      "stock_qty": 20,
      "attributes": [
        { "attribute_name": "Size", "attribute_value": "100ml" }
      ]
    }
  ],
  "offer": {
    "type": "fixed",
    "label": "EGP 70 off",
    "terms_en": ["Valid in store branches only"],
    "terms_ar": ["Arabic branch-only term"],
    "branch_only": true,
    "duration_days": 30,
    "claim_expiration_minutes": 1440,
    "max_claims_per_user": 1,
    "max_total_claims": 100,
    "fixed_amount": 70
  }
}
```

Percentage offer:

```json
{
  "offer": {
    "type": "percentage",
    "duration_days": 30,
    "percentage_value": 15,
    "max_discount": 100
  }
}
```

Buy X Get Y offer:

```json
{
  "offer": {
    "type": "buy_x_get_y",
    "duration_days": 30,
    "buy_qty": 2,
    "get_qty": 1,
    "allow_mix_buy_variants": true,
    "allow_mix_reward_variants": false,
    "buy_variant_skus": ["BLUE-CHANEL-100"],
    "reward_variant_skus": ["BLUE-CHANEL-50"]
  }
}
```

### Main Validations

Product:

- `title`: required string max 255.
- `slug`: optional, unique per store.
- `currency`: required 3-character string.
- `sku`: optional, unique per store.
- `category_ids.*`: must exist in `categories`.
- `variants`: required array, min 1.

Variant:

- `variants.*.title`: required.
- `variants.*.original_price`: required numeric min 0.
- `variants.*.price`: prohibited. Backend resolves public price from offer pricing rules.
- `variants.*.compare_at_price`: prohibited.
- `variants.*.currency`: required with variants, 3-character string.
- `variants.*.inventory_mode`: `tracked` or `unlimited`.
- If `tracked`, `stock_qty` is required.
- If `unlimited`, `stock_qty` and `low_stock_threshold` must be empty.
- Only one variant can be default.
- Variant SKUs must be unique in the request.

Offer:

- `offer.type`: required, one of `fixed`, `percentage`, `buy_x_get_y`.
- `offer.status`: optional, one of `active`, `inactive`.
- `offer.terms_en`, `offer.terms_ar`: optional arrays of strings max 500 each.
- `offer.branch_only`: optional boolean.
- At least one of `offer.duration_days` or `offer.duration_hours` is required.
- `offer.claim_expiration_minutes`: optional integer min 1.
- `offer.max_claims_per_user`: optional integer min 1.
- `offer.max_total_claims`: optional integer min 1.
- For `fixed`, `offer.fixed_amount` is required.
- For `percentage`, `offer.percentage_value` is required and must be `<= 100`; `offer.max_discount` is optional.
- For `buy_x_get_y`, `buy_qty`, `get_qty`, `buy_variant_skus`, and `reward_variant_skus` are required.
- BOGO SKUs must exist in the submitted variants.

### Response

The exact product response is controlled by the product resource. The important flow result is:

- Product record exists.
- Product variants exist.
- Product offer exists.
- Offer terms, limits, branch flag, pricing fields, and variant targets are stored.

---

## 2. Admin Approval and Product Availability

A customer can claim only if:

- Product `status` is `active`.
- Product `approval_status` is `approved`.
- Offer exists.
- Offer `status` is `active`.
- Offer `starts_at` is null or in the past.
- Offer `ends_at` is null or in the future.
- Claim limits are not exhausted.

If any of these fail, claim creation returns HTTP 422 with a business message.

Examples:

```json
{
  "success": false,
  "message": "Only approved active products can be claimed."
}
```

```json
{
  "success": false,
  "message": "This offer is not yet claimable."
}
```

---

## 3. Customer Discovers Products and Offers

Public product endpoints:

```http
GET /api/v1/products
GET /api/v1/products/{product}
GET /api/v1/public-stores/{store}/products
GET /api/v1/search/offers
```

Common public product filters:

| Filter | Meaning |
| --- | --- |
| `category` | Category id; includes direct category and children where supported |
| `search` | Search product content |
| `featured` | Boolean featured filter |
| `min_price` | Minimum `base_price` |
| `max_price` | Maximum `base_price` |
| `min_review_score` | Minimum rating |
| `sort_by` | `trending`, `highest_price`, `lowest_price`, `most_seller`, `newest`, `popular`, `price` |
| `sort_order` | `asc` or `desc` for sortable modes that support order |
| `per_page` | Pagination size where supported |

Public product responses include offer and interaction metadata through the product resources. The frontend uses this to render the offer card and claim action.

---

## 4. Customer Creates Offer Claim

Endpoint:

```http
POST /api/v1/products/{product}/claims
```

Auth:

- Sanctum required.
- Customer authenticated user is used as the claim owner.

### Request: Fixed or Percentage Offer

If product has variants:

```json
{
  "variant_ids": ["variant-uuid"]
}
```

If product has no variants:

```json
{}
```

### Request: Buy X Get Y Offer

```json
{
  "buy_variant_ids": ["buy-variant-uuid-1", "buy-variant-uuid-2"],
  "reward_variant_ids": ["reward-variant-uuid"]
}
```

### Validations

- `variant_ids`: optional array of UUID strings.
- `buy_variant_ids`: optional array of UUID strings.
- `reward_variant_ids`: optional array of UUID strings.
- For BOGO:
  - `buy_variant_ids` count must equal configured `offer.buy_qty`.
  - `reward_variant_ids` count must equal configured `offer.get_qty`.
- For fixed/percentage:
  - If product has variants, at least one `variant_id` is required.
- Selected variants must be active and belong to the product.
- For BOGO:
  - Buy variants must be allowed by the offer buy targets.
  - Reward variants must be allowed by the offer reward targets.
  - If mixing is disabled, multiple unique variants for that role are rejected.

### Claim Limit Logic

The backend checks active, redeemed, and expired claims against limits. Cancelled claims do not count.

Per-user limit:

```json
{
  "success": false,
  "message": "Claim limit reached.",
  "reason": "claim_limit_reached"
}
```

Global limit:

```json
{
  "success": false,
  "message": "Offer claims exhausted.",
  "reason": "offer_claims_exhausted"
}
```

### Snapshot Logic

On successful claim creation, the backend freezes key display and redemption data into `offer_snapshot`:

- `claimed_at`
- `product`: id, store_id, title, slug, currency, primary image URL
- `customer`: id, full name
- `store`: id, name, logo URL
- `offer`: id, type, status, label, localized terms, terms_en, terms_ar, branch_only, starts_at, ends_at, expiration, claim limits, pricing fields
- `selected_variants` for fixed/percentage
- `selected_buy_variants` and `selected_reward_variants` for BOGO

Snapshot variants include:

```json
{
  "id": "variant-uuid",
  "sku": "BLUE-CHANEL-100",
  "title": "100ml",
  "price": "630.00",
  "currency": "EGP",
  "inventory_mode": "tracked"
}
```

### Response: 201

```json
{
  "success": true,
  "message": "Offer claim created successfully.",
  "data": {
    "id": "claim-uuid",
    "user_id": "customer-uuid",
    "store_id": "store-uuid",
    "product_id": "product-uuid",
    "offer_id": "offer-uuid",
    "status": "active",
    "claim_token": "public-claim-token",
    "qr_code_token": "qr-token",
    "offer_snapshot": {
      "product": {
        "id": "product-uuid",
        "title": "Blue Chanel Perfume",
        "currency": "EGP",
        "image_url": "https://api.example.com/storage/products/image.jpg"
      },
      "customer": {
        "id": "customer-uuid",
        "name": "Customer Name"
      },
      "store": {
        "id": "store-uuid",
        "name": "Store Name",
        "logo_url": "https://api.example.com/storage/stores/logo.jpg"
      },
      "offer": {
        "type": "fixed",
        "terms": ["Valid in store branches only"],
        "branch_only": true,
        "fixed_amount": "70.00",
        "max_claims_per_user": 1,
        "max_total_claims": 100
      },
      "selected_variants": [
        {
          "id": "variant-uuid",
          "title": "100ml",
          "price": "630.00",
          "currency": "EGP"
        }
      ]
    },
    "customer": {
      "id": "customer-uuid",
      "name": "Customer Name"
    },
    "product": {
      "id": "product-uuid",
      "title": "Blue Chanel Perfume",
      "image_url": "https://api.example.com/storage/products/image.jpg"
    },
    "store": {
      "id": "store-uuid",
      "name": "Store Name",
      "logo_url": "https://api.example.com/storage/stores/logo.jpg"
    },
    "usage_count": 0,
    "expires_at": "2026-07-02T12:00:00+00:00",
    "redeemed_at": null,
    "redeemed_by": null,
    "revenue_amount": null,
    "revenue_currency": null,
    "is_expired": false,
    "created_at": "2026-07-01T12:00:00+00:00",
    "updated_at": "2026-07-01T12:00:00+00:00"
  }
}
```

---

## 5. Customer Lists and Filters My Coupons

Endpoint:

```http
GET /api/v1/me/offer-claims
```

Auth:

- Sanctum required.
- Returns only claims owned by the authenticated user.

### Query Parameters

| Param | Type | Validation | Logic |
| --- | --- | --- | --- |
| `status` | string | one of claim statuses | Filters claim status |
| `search` | string | max 255 | Searches product title, store name, or claim token |
| `category` | int | existing category id | Filters product categories |
| `subcategory` | int | existing category id | Filters product categories |
| `category_slug` | string | max 255 | Matches category slug or children of a parent slug |
| `sort_by` | string | `newest`, `expires_soon`, `status_then_discount` | Sort mode |
| `per_page` | int | min 1, max 100 | Pagination size |

### Sort Logic

- `newest`: newest claim first.
- `expires_soon`: active claims first, then nearest `expires_at`, then newest.
- `status_then_discount`: active claims first, then highest discount value from snapshot, then newest.

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": "claim-uuid",
      "status": "active",
      "claim_token": "public-claim-token",
      "qr_code_token": "qr-token",
      "customer": {
        "id": "customer-uuid",
        "name": "Customer Name"
      },
      "product": {
        "id": "product-uuid",
        "title": "Blue Chanel Perfume",
        "image_url": "https://api.example.com/storage/products/image.jpg"
      },
      "store": {
        "id": "store-uuid",
        "name": "Store Name",
        "logo_url": "https://api.example.com/storage/stores/logo.jpg"
      },
      "usage_count": 0,
      "expires_at": "2026-07-02T12:00:00+00:00",
      "redeemed_at": null,
      "revenue_amount": null,
      "revenue_currency": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 40
  }
}
```

Show one claim:

```http
GET /api/v1/me/offer-claims/{claim}
```

If the claim does not belong to the user, backend returns 404.

---

## 6. Store Lists Claims

Endpoint:

```http
GET /api/v1/stores/{store}/offer-claims
```

Auth:

- Sanctum required.
- Store owner or employee with claims access.

### Query Parameters

| Param | Type | Validation | Logic |
| --- | --- | --- | --- |
| `status` | string | one of claim statuses | Filters claim status |
| `per_page` | int | min 1, max 100 | Pagination size |

### Sort Logic

Store claims are ordered by:

1. latest `redeemed_at`
2. latest `created_at`

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": "claim-uuid",
      "status": "redeemed",
      "claim_token": "public-claim-token",
      "qr_code_token": "qr-token",
      "customer": {
        "id": "customer-uuid",
        "name": "Customer Name"
      },
      "product": {
        "id": "product-uuid",
        "title": "Blue Chanel Perfume",
        "image_url": "https://api.example.com/storage/products/image.jpg"
      },
      "store": {
        "id": "store-uuid",
        "name": "Store Name",
        "logo_url": "https://api.example.com/storage/stores/logo.jpg"
      },
      "usage_count": 47,
      "expires_at": "2026-07-02T12:00:00+00:00",
      "redeemed_at": "2026-07-01T14:30:00+00:00",
      "revenue_amount": "125.75",
      "revenue_currency": "EGP"
    }
  ],
  "summary": {
    "all_claims": 10,
    "total_usage": 7,
    "this_month_usage": 3,
    "total_revenue": [
      {
        "amount": "1250.75",
        "currency": "EGP",
        "recorded_redemptions": 7
      }
    ]
  },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 10
  }
}
```

### Summary Logic

The `summary` object is store-wide and is not limited to the current page.

| Field | Logic |
| --- | --- |
| `all_claims` | Count of all claims for the store across all statuses |
| `total_usage` | Count of redeemed claims for the store |
| `this_month_usage` | Count of redeemed claims where `redeemed_at` is in the current calendar month |
| `total_revenue` | Sum of redeemed claim `revenue_amount`, grouped by `revenue_currency` |
| `total_revenue[].recorded_redemptions` | Count of redeemed claims that contributed recorded revenue for that currency |

Show one store claim:

```http
GET /api/v1/stores/{store}/offer-claims/{claim}
```

If the claim does not belong to the store, backend returns 404.

---

## 7. Store Redeems Claim

Endpoint:

```http
POST /api/v1/stores/{store}/offer-claims/redeem
```

Auth:

- Sanctum required.
- Store owner or employee with `redeemClaims` permission.

### Request

Automatic revenue:

```json
{
  "qr_code_token": "qr-token"
}
```

Manual revenue override:

```json
{
  "qr_code_token": "qr-token",
  "revenue_amount": 125.75,
  "currency": "EGP"
}
```

### Validations

- `qr_code_token`: required string max 100.
- `revenue_amount`: optional numeric min 0 max `999999999999.99`; required with `currency`.
- `currency`: optional uppercase 3-letter ISO currency; required with `revenue_amount`.

### Redemption Business Rules

The backend:

1. Locks the claim by QR token.
2. Confirms claim belongs to the store.
3. Rejects already redeemed claims.
4. Rejects non-active claims.
5. Marks expired claims as `expired` and rejects redemption.
6. Reads selected variants from the immutable claim snapshot.
7. Locks selected variants.
8. Confirms selected variants still belong to the product and are active.
9. For tracked inventory, confirms enough stock exists.
10. Increments variant `redemption_count`.
11. Decrements tracked variant `stock_qty`.
12. Increments product `redemption_count`.
13. Captures revenue.
14. Marks claim as `redeemed`.
15. Awards points if not already awarded.
16. Dispatches `OfferClaimRedeemed`.
17. Invalidates seller analytics cache.

### Automatic Revenue Logic

Manual override wins if both `revenue_amount` and `currency` are sent.

If omitted:

- `fixed` and `percentage` offers:
  - Sum `offer_snapshot.selected_variants[].price`.
  - If no selected variants exist, use product `base_price`.
- `buy_x_get_y` offers:
  - Sum only `offer_snapshot.selected_buy_variants[].price`.
  - Do not count `selected_reward_variants`; these are free reward items.
- Currency:
  - First non-empty selected variant currency.
  - Fallback to product currency.

The saved fields are:

- `offer_claims.revenue_amount`
- `offer_claims.revenue_currency`

### Success Response

```json
{
  "success": true,
  "message": "Offer claim redeemed successfully.",
  "data": {
    "id": "claim-uuid",
    "status": "redeemed",
    "redeemed_at": "2026-07-01T14:30:00+00:00",
    "redeemed_by": "employee-user-uuid",
    "revenue_amount": "125.75",
    "revenue_currency": "EGP",
    "usage_count": 47,
    "customer": {
      "id": "customer-uuid",
      "name": "Customer Name"
    },
    "product": {
      "id": "product-uuid",
      "title": "Blue Chanel Perfume",
      "image_url": "https://api.example.com/storage/products/image.jpg"
    },
    "store": {
      "id": "store-uuid",
      "name": "Store Name",
      "logo_url": "https://api.example.com/storage/stores/logo.jpg"
    }
  }
}
```

### Error Responses

Claim not found for store:

```json
{
  "success": false,
  "message": "The scanned claim could not be found for this store."
}
```

Already redeemed:

```json
{
  "success": false,
  "message": "This claim has already been redeemed."
}
```

Expired:

```json
{
  "success": false,
  "message": "This claim has expired."
}
```

Insufficient stock:

```json
{
  "success": false,
  "message": "Insufficient stock is available to redeem this claim."
}
```

---

## 8. Analytics After Redemption

Endpoint:

```http
GET /api/v1/stores/{storeId}/analytics
```

Auth:

- Sanctum required.
- Store owner or employee with analytics permission.

### Filters

| Param | Type | Values |
| --- | --- | --- |
| `period` | string | `all`, `today`, `last_7_days`, `last_30_days`, `this_month`, `this_year` |
| `start_date` | date | `YYYY-MM-DD`; required with `end_date` |
| `end_date` | date | `YYYY-MM-DD`; required with `start_date`; must be after or equal start |

If `start_date` and `end_date` are provided, they take precedence over `period`.

### Dashboard Response

```json
{
  "active_offers_count": 18,
  "used_coupons_count": 145,
  "shares_count": 34,
  "coupon_revenue": [
    {
      "amount": "12500.75",
      "currency": "EGP",
      "recorded_redemptions": 120
    }
  ],
  "monthly_goal": {
    "goal": 100,
    "current": 45,
    "achievement_percent": 45.0,
    "growth_percent": 12.5
  },
  "new_followers": {
    "count": 23,
    "growth_percent": 15.2
  },
  "store_visits": {
    "count": 1250,
    "growth_percent": -3.4
  },
  "offer_distribution": [
    { "type": "fixed", "percentage": 50.0, "label": "Fixed Discount" }
  ],
  "peak_redemption_times": [
    { "day": "monday", "time_window": "morning", "count": 12 }
  ],
  "top_performing_offers": [
    {
      "product_title": "Blue Chanel Perfume",
      "offer_type": "fixed",
      "offer_label": "EGP 70 off",
      "usage_count": 47,
      "views": 320
    }
  ]
}
```

### Analytics Field Logic

| Field | Logic |
| --- | --- |
| `active_offers_count` | Active offers for active, approved products where offer date window is claimable now |
| `used_coupons_count` | Redeemed claims in the selected period/date range |
| `shares_count` | Product shares in the selected period/date range |
| `coupon_revenue` | Sum of redeemed claim revenue grouped by `revenue_currency` |
| `monthly_goal.current` | Calendar-month redemptions |
| `monthly_goal.growth_percent` | Current calendar month vs previous calendar month |
| `new_followers` | Store followers in selected period and growth vs previous matching range |
| `store_visits` | Product views for store products in selected period and growth vs previous matching range |
| `offer_distribution` | Active offers grouped by type, normalized to 100% |
| `peak_redemption_times` | Redeemed claims bucketed into 7 days x 4 time windows |
| `top_performing_offers` | Top 10 redeemed offers by usage count, with product views |

---

## 9. Product Analytics

Endpoint:

```http
GET /api/v1/stores/{storeId}/analytics/products/{productId}
```

Filters are the same as seller analytics:

- `period`
- `start_date`
- `end_date`

The product must belong to the requested store.

### Response Shape

```json
{
  "header": {
    "views": 1500,
    "likes": 230,
    "comments": 45,
    "saves": 89
  },
  "overview": {
    "impressions": 1500,
    "reached_accounts": 1200,
    "profile_visits": 180,
    "new_followers": 12,
    "traffic_sources": [
      { "source": "search", "percentage": 40.0 }
    ]
  },
  "engagement": {
    "total_interactions": 400,
    "engagement_rate": 26.67,
    "trend": [
      { "date": "2026-07-01", "count": 15 }
    ],
    "action_breakdown": {
      "likes": 230,
      "comments": 45,
      "saves": 89,
      "shares": 36
    }
  },
  "audience": {
    "followers_percent": 65.0,
    "non_followers_percent": 35.0,
    "age_groups": [
      { "range": "18-24", "percentage": 35.0 }
    ],
    "gender_groups": [
      { "gender": "male", "percentage": 55.0 },
      { "gender": "female", "percentage": 42.0 },
      { "gender": "other", "percentage": 3.0 }
    ]
  }
}
```

Product engagement `trend` includes likes, comments, saves, and shares.

---

## 10. Cache and Invalidation Logic

Analytics responses are cached with versioned cache keys.

Seller analytics cache invalidates when:

- A claim is redeemed.
- Store monthly goal is updated.
- A product share is recorded for a store product.

Product analytics cache invalidates when:

- A product share is recorded.

The versioned key strategy avoids needing to delete every possible `period` or custom date-range cache key.

---

## 11. End-to-End Flow Summary

1. Seller creates product with variants and offer.
2. Admin/product lifecycle approves and activates product.
3. Customer discovers product through public product/search/store endpoints.
4. Customer submits claim request.
5. Backend validates product, offer, dates, limits, and selected variants.
6. Backend creates immutable claim snapshot and QR token.
7. Customer shows QR code to store.
8. Store owner/employee scans QR and calls redeem endpoint.
9. Backend locks claim and variants, validates stock, decrements stock, increments redemption counters.
10. Backend captures revenue automatically from claim snapshot unless manual override is sent.
11. Backend marks claim redeemed, awards points, dispatches event, invalidates analytics cache.
12. Seller dashboard reflects used coupon count, coupon revenue, peak times, top offers, and monthly goal progress.
