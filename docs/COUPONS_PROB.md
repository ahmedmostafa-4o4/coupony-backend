# Coupons Production Backend Requirements

> Audience: **Backend developer**.
> Purpose: define EXACTLY what the backend must provide before the Flutter Coupons feature can become fully Production.
> Scope: based only on the already-documented Coupony backend (`Offers_and_Claims`, `coupony-offer-details-api-documentation-v2`, `Products`, `Stores_and_Feed`). Nothing here is invented — anything not documented is explicitly marked as a **gap**.

---

## Current Situation

The Flutter Coupons feature is **not** talking to the Coupony backend today. It is built entirely on mock/fabricated data:

- The remote datasource calls **`https://dummyjson.com/products`** (hardcoded), not `https://api.coupony.shop/api/v1`.
- `CouponModel.fromDummyJson` **fabricates** almost every field: `coupon_code`, `expires_at`, `is_expired`, `qr_data`, `external_url`, and the category keys.
- "Get Offer" (Offer Details bottom bar) builds a `CouponEntity` **locally** and shows a success message **without any API call** — no real claim is created.
- The "My Coupons" list, status (active/expired), QR, expiry and terms are all derived from fabricated values.
- `ApiConstants` currently contains **no** coupon/claim endpoint at all.

The migration target is the **documented Offer Claims system** (`/products/{id}/claims` + `/me/offer-claims`). A "coupon" in the app is, conceptually, an **offer claim**.

> Note: a stale doc in this folder (`BACKEND_REQUIREMENTS.md`) proposed `/me/coupons` and `/me/coupons/{id}`. Those endpoints **do not exist** and are **not** the target. Use the documented **offer-claims** endpoints below.

---

## Already Supported

These are documented and usable immediately — no backend work required.

### `POST /api/v1/products/{product}/claims`
- **Endpoint:** create an offer claim for a product (auth required). Body: `variant_ids[]` for standard offers with variants, or `buy_variant_ids[]` + `reward_variant_ids[]` for Buy-X-Get-Y. Returns the claim with `id`, `status`, `claim_token`, `qr_code_token`, `offer_snapshot { product, offer, selected_variants }`, `expires_at`, `redeemed_at`, `is_expired`, `created_at`.
- **Why Flutter will use it:** to perform the REAL "Get Offer" action instead of the local fabrication.
- **Where:** Offer Details "Get Offer" button (`info_offer_shop.dart` customer branch and `public_store/customer_product_detail_page.dart` `_claimOffer`).

### `GET /api/v1/me/offer-claims`
- **Endpoint:** paginated list of the authenticated user's claims. Query: `status` (`active` | `redeemed` | `expired`), `search` (product title, store name, claim token), `category` (product category id), `per_page`.
- **Why Flutter will use it:** to populate the "My Coupons" list with the user's real claims.
- **Where:** `CouponsPage` / `CouponsCubit`.

### `GET /api/v1/me/offer-claims/{claim}`
- **Endpoint:** single claim details.
- **Why Flutter will use it:** to load the coupon details screen with verified data.
- **Where:** `CouponDetailsPage` / `CouponDetailsCubit`.

### Claim `status` (`active` | `redeemed` | `expired`)
- **Why:** drives the list tabs and the active/expired/redeemed split.
- **Where:** `CouponsPage` status filter; `CouponDetailsCard` state.

### `qr_code_token` (+ `claim_token`)
- **Why:** the QR must encode a **verifiable** token the store can redeem, not a fabricated string.
- **Where:** `CouponDetailsCard` QR payload; consumed by seller redeem (`POST /stores/{store}/offer-claims/redeem`).

### `expires_at` / `is_expired`
- **Why:** real expiry term + active/expired classification.
- **Where:** `CouponDetailsCard` expiry term; `CouponsCubit` status split.

### `redeemed_at`
- **Why:** to show redeemed state and timestamp.
- **Where:** Coupon Details; future "Redeemed" list tab.

### `offer_snapshot.product` (product snapshot: `id`, `title`, `slug`, `currency`)
- **Why:** title + in-app navigation back to the product, plus pricing context.
- **Where:** list item + details card.

### `offer_snapshot.offer` (`type`, `percentage_value` / `fixed_amount`, `starts_at`, `ends_at`, `claim_expiration_minutes`)
- **Why:** discount value and offer validity window.
- **Where:** discount tag + terms.

### `offer_snapshot.store` (store snapshot: `id`, `name`)
- **Why:** store name + navigation to the public store.
- **Where:** list item + details card header.

### Search (`?search=`)
- **Why:** matches the existing local search box.
- **Where:** `CouponsPage` search field.

### Category filter (`?category={id}`)
- **Why:** parent-category chip filtering (single product-category id).
- **Where:** `CouponsPage` category chips (parent level only — see gap on subcategory below).

