# Offer Claim Card Data Design

## Scope

Extend authenticated offer-claim responses with the remaining fields required by the coupon card:

- the claiming customer's display name;
- the product's primary image URL; and
- the offer's current redeemed usage count.

Existing claim identifiers, tokens, `offer_snapshot`, `store`, status, and timestamps remain unchanged.

## Response Contract

Each claim returned by authenticated claim endpoints will include:

```json
{
  "customer": {
    "id": "user-id",
    "name": "Ahmed Mohamed"
  },
  "product": {
    "id": "product-id",
    "title": "Luxury Men's Perfume",
    "image_url": "https://example.test/storage/products/perfume.jpg"
  },
  "usage_count": 47
}
```

`usage_count` is the total number of redeemed claims with the same `offer_id`. Active, expired, and cancelled claims do not contribute to this value.

## Claim Snapshot

New claims add customer and product display data to `offer_snapshot`:

```json
{
  "customer": {
    "id": "user-id",
    "name": "Ahmed Mohamed"
  },
  "product": {
    "id": "product-id",
    "store_id": "store-id",
    "title": "Luxury Men's Perfume",
    "slug": "luxury-mens-perfume",
    "currency": "EGP",
    "image_url": "https://example.test/storage/products/perfume.jpg"
  }
}
```

The customer name uses `User::full_name`. The image uses the product image marked `is_primary`; if no image is marked primary, `image_url` is `null`. Relative storage paths are converted to public URLs using the existing claim snapshot URL handling.

The snapshot preserves what was displayed when the customer claimed the offer. It does not duplicate mutable profile or product records beyond the fields required by the claim card.

## Resource Fallbacks

`OfferClaimResource` exposes top-level `customer` and `product` objects.

For new claims, each object is read from `offer_snapshot`. For claims created before this change:

- `customer` falls back to the loaded `OfferClaim::user` relation and `User::full_name`;
- `product` falls back to the loaded `OfferClaim::product` relation and its primary image; and
- a deleted or unavailable related record produces `null` for that object.

Snapshot values take precedence over current relation values so later profile, title, or image edits do not rewrite historical claim display data.

## Usage Aggregation

`usage_count` is live data rather than a snapshot. Claim list and detail queries aggregate redeemed claims by `offer_id` and expose the result to `OfferClaimResource`.

List queries must perform one grouped aggregation for the returned offers. They must not execute a count query per claim. A missing aggregate is serialized as integer `0`.

## Query Loading

Authenticated claim queries eager-load the fallback relations required by older claims:

- `user.profile` for `User::full_name`;
- `product.images` for the primary product image; and
- the existing `store` and `offer` relations.

Authorization behavior does not change. Customer identity is returned only from endpoints where the authenticated caller is authorized to view the claim.

## Testing

Feature tests will verify:

- new claims snapshot the customer name and primary product image URL;
- the resource prefers snapshot values after customer or product edits;
- older claims fall back to current customer and product relations;
- missing relations return `null` without changing the rest of the claim response;
- relative and absolute product image URLs are serialized correctly;
- `usage_count` includes only redeemed claims for the same offer;
- claims for other offers do not affect the count; and
- claim lists load customer, product image, and usage data without per-claim queries.

## Documentation

Update the offer-claim API and Flutter integration documentation with the `customer`, `product.image_url`, and `usage_count` fields. Examples must distinguish the live usage aggregate from customer and product snapshot data.
