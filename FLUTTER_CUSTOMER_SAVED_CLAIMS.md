# Flutter Integration: Customer Saved Claims

This document provides details for the Flutter app to integrate the Customer Saved Claims feature.

## Endpoints Overview

The backend provides two endpoints for the authenticated customer to view their claims. All endpoints require a valid Sanctum Bearer token.

### 1. List My Claims
`GET /api/v1/me/offer-claims`

**Query Parameters (All Optional):**
- `status`: String. Filter by claim status. Valid values: `active`, `redeemed`, `expired`, `cancelled`.
- `search`: String. Perform a deep search on `claim_token`, related product `title`, or related store `name`.
- `category`: Integer. Filter claims where the associated product belongs to the specific category ID.
- `per_page`: Integer. The number of records to return per page (default: 15).

**Response Format:**
```json
{
  "success": true,
  "data": [
    {
      "id": "claim-uuid",
      "user_id": 1,
      "store_id": 10,
      "product_id": 5,
      "offer_id": 12,
      "status": "active",
      "claim_token": "abc123token",
      "qr_code_token": "xyz987token",
      "offer_snapshot": { ... },
      "expires_at": "2026-06-30T20:59:59+00:00",
      "redeemed_at": null,
      "is_expired": false,
      "created_at": "2026-06-01T12:00:00+00:00",
      "updated_at": "2026-06-01T12:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 5
  }
}
```

### 2. Show Specific Claim Details
`GET /api/v1/me/offer-claims/{claim}`

**Path Parameters:**
- `claim`: UUID. The ID of the offer claim.

**Response Format:**
```json
{
  "success": true,
  "data": {
    "id": "claim-uuid",
    // ... claim details
  }
}
```

## Flutter Implementation Tips

1. **Model Serialization:** You can reuse your existing `OfferClaim` dart model. Ensure it properly parses the `offer_snapshot` and relationships if needed.
2. **Infinite Scrolling:** Use the `meta` object to implement infinite scrolling using the `current_page` and `last_page` values.
3. **Filtering & Searching:** In the "My Claims" screen, add a search bar and a dropdown for `status`. Pass these values to the `GET` request. 
4. **QR Code:** The `qr_code_token` should be passed to your QR code generator widget so the seller can scan and redeem it.
