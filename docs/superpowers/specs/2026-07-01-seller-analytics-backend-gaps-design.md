# Seller Analytics Backend Gaps Design

## Scope

Extend `GET /api/v1/stores/{storeId}/analytics` with the data needed by the seller analytics dashboard:

- localized display labels for offer distribution items;
- product view counts for top-performing offers; and
- calendar-month redemption growth for the monthly goal.

Chart colors and store identity remain outside this endpoint. Flutter owns chart colors through its design system, while the existing stores endpoint remains the source of store name and logo data.

## Response Contract

The existing response remains backward compatible. The endpoint adds the following fields:

```json
{
  "monthly_goal": {
    "goal": 100,
    "current": 45,
    "achievement_percent": 45.0,
    "growth_percent": 12.5
  },
  "offer_distribution": [
    {
      "type": "fixed",
      "percentage": 50.0,
      "label": "Fixed Discount"
    }
  ],
  "top_performing_offers": [
    {
      "product_title": "Premium Coffee",
      "offer_type": "percentage",
      "offer_label": "20% Off",
      "usage_count": 145,
      "views": 320
    }
  ]
}
```

No `colorValue` field will be added. ARGB values are presentation details and must be mapped from `type` by Flutter.

## Offer Distribution Labels

Each `offer_distribution` item receives a `label` resolved from backend translation files according to the request locale. Translation keys cover the existing `fixed`, `percentage`, and `buy_x_get_y` values defined by `ProductOfferType`.

If an unrecognized type reaches the response, its raw type string is returned as the label. This keeps the response usable while a translation is added.

## Top-Offer Views

Each `top_performing_offers` item receives a `views` integer. It counts `product_views` belonging to that offer's product within the same period selected for the dashboard.

Views must be aggregated independently before they are associated with the top-offer result. Joining raw view and claim rows would multiply records and corrupt both `usage_count` and `views`. Ranking remains based on redeemed `usage_count`, descending, with the existing limit of ten items.

For the `all` period, the view count has no date boundary, matching the existing period resolver behavior.

## Monthly Goal Growth

`monthly_goal.growth_percent` compares redeemed claims in the current calendar month through the current time with redeemed claims in the previous calendar month. It does not depend on the dashboard's `period` query parameter because `monthly_goal.current` is already a calendar-month metric.

The calculation uses the existing `GrowthCalculator` so its rounding and zero-baseline behavior remain consistent with follower and visit growth. When there are no redemptions in either month, the response returns `0.0`.

## Caching and Errors

The existing 15-minute seller dashboard cache remains unchanged. Aggregate values are cached, while localized offer labels are applied after cache retrieval so responses honor each request's locale. The new fields introduce no request parameters or error responses.

No database migration or persisted analytics summary is required.

## Testing

Feature tests will verify:

- English and Arabic labels for every existing offer type;
- raw-type fallback for an unknown offer type where the data layer permits it;
- top-offer views are scoped to the requested period, store, and product;
- adding views does not change the redeemed usage count or ranking;
- monthly growth compares current and previous calendar months;
- zero-baseline monthly growth follows `GrowthCalculator`; and
- empty dashboard data includes `monthly_goal.growth_percent` as `0.0` while distribution and top-offer arrays remain empty.

## Documentation

Update `docs/Seller_Analytics/FLUTTER_SELLER_ANALYTICS_INTEGRATION.md` and the seller analytics Postman collection with the added fields. The integration guide must state that Flutter maps offer types to chart colors and that no backend `colorValue` is provided.
