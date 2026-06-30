# Offer Claims Backend Gaps Design

Date: 2026-06-30

## Scope

This design covers the backend gaps needed by the Flutter coupon details and "My Coupons" flows:

- Coupon terms on product offers and claims.
- Branch-only information.
- Store logo inside claim data.
- Per-user and global claim limits.
- Remaining claim state for product detail.
- Subcategory and slug filtering on `GET /me/offer-claims`.
- Sort support on `GET /me/offer-claims`.

The short human-readable coupon code and external offer URL gaps are out of scope for this spec.

## Approach

Use the existing product offer and offer claim flow. Add explicit fields to `product_offers`, expose them through the existing resources, enforce limits inside claim creation, and snapshot display-critical data into `offer_snapshot`.

This avoids a separate terms table and keeps the implementation aligned with the existing `ProductOffer`, `CreateOfferClaim`, `ProductOfferResource`, `OfferClaimResource`, and `MyOfferClaimController` structure.

## Schema

Add a migration for `product_offers`:

- `terms_en` JSON nullable.
- `terms_ar` JSON nullable.
- `branch_only` boolean default `false`.
- `max_claims_per_user` unsigned integer nullable.
- `max_total_claims` unsigned integer nullable.

Update `App\Domain\Product\Models\ProductOffer`:

- Add the fields to `$fillable`.
- Cast `terms_en` and `terms_ar` to arrays.
- Cast `branch_only` to boolean.
- Cast `max_claims_per_user` and `max_total_claims` to integers.

## Product Offer API Contract

`ProductOfferResource` returns the localized public terms field based on `Accept-Language`:

```json
{
  "terms": ["Valid until offer expiry", "Valid in branches only"],
  "branch_only": true,
  "max_claims_per_user": 1,
  "max_total_claims": 500,
  "remaining_claims": 0,
  "remaining_total_claims": 218,
  "already_claimed": true
}
```

`terms` selection rules:

- Return `terms_ar` for Arabic requests when it has at least one item.
- Return `terms_en` for non-Arabic requests when it has at least one item.
- Fall back to the other non-empty language array.
- Return an empty array when neither language has terms.

Editable raw fields, `terms_en` and `terms_ar`, should be accepted by seller/admin write flows that already manage product offers. Public product responses should expose only `terms`.

## Claim State Semantics

For authenticated product detail requests, computed offer state should include:

- `already_claimed`: `true` when the current user has at least one non-cancelled claim for the offer.
- `remaining_claims`: the authenticated user's remaining claim count when `max_claims_per_user` is set, otherwise `null`.
- `remaining_total_claims`: the global remaining claim count when `max_total_claims` is set, otherwise `null`.

Non-cancelled claims are claims whose status is not `cancelled`. Active, redeemed, and expired claims all count against limits.

For unauthenticated product detail requests:

- `already_claimed` is `false`.
- `remaining_claims` is `null`.
- `remaining_total_claims` is still returned when `max_total_claims` is set.

## Claim Creation

`CreateOfferClaim` enforces the new limits before creating a claim:

- Count non-cancelled claims for the same `user_id` and `offer_id`.
- If `max_claims_per_user` is set and the user has reached it, reject the request with HTTP 422 and `reason: claim_limit_reached`.
- Count all non-cancelled claims for the same `offer_id`.
- If `max_total_claims` is set and the offer has reached it, reject the request with HTTP 422 and `reason: offer_claims_exhausted`.

The existing product status, approval status, offer status, start date, end date, and variant selection validations still apply.

## Claim Snapshot

When a claim is created, `offer_snapshot.offer` copies the current offer display and limit fields:

- `terms`: localized terms for the request locale.
- `terms_en`.
- `terms_ar`.
- `branch_only`.
- `max_claims_per_user`.
- `max_total_claims`.

The snapshot also includes a store object:

```json
{
  "store": {
    "id": "store-id",
    "name": "Store name",
    "logo_url": "https://example.test/storage/stores/store-id/logo/logo.png"
  }
}
```

Snapshots are historical. Existing claims do not change when a seller edits terms, branch-only behavior, or limits after the claim is created.

## Claim Resource

`OfferClaimResource` keeps the existing token fields and adds store display data without requiring Flutter to call `GET /public-stores/{store_id}` for each claim.

The resource should prefer `offer_snapshot.store` when present. For older claims without that snapshot, it can fall back to the loaded `store` relationship and return `id`, `name`, and `logo_url`.

## My Coupons Filtering

Extend `GET /me/offer-claims` validation:

- Keep `category` as the existing category id filter.
- Add `subcategory`: nullable integer, must exist in `categories.id`.
- Add `category_slug`: nullable string.
- Add `sort_by`: nullable enum.

Filtering behavior:

- `category` filters claims through `product.categories`.
- `subcategory` filters claims through `product.categories.id = subcategory`.
- `category_slug` filters through either `product.categories.slug = category_slug` or `product.categories.parent.slug = category_slug`.
- If both `category` and `subcategory` are present, both filters apply.

This supports the Flutter parent chip plus subcategory cloud while keeping backward compatibility with the current single `category` query parameter.

## My Coupons Sorting

Supported `sort_by` values:

- `newest`: newest claims first. This is the current default.
- `expires_soon`: active claims with the nearest expiry first, with null expiries last.
- `status_then_discount`: active claims first, then higher inferred discount, then newest.

For `status_then_discount`, infer discount from `offer_snapshot.offer` in this priority:

- `percentage_value`.
- `fixed_amount`.
- `max_discount`.

If exact JSON discount ordering is not portable for the configured database, keep the enum accepted and fall back to newest ordering rather than failing the request.

## Error Handling

Claim-limit failures return HTTP 422:

```json
{
  "success": false,
  "message": "Claim limit reached.",
  "reason": "claim_limit_reached"
}
```

Global exhaustion uses:

```json
{
  "success": false,
  "message": "Offer claims exhausted.",
  "reason": "offer_claims_exhausted"
}
```

Implementation can use a small purpose-built exception type for claim limit failures so `OfferClaimController@store` can distinguish these from generic domain validation errors.

## Testing

Add focused feature tests for:

- `GET /products/{id}` returns localized `offer.terms`, `branch_only`, limit fields, `already_claimed`, and remaining counts.
- Claim creation snapshots localized terms, raw terms, branch flag, limits, and store logo.
- Per-user limits reject repeat claims with HTTP 422 and `reason: claim_limit_reached`.
- Global limits reject claims when total non-cancelled claims reach the cap with HTTP 422 and `reason: offer_claims_exhausted`.
- `/me/offer-claims` supports `subcategory`.
- `/me/offer-claims` supports `category_slug`.
- `/me/offer-claims` accepts `newest`, `expires_soon`, and `status_then_discount`.

Existing tests around claim creation, redemption, and product detail should continue passing.
