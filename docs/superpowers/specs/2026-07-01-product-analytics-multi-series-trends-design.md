# Product Analytics Multi-Series Trends Design

## Goal

Add backend support for the product analytics UI presets that need instant switching between daily, monthly, and peak-time trend views.

Endpoint:

```http
GET /api/v1/stores/{storeId}/analytics/products/{productId}
```

## Current Behavior

The endpoint currently returns one `engagement.trend` array. Its granularity is selected by the backend from the requested `period` or explicit date range:

- Daily for ranges up to 30 days.
- Monthly for larger ranges.
- No product-level peak-time series exists.

This will remain unchanged for backward compatibility.

## Proposed Response Additions

Add a new sibling field under `engagement`:

```json
{
  "engagement": {
    "trend": [
      { "date": "2026-07-01", "count": 12 }
    ],
    "trends": {
      "days": [
        { "index": 0, "date": "2026-06-25", "count": 2 },
        { "index": 1, "date": "2026-06-26", "count": 5 }
      ],
      "months": [
        { "index": 0, "month": "2026-02", "count": 44 },
        { "index": 1, "month": "2026-03", "count": 31 }
      ],
      "peak_times": [
        { "index": 0, "label": "morning", "start_hour": 6, "end_hour": 12, "count": 10 },
        { "index": 1, "label": "afternoon", "start_hour": 12, "end_hour": 18, "count": 18 },
        { "index": 2, "label": "evening", "start_hour": 18, "end_hour": 24, "count": 24 },
        { "index": 3, "label": "night", "start_hour": 0, "end_hour": 6, "count": 5 }
      ]
    }
  }
}
```

## Data Logic

`engagement.trends.days`:

- Always returns 7 buckets.
- Uses the last 7 calendar days ending today.
- Counts product interactions per day.
- Interaction count matches existing trend logic: likes + comments + saves + shares.

`engagement.trends.months`:

- Always returns 6 buckets.
- Uses the last 6 calendar months ending with the current month.
- Counts product interactions per month.

`engagement.trends.peak_times`:

- Always returns 4 buckets.
- Uses the current request range resolved from `period`, `start_date`, and `end_date`.
- Counts product interactions grouped by time of day.
- Buckets:
  - `night`: 00:00-05:59
  - `morning`: 06:00-11:59
  - `afternoon`: 12:00-17:59
  - `evening`: 18:00-23:59

## Compatibility

- Keep existing `engagement.trend` exactly as-is.
- Add `engagement.trends` only as an additive field.
- Existing Flutter integrations can keep reading `trend`.
- New Flutter integration can switch instantly between `trends.days`, `trends.months`, and `trends.peak_times`.

## Tests

Add product analytics feature tests for:

- Response includes `engagement.trends.days`, `months`, and `peak_times`.
- `days` always has 7 indexed buckets.
- `months` always has 6 indexed buckets.
- `peak_times` always has 4 indexed buckets.
- Counts include likes, comments, saves, and shares.
- Existing `engagement.trend` behavior remains unchanged.

## Documentation

Update:

- `docs/Seller_Analytics/FLUTTER_SELLER_ANALYTICS_INTEGRATION.md`
- `docs/Offers_and_Claims/SELLER_PRODUCT_TO_CUSTOMER_CLAIM_FLOW.md` only if the high-level analytics flow section needs the new product analytics response shape.

