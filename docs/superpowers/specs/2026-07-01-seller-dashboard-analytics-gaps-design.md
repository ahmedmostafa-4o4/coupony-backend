# Seller Dashboard Analytics Gaps Design

## Scope

Extend the existing seller analytics and claim redemption contracts to support the Seller Home KPI grid and the My Offers summary:

- claimable active-offer count;
- period-scoped redeemed coupon count;
- period-scoped store-wide share count;
- optional manually captured redemption revenue;
- rolling 30-day and custom date ranges; and
- product engagement trends that include shares.

Chart colors remain owned by Flutter. Product-level peak-time series and a backend `generatedAt` field are outside this scope.

## Store Analytics Response

`GET /api/v1/stores/{storeId}/analytics` adds these top-level fields:

```json
{
  "active_offers_count": 12,
  "used_coupons_count": 84,
  "shares_count": 31,
  "coupon_revenue": [
    {
      "amount": "15250.00",
      "currency": "EGP",
      "recorded_redemptions": 70
    }
  ]
}
```

`used_coupons_count` is the number of claims redeemed during the selected range. `shares_count` is the number of `product_shares` records for products belonging to the store during that range.

`coupon_revenue` groups manually recorded revenue by currency. Each amount is serialized as a fixed two-decimal string. `recorded_redemptions` counts the redeemed claims contributing to that currency total. Clients compare the sum of `recorded_redemptions` with `used_coupons_count` to determine whether revenue coverage is complete.

For an all-time request, `used_coupons_count` also supplies the My Offers total usage metric. The existing store claims endpoint can independently provide this count through `GET /api/v1/stores/{store}/offer-claims?status=redeemed`, but including it in analytics avoids a second request for analytics screens.

## Active Offer Definition

`active_offers_count` is not affected by the requested analytics period. It counts offers that are currently claimable:

- `product_offers.status` is `active`;
- the related product status is `active`;
- the product approval status is `approved`;
- `starts_at` is null or not later than the current time; and
- `ends_at` is null or not earlier than the current time.

This definition matches the eligibility checks in `CreateOfferClaim` rather than counting only a status flag.

## Revenue Capture

Add nullable columns to `offer_claims`:

- `revenue_amount decimal(14,2)`; and
- `revenue_currency char(3)`.

`POST /api/v1/stores/{store}/offer-claims/redeem` accepts:

```json
{
  "qr_code_token": "claim-qr-token",
  "revenue_amount": 250.00,
  "currency": "EGP"
}
```

The revenue fields are optional for backward compatibility, but they must be provided together. `revenue_amount` must be numeric, non-negative, and fit the database precision. `currency` must match three uppercase ASCII letters. The request field `currency` is stored as `revenue_currency`.

Claims redeemed without revenue remain valid and contribute to `used_coupons_count`, but not `coupon_revenue`. Multiple currencies are never summed together.

The claim resource exposes `revenue_amount` and `revenue_currency` so authorized claim viewers can inspect the recorded values.

## Period Contract

Both seller dashboard and product analytics requests add `last_30_days` to the existing `period` values.

They also accept a custom range:

```text
?start_date=2026-06-01&end_date=2026-06-30
```

Rules:

- both dates are required when either is present;
- both use `YYYY-MM-DD`;
- `end_date` must be on or after `start_date`;
- the start boundary is the beginning of `start_date`;
- the end boundary is the end of `end_date`; and
- a valid custom range takes precedence over `period`.

`last_30_days` is a rolling range ending at the request time. Its previous range is the immediately preceding rolling 30-day window, used by growth calculations.

The shared `PeriodResolver` will return the current and previous boundaries for named and custom ranges. Analytics actions receive a resolved range rather than independently interpreting request strings.

## Product Engagement Trend

`GetProductAnalyticsAction` currently includes shares in `total_interactions` and `action_breakdown`, but its trend query combines only likes, comments, and saves. Add `ProductShare` to the trend aggregation so all three engagement outputs use the same interaction definition.

The endpoint continues returning one date-keyed trend series per request. Daily or monthly grouping remains selected from the resolved range. No product-level time-of-day series is added.

## Caching

Named-period cache keys retain the existing store/product and period components. Custom-range keys include normalized start and end dates so different ranges cannot share cached data.

Successful claim redemption invalidates seller dashboard caches for the affected store because it changes redeemed counts and may change revenue totals. Recording a product share invalidates product analytics caches for that product and seller dashboard caches for its store. The current share controller performs no analytics cache invalidation, so both invalidation paths are part of this change.

## Validation And Errors

Invalid date pairs, date order, revenue pairs, revenue precision, or currency format return the existing Laravel `422` validation response. Existing authorization and claim redemption domain errors remain unchanged.

No color fields are added to traffic sources, age groups, gender groups, or offer distribution. Flutter maps semantic keys to design-system colors.

## Testing

Feature and unit tests will verify:

- active offers satisfy every product, approval, status, and date condition;
- used coupon and share counts honor named and custom ranges;
- `last_30_days` uses a rolling current and previous window;
- custom dates override `period` and use full-day boundaries;
- invalid or incomplete date and revenue pairs return `422`;
- redemption stores valid revenue and remains compatible without revenue;
- revenue totals group by currency and exclude unrecorded claims;
- recorded-redemption coverage counts are correct;
- cache keys isolate custom ranges;
- redemption and sharing do not leave stale dashboard totals; and
- product trends include shares without changing action breakdown totals.

## Documentation

Update `docs/Seller_Analytics/FLUTTER_SELLER_ANALYTICS_INTEGRATION.md` and the seller analytics Postman collection with the KPI fields, range parameters, redemption revenue input, and revenue coverage semantics. Explicitly document that colors remain frontend-owned and that product analytics still returns one trend series without peak-time buckets.
