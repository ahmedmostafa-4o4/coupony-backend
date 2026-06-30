# Offer Claims Backend Gaps Changes

Date: 2026-06-30

## Summary

This change fills the backend gaps needed by Flutter coupon details and "My Coupons" screens. Product offers now support seller-managed localized terms, branch-only flags, per-user claim limits, and global claim limits. Claims now snapshot the offer display data and store logo at claim time.

## Schema

`product_offers` now includes:

- `terms_en`: nullable JSON array of English terms.
- `terms_ar`: nullable JSON array of Arabic terms.
- `branch_only`: boolean, default `false`.
- `max_claims_per_user`: nullable integer.
- `max_total_claims`: nullable integer.

These fields are accepted in seller and admin product offer payloads under `offer`.

## Product Detail Offer Response

`GET /api/v1/products/{product}` returns the new offer fields:

```json
{
  "offer": {
    "terms": ["Valid in selected branches only."],
    "branch_only": true,
    "max_claims_per_user": 1,
    "max_total_claims": 500,
    "remaining_claims": 0,
    "remaining_total_claims": 218,
    "already_claimed": true
  }
}
```

`terms` is localized by `Accept-Language`:

- Arabic requests prefer `terms_ar`.
- Other requests prefer `terms_en`.
- If the preferred language is empty, the API falls back to the other language.
- If both are empty, the API returns an empty array.

`already_claimed` and `remaining_claims` require an authenticated user. Anonymous requests return `already_claimed: false` and `remaining_claims: null`.

## Claim Creation

`POST /api/v1/products/{product}/claims` enforces claim limits before creating a claim.

Per-user limit failure:

```json
{
  "success": false,
  "message": "Claim limit reached.",
  "reason": "claim_limit_reached"
}
```

Global limit failure:

```json
{
  "success": false,
  "message": "Offer claims exhausted.",
  "reason": "offer_claims_exhausted"
}
```

Both failures return HTTP 422. Active, redeemed, and expired claims count against limits. Cancelled claims do not.

## Claim Snapshot And Response

New claims copy these fields into `offer_snapshot.offer`:

- `terms`
- `terms_en`
- `terms_ar`
- `branch_only`
- `max_claims_per_user`
- `max_total_claims`

New claims also snapshot store display data:

```json
{
  "store": {
    "id": "store-id",
    "name": "Store name",
    "logo_url": "https://example.test/storage/stores/store-id/logo/logo.png"
  }
}
```

`OfferClaimResource` now returns `store` data directly. It prefers `offer_snapshot.store` and falls back to the loaded `store` relationship for older claims.

## My Coupons Query Parameters

`GET /api/v1/me/offer-claims` now supports:

- `subcategory`: category id for child category filtering.
- `category_slug`: matches a product category slug or a parent category slug.
- `sort_by`: one of `newest`, `expires_soon`, or `status_then_discount`.

Sorting behavior:

- `newest`: newest claims first.
- `expires_soon`: active claims first, nearest expiry first, null expiries last.
- `status_then_discount`: active claims first, then highest inferred discount from `offer_snapshot.offer.percentage_value`, `fixed_amount`, or `max_discount`, then newest.

## Verification

Focused tests were added or updated for:

- Localized offer terms and remaining claim state on product detail.
- Claim snapshots for terms, limits, branch-only flag, and store logo.
- Per-user and global claim limit failures.
- `/me/offer-claims` subcategory filtering.
- `/me/offer-claims` category slug filtering.
- `/me/offer-claims` supported sort options.

Commands run:

```powershell
php artisan test tests/Feature/OfferClaimTest.php tests/Feature/API/V1/MyOfferClaimControllerTest.php
php artisan test tests/Feature/ProductTest.php --filter='test_public_show_returns_localized_offer_terms_and_claim_state'
```