### Seller redemption — `POST /api/v1/stores/{store}/offer-claims/redeem`
- **Why:** the customer observes the result as `status = redeemed` / `redeemed_at`. Customer app does not call this; it is the verification counterpart of `qr_code_token`.
- **Where:** customer side reads status only.

### Variant data via `GET /api/v1/products/{id}`
- **Why:** claim creation needs `variant_ids`; the product detail returns `variants[]` with `is_default`/`is_in_stock`.
- **Where:** Offer Details "Get Offer" must send the selected/default variant id(s).

---

## Backend Gaps

The most important section. Each gap is a capability the current feature renders or depends on that is **not** present in the documented backend. Do **not** treat these as already-available.

### Gap 1 — Coupon Terms
- **Problem:** the coupon card renders a bulleted terms list, but product offers/claims document no `terms` field. Only **banners** document `terms_of_use`.
- **Why Flutter needs it:** `CouponDetailsCard` shows offer conditions to the user.
- **Current fake implementation:** terms are generated locally (`couponDetailExpiresTerm(date)` + the hardcoded `couponDetailBranchOnlyTerm`).
- **Suggested backend solution:** add a `terms` array to the offer object in `GET /products/{id}` and mirror it inside `offer_snapshot` of the claim. (Banners already prove this pattern.)
- **Required fields:** `terms: string[]` (localized by `Accept-Language`).
- **Priority:** High.

### Gap 2 — Branch-Only information
- **Problem:** the card shows a "valid in branches only" term; nothing documents this flag.
- **Why Flutter needs it:** to truthfully tell the user where the coupon is valid instead of a hardcoded sentence.
- **Current fake implementation:** the hardcoded `couponDetailBranchOnlyTerm` string is always shown.
- **Suggested backend solution:** either include this as one of the `terms` strings (Gap 1) or expose a boolean.
- **Required fields:** `branch_only: bool` (or fold into `terms`).
- **Priority:** Medium.

### Gap 3 — Human-readable coupon code
- **Problem:** the list and card display a short code; only `claim_token` / `qr_code_token` are documented (long opaque tokens).
- **Why Flutter needs it:** a short, user-facing redemption code distinct from the QR token.
- **Current fake implementation:** `couponCode = 'CPN-<hash>'` / `'BRAND-id'` fabricated client-side.
- **Suggested backend solution:** add a short display `code` on the claim, OR confirm that `claim_token` should be displayed directly (product decision).
- **Required fields:** `code: string` (or explicit guidance to display `claim_token`).
- **Priority:** Medium.

### Gap 4 — Store logo inside the claim
- **Problem:** the claim's store snapshot is `{ id, name }` only; the card shows the store **logo**.
- **Why Flutter needs it:** to render the store avatar without an extra round-trip per coupon.
- **Current fake implementation:** logo taken from dummyjson `thumbnail`.
- **Suggested backend solution:** add `logo_url` to the claim's store snapshot. (Fallback otherwise: Flutter fetches `GET /public-stores/{store_id}` per store, which is heavier.)
- **Required fields:** `store.logo_url: string`.
- **Priority:** Medium.

### Gap 5 — Claim limits
- **Problem:** nothing documents a per-offer / per-user claim limit; "Get Offer" can be pressed repeatedly.
- **Why Flutter needs it:** to disable/blocks re-claiming and avoid duplicate claims.
- **Current fake implementation:** none — the UI always shows success and never checks a limit.
- **Suggested backend solution:** expose a claim limit (and/or an "already claimed" state) on the offer in `GET /products/{id}`, plus a clear 422 reason code when the limit is reached.
- **Required fields:** `offer.max_claims_per_user: int|null`, and a 422 `reason` such as `claim_limit_reached`.
- **Priority:** High.

### Gap 6 — Remaining claims
- **Problem:** there is no documented "how many claims this user has left / how many remain globally."
- **Why Flutter needs it:** to show "N left" or "already claimed" on the Offer Details action.
- **Current fake implementation:** none (not represented at all).
- **Suggested backend solution:** add a remaining-count and/or `already_claimed` flag to the offer in `GET /products/{id}` (and optionally to the search/offer item).
- **Required fields:** `offer.remaining_claims: int|null`, `offer.already_claimed: bool`.
- **Priority:** Medium.

### Gap 7 — Subcategory / slug-based filtering on `/me/offer-claims`
- **Problem:** the "My Coupons" UI filters by a **two-level** taxonomy (parent chip + subcategory cloud) using slugs. The claims endpoint exposes only a single `category` (product-category id) and `search`.
- **Why Flutter needs it:** to reproduce the existing parent + subcategory filtering server-side instead of the current hardcoded slug → `raw_category_key` maps.
- **Current fake implementation:** `CouponsCubit._parentExternalKeys` / `_subcategoryExternalKeys` map hardcoded slugs to dummyjson `raw_category_key` sets and filter locally.
- **Suggested backend solution:** add `subcategory` (child category id) and/or `category_slug` query params to `GET /me/offer-claims`. If not provided, Flutter must drop to single-level (parent-id) filtering only.
- **Required fields:** query params `subcategory: int`, `category_slug: string`.
- **Priority:** Medium.

