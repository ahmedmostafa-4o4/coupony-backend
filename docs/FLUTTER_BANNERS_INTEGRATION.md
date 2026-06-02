# Banners API - Flutter Integration Guide

## Overview

The Banners API supports seller-submitted promotional banners that are reviewed by admins and displayed to customers. A banner links to one or more product offers and one or more store branches, includes terms of use, and can be liked, favorited, shared, and claimed by customers.

Flutter should use these endpoints for the home/banner carousel and banner details page.

| Area | Endpoint | Auth | Purpose |
|---|---|---|---|
| Customer | `GET /api/v1/customer/banners` | Optional | Active banner carousel list |
| Customer | `GET /api/v1/customer/banners/{banner}` | Optional | Banner details |
| Customer | `POST /api/v1/customer/banners/{banner}/likes` | Required | Like banner |
| Customer | `DELETE /api/v1/customer/banners/{banner}/likes` | Required | Unlike banner |
| Customer | `POST /api/v1/customer/banners/{banner}/favorites` | Required | Add banner to favorites |
| Customer | `DELETE /api/v1/customer/banners/{banner}/favorites` | Required | Remove banner from favorites |
| Customer | `POST /api/v1/customer/banners/{banner}/shares` | Required | Record share event |
| Customer | `POST /api/v1/customer/banners/{banner}/claims` | Required | Claim all eligible offers linked to the banner |
| Seller | `GET /api/v1/stores/{store}/banner-offers` | Required | Offers seller can attach to a banner |
| Seller | `GET /api/v1/stores/{store}/banners` | Required | Seller banner requests |
| Seller | `POST /api/v1/stores/{store}/banners` | Required | Submit banner request |
| Seller | `GET /api/v1/stores/{store}/banners/{banner}` | Required | Seller banner request detail |
| Seller | `PATCH /api/v1/stores/{store}/banners/{banner}` | Required | Edit pending/rejected request |
| Admin | `GET /api/v1/admin/banners` | Admin | List all banner requests |
| Admin | `GET /api/v1/admin/banners/{banner}` | Admin | Admin banner detail |
| Admin | `PATCH /api/v1/admin/banners/{banner}` | Admin | Update banner metadata |
| Admin | `POST /api/v1/admin/banners/{banner}/approve` | Admin | Approve banner |
| Admin | `POST /api/v1/admin/banners/{banner}/reject` | Admin | Reject banner |

## Customer Behavior

- Customer list returns a maximum of 10 banners.
- Banners are ordered by `priority` ascending.
- Expired banners are never returned: backend filters with `now() <= end_time`.
- Only `status=approved`, `is_active=true` banners are returned.
- A banner is returned only when it has at least one currently eligible linked offer.
- The list endpoint is cached for 15 minutes and invalidated by admin banner updates.
- Banner images should be designed around 600x400 or a 3:2 ratio. Flutter can apply the dark overlay.

## Customer List

### `GET /api/v1/customer/banners`

Auth is optional. If Flutter passes a Bearer token, `is_liked` and `is_favorited` are returned for the current user. Without auth, both flags are `false`.

### Success Response

```json
{
  "success": true,
  "message": "Banners retrieved successfully.",
  "data": [
    {
      "id": "9f1d0e2a-1111-4444-9999-111111111111",
      "store_id": "9f1d0e2a-2222-4444-9999-222222222222",
      "image_url": "https://api.coupony.shop/storage/banners/store-id/banner.jpg",
      "image_path": "banners/store-id/banner.jpg",
      "discount_label": "30% OFF",
      "date_range": "June 1 - June 30",
      "cta_label": "Claim now",
      "terms_of_use": "Valid for selected branches only.",
      "end_time": "2026-06-30T20:59:59+00:00",
      "priority": 1,
      "is_active": true,
      "status": "approved",
      "likes_count": 15,
      "is_liked": false,
      "is_favorited": false,
      "store": {
        "id": "9f1d0e2a-2222-4444-9999-222222222222",
        "name": "Coffee Store",
        "description": "Specialty coffee and desserts",
        "logo_url": "https://api.coupony.shop/storage/stores/logo.png",
        "banner_url": "https://api.coupony.shop/storage/stores/banner.png",
        "phone": "+201234567890",
        "email": "store@example.com"
      },
      "branches": [
        {
          "id": 10,
          "address_line1": "Mall branch",
          "city": "Cairo",
          "latitude": "30.04440000",
          "longitude": "31.23570000"
        }
      ],
      "offers": [
        {
          "id": "9f1d0e2a-3333-4444-9999-333333333333",
          "type": "percentage",
          "status": "active",
          "label": "30% off drinks",
          "starts_at": "2026-06-01T10:00:00+00:00",
          "ends_at": "2026-06-30T20:59:59+00:00",
          "claim_expiration_minutes": 1440,
          "percentage_value": "30.00",
          "product": {
            "id": "9f1d0e2a-4444-4444-9999-444444444444",
            "title": "Iced Latte",
            "status": "active",
            "approval_status": "approved",
            "primary_image_url": "https://api.coupony.shop/storage/products/image.jpg"
          }
        }
      ]
    }
  ]
}
```

## Customer Detail

### `GET /api/v1/customer/banners/{banner}`

Use this when the user taps a banner. It returns the same banner object with linked offers, branches, terms of use, and store details.

### Not Found

Returns `404` when the banner is pending, rejected, inactive, expired, or has no eligible offers.

```json
{
  "success": false,
  "message": "Banner not found."
}
```

