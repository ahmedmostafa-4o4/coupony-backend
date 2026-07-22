# Following Feed — Backend Response

## Resolved: product-details identifier

`GET /api/v1/customer/home/following-feed` now returns the product UUID in
`data.items[*].offer.id`. Flutter can pass this value directly to
`GET /api/v1/products/{product}`.

Previously, the resource returned the related `product_offers.id`. That UUID
does not identify a `Product`, so it could fail when the client opened product
details. The correction applies to followed, recommended, and trending items,
because all three tiers use the same response resource.

### Implemented change

```php
// Before
'id' => $this->offer_id,

// After
'id' => $this->id,
```

The query continues to select `product_offers.id as offer_id` for offer
calculation, but the public feed identifier now uses the selected product's
primary key. No Flutter change is required for the existing product-details
navigation; it should continue to use `offer.id`.

Regression coverage: `tests/Feature/FollowingFeedTest.php` verifies that the
feed identifier equals the product ID and differs from its offer ID.

## Backend findings for the reported gaps

| Gap | Backend status |
| --- | --- |
| Invalid `offer.id` when opening product details | Resolved in this change. |
| Like, save, comment, and follow fields | Already supplied by the endpoint: `is_liked`, `likes_count`, `comments_count`, `is_saved`, and `store.is_followed`. UI wiring remains a Flutter responsibility. |
| Recommendation metadata | Already supplied by the endpoint: `source_type` and `recommendation_reason`. UI badges remain a Flutter responsibility. |
| Empty fallback feed | The endpoint implements followed → recommended → trending fallback. Whether a zero-follow user sees it or a client CTA is a Flutter product decision. |
| `category_ar` in English | Not confirmed as a backend data bug from the provided payload alone. The value is locale-aware: an `ar` request returns `categories.name_ar` when it is available. Verify the production request's `Accept-Language` header and the corresponding category row before changing data. |

## Contract note

`category` remains the category slug. `category_ar` is the display label chosen
by the request locale, despite the legacy field name. Clients should send
`Accept-Language: ar` when they require Arabic labels.