### Gap 8 — Sort parameter on `/me/offer-claims`
- **Problem:** the list currently sorts active-first then highest-discount; no documented `sort_by` on claims.
- **Why Flutter needs it:** consistent ordering from the server.
- **Current fake implementation:** client-side sort in the datasource.
- **Suggested backend solution:** optional `sort_by` (e.g. `status_then_discount`, `expires_soon`, `newest`). If omitted, Flutter keeps sorting client-side (acceptable).
- **Required fields:** query param `sort_by: enum` (optional).
- **Priority:** Low.

### Gap 9 — External offer URL
- **Problem:** the card has an "open offer" button using `external_url`; no such field is documented on claims/offers.
- **Why Flutter needs it:** today the button opens a (fabricated) URL.
- **Current fake implementation:** `external_url = https://dummyjson.com/products/{id}` fabricated.
- **Suggested backend solution:** **none required** — Flutter should replace this with in-app navigation to the product via `offer_snapshot.product.id` (`CustomerOfferDetailPage`). Listed here only to confirm the field is intentionally not needed.
- **Required fields:** none (resolved on the Flutter side).
- **Priority:** Low.

---

## Recommended Response Models

Suggestions only. No new business logic is introduced; these mirror the documented claim shape plus the gap fields above.

### Offer Claim (list item / details)

```json
{
  "id": "claim-uuid",
  "status": "active",
  "claim_token": "long-opaque-token",
  "qr_code_token": "long-opaque-token",
  "code": "CPN-AB12",                      // Gap 3 (or display claim_token)
  "expires_at": "2026-07-01T20:59:59+00:00",
  "redeemed_at": null,
  "is_expired": false,
  "created_at": "2026-06-30T10:00:00+00:00",
  "terms": [                                // Gap 1
    "Valid until 1 July 2026",
    "Valid in participating branches only"  // Gap 2 (or branch_only flag)
  ],
  "branch_only": true,                      // Gap 2 (optional alternative)
  "offer_snapshot": {
    "product": { "id": "product-uuid", "title": "…", "slug": "…", "currency": "EGP" },
    "offer": {
      "id": "offer-uuid",
      "type": "percentage",
      "percentage_value": "30.00",
      "fixed_amount": null,
      "starts_at": "2026-06-01T00:00:00+00:00",
      "ends_at": "2026-07-01T00:00:00+00:00",
      "claim_expiration_minutes": 60
    },
    "store": {
      "id": "store-uuid",
      "name": "Store Name",
      "logo_url": "https://…/logo.webp"     // Gap 4
    },
    "selected_variants": [
      { "id": "variant-uuid", "title": "…", "price": "99.00", "currency": "EGP" }
    ]
  }
}
```

### Offer block inside `GET /products/{id}` (for claim-limit awareness)

```json
{
  "offer": {
    "id": "offer-uuid",
    "type": "percentage",
    "max_claims_per_user": 1,    // Gap 5
    "remaining_claims": 0,       // Gap 6
    "already_claimed": true,     // Gap 6
    "terms": ["…"]               // Gap 1
  }
}
```

### Claim-limit error (422)

```json
{
  "success": false,
  "message": "This offer cannot be claimed.",
  "errors": { "reason": "claim_limit_reached" }   // Gap 5
}
```

---

## Final Backend Checklist

- [ ] Coupon terms (`terms[]` on offer + claim snapshot) — Gap 1
- [ ] Branch-only information (`branch_only` or via terms) — Gap 2
- [ ] Human-readable display coupon code (`code`, or confirm `claim_token`) — Gap 3
- [ ] Store logo inside the claim snapshot (`store.logo_url`) — Gap 4
- [ ] Claim limits (`offer.max_claims_per_user` + 422 `reason`) — Gap 5
- [ ] Remaining claims / already-claimed (`offer.remaining_claims`, `offer.already_claimed`) — Gap 6
- [ ] Subcategory / slug filtering on `/me/offer-claims` (`subcategory`, `category_slug`) — Gap 7
- [ ] Sort parameter on `/me/offer-claims` (`sort_by`, optional) — Gap 8
- [ ] (No action) External offer URL — resolved in Flutter via in-app product navigation — Gap 9

> Core claim lifecycle (create / list / details / status / `qr_code_token` / `expires_at` / `redeemed_at` / snapshots / search / single-category filter) is **already documented and ready**. The checklist above is the remaining work for a faithful, fully-production Coupons experience.