## Customer Interactions

All interaction endpoints require auth.

### Like

`POST /api/v1/customer/banners/{banner}/likes`

```json
{
  "success": true,
  "message": "Banner liked successfully.",
  "data": {
    "banner_id": "9f1d0e2a-1111-4444-9999-111111111111",
    "likes_count": 16,
    "is_liked": true
  }
}
```

### Unlike

`DELETE /api/v1/customer/banners/{banner}/likes`

```json
{
  "success": true,
  "message": "Banner unliked successfully.",
  "data": {
    "banner_id": "9f1d0e2a-1111-4444-9999-111111111111",
    "likes_count": 15,
    "is_liked": false
  }
}
```

### Favorite

`POST /api/v1/customer/banners/{banner}/favorites`

```json
{
  "success": true,
  "message": "Banner added to favorites successfully.",
  "data": {
    "banner_id": "9f1d0e2a-1111-4444-9999-111111111111",
    "is_favorited": true
  }
}
```

### Unfavorite

`DELETE /api/v1/customer/banners/{banner}/favorites`

```json
{
  "success": true,
  "message": "Banner removed from favorites successfully.",
  "data": {
    "banner_id": "9f1d0e2a-1111-4444-9999-111111111111",
    "is_favorited": false
  }
}
```

### Share

`POST /api/v1/customer/banners/{banner}/shares`

Request body:

```json
{
  "platform": "whatsapp"
}
```

Allowed platforms: `whatsapp`, `facebook`, `twitter`, `instagram`, `copy_link`, `other`.

Success response:

```json
{
  "success": true,
  "message": "Banner share recorded successfully."
}
```

## Claim Banner

### `POST /api/v1/customer/banners/{banner}/claims`

Creates one grouped banner claim that contains all currently eligible offers linked to the banner.

```json
{
  "success": true,
  "message": "Banner claim created successfully.",
  "data": {
    "id": "9f1d0e2a-5555-4444-9999-555555555555",
    "banner_id": "9f1d0e2a-1111-4444-9999-111111111111",
    "user_id": "9f1d0e2a-6666-4444-9999-666666666666",
    "store_id": "9f1d0e2a-2222-4444-9999-222222222222",
    "status": "active",
    "claim_token": "long-random-token",
    "qr_code_token": "long-random-token",
    "expires_at": "2026-06-30T20:59:59+00:00",
    "claim_snapshot": {
      "claimed_at": "2026-06-02T12:00:00+00:00",
      "banner": {},
      "store": {},
      "branches": [],
      "offers": []
    }
  }
}
```

If no offers are eligible anymore:

```json
{
  "success": false,
  "message": "This banner is not available for claiming."
}
```

## Seller Banner Request

### Selectable Offers

`GET /api/v1/stores/{store}/banner-offers`

Returns product offers from the store that the seller can attach to a banner request. The list can include pending and approved products/offers, because admin banner approval can approve pending linked offers.

### Submit Banner

`POST /api/v1/stores/{store}/banners`

Use `multipart/form-data`.

| Field | Type | Required | Notes |
|---|---|---|---|
| `image` | file | Yes | jpg, jpeg, png, webp, max 5MB |
| `discount_label` | string | Yes | Max 100 chars |
| `date_range` | string | No | Display text only |
| `cta_label` | string | Yes | Max 100 chars |
| `terms_of_use` | string | Yes | Terms shown on detail page |
| `end_time` | datetime | Yes | Must be in the future |
| `offer_ids[]` | uuid[] | Yes | Offers must belong to this store |
| `address_ids[]` | int[] | Yes | Branches must belong to this store |
| `min_transaction` | prohibited | No | Do not send this field |

Success creates a `pending` banner with `is_active=false`.

### Edit Banner

`PATCH /api/v1/stores/{store}/banners/{banner}`

Seller can edit only `pending` or `rejected` banners. Editing a rejected banner resubmits it as `pending`.

## Admin Workflow

### Approve

`POST /api/v1/admin/banners/{banner}/approve`

Optional body:

```json
{
  "priority": 1,
  "is_active": true
}
```

Approval behavior:

- Sets `status=approved`.
- Sets `approved_at` and `approved_by`.
- Sets `is_active=true` by default.
- Uses the provided `priority` when sent.
- Approves linked pending products/offers in the same transaction.
- Invalidates the customer banner cache.

### Reject

`POST /api/v1/admin/banners/{banner}/reject`

```json
{
  "reason": "Image quality is too low."
}
```

Rejecting sets `status=rejected`, `is_active=false`, and stores `rejection_reason`.

## Flutter Model Notes

Recommended client entities:

- `BannerEntity`
- `BannerOfferEntity`
- `BannerBranchEntity`
- `BannerStoreEntity`
- `BannerClaimEntity`

Important fields for UI:

- Carousel card: `id`, `image_url`, `discount_label`, `date_range`, `cta_label`, `end_time`, `likes_count`, `is_liked`, `is_favorited`.
- Detail page: all carousel fields plus `terms_of_use`, `offers`, `branches`, `store`.
- Claim success: `id`, `qr_code_token`, `claim_snapshot`, `expires_at`.

## Error Handling

| Status | Meaning |
|---|---|
| `401` | Missing/invalid token for protected endpoint |
| `403` | User cannot manage this store/admin area |
| `404` | Banner not public/visible or does not exist |
| `422` | Validation error or banner cannot be claimed |
| `500` | Unexpected backend error |

