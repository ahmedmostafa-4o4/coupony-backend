# Explore & Picks API — Flutter Integration Guide

## Overview

The Explore system provides the main discovery experience for customers. It consists of two endpoints:

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/v1/explore` | GET | Optional | Full explore page bootstrap (all sections) |
| `/api/v1/explore/picks` | GET | Optional | Paginated "Picked for You" offers |

Both endpoints work without authentication, but passing a Bearer token enables personalized results (favorites, interest-based recommendations).

---

## Endpoint 1: Explore Bootstrap

### `GET /api/v1/explore`

Returns all explore page sections in a single response.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `interest_id` | integer | No | Filter by product category ID (must be active) |
| `activity_id` | integer | No | Filter by store category ID (must be active) |
| `search` | string | No | Search term (max 200 chars) — matches offer labels, product titles, store names |
| `lat` | numeric | No | Latitude (-90 to 90). Required with `lng` to enable nearby section |
| `lng` | numeric | No | Longitude (-180 to 180). Required when `lat` is provided |

### Response Structure

```json
{
  "success": true,
  "data": {
    "interests": [...],
    "activities": [...],
    "trending": [...],
    "flash": [...],
    "top_stores": [...],
    "nearby": [...],
    "server_time": "2026-05-26T12:00:00Z"
  }
}
```

---

## Response Sections

### `interests` — Personalized Product Offers

Returns product offers tailored to the authenticated user's onboarding preferences.

**How personalization works:**

1. **`interesting_offers`** — The user's selected store category slugs (from onboarding). Products are filtered to only include those from stores belonging to these categories.

2. **`budget`** — Price filtering:
   - `low` → products priced ≤ 100
   - `medium` → products priced 100–500
   - `best_value` → no price cap, sorted by highest discount

3. **`shopping_style`** — Sort preference:
   - `best_discount` or `based_on_offer` → sorted by highest discount %
   - `online` or `in_store` → sorted by popularity (favorites count)

**Guest users** (no auth) → returns popular products by favorites count.

**Response format** (same as trending):
```json
{
  "id": 10,
  "product_id": "uuid",
  "store_id": "uuid",
  "image_url": "https://...",
  "title": "50% Off Headphones",
  "store_name": "TechStore",
  "discount_percent": 50.0,
  "original_price": 200.0,
  "discounted_price": 100.0,
  "saved_count": 42,
  "interest_id": 1,
  "activity_id": 2,
  "is_favorite": false
}
```

---

### `activities` — Store Categories

Returns all active store categories for the filter chips UI.

```json
{
  "id": 1,
  "name": "Restaurants",
  "icon_url": "https://..."
}
```

---

### `trending` — Trending Offers

Products sorted by a trending score formula:

```
score = favorites_count × 1
      + views_last_7_days × 0.5
      + discount_percent × 0.2
      + recency_score (max 30 - days since offer created)
```

**Response format:**
```json
{
  "id": 10,
  "product_id": "uuid",
  "store_id": "uuid",
  "image_url": "https://...",
  "title": "50% Off Headphones",
  "store_name": "TechStore",
  "discount_percent": 50.0,
  "original_price": 200.0,
  "discounted_price": 100.0,
  "saved_count": 42,
  "interest_id": 1,
  "activity_id": 2,
  "is_favorite": false
}
```

---

### `flash` — Flash Offers (Expiring Soon)

Offers expiring within the next 24 hours, sorted by soonest-expiring first.

```json
{
  "id": 15,
  "product_id": "uuid",
  "store_id": "uuid",
  "image_url": "https://...",
  "title": "Flash Deal - Shoes",
  "store_name": "ShoeWorld",
  "discount_percent": 30.0,
  "expires_at": "2026-05-26T18:00:00Z",
  "interest_id": 2,
  "activity_id": 1
}
```

Use `expires_at` for countdown timers.

---

### `top_stores` — Top Rated Stores

Stores with active offers, sorted by rating. Includes their best coupon.

```json
{
  "id": "uuid",
  "store_id": "uuid",
  "name": "TechStore",
  "image_url": "https://...",
  "followers_count": 150,
  "rating": 4.8,
  "interest_id": 1,
  "activity_id": 1,
  "best_coupon_title": "50% Off Headphones",
  "best_coupon_discount": 50.0,
  "best_coupon_image_url": "https://..."
}
```

---

### `nearby` — Nearby Offers (Location-Based)

Only populated when `lat` and `lng` are provided. Sorted by distance (closest first).

```json
{
  "id": 20,
  "product_id": "uuid",
  "store_id": "uuid",
  "image_url": "https://...",
  "title": "20% Off Coffee",
  "store_name": "CafeCorner",
  "original_price": 50.0,
  "discounted_price": 40.0,
  "save_percent": 20.0,
  "distance_km": 1.23,
  "interest_id": 3,
  "activity_id": 2
}
```

Returns empty array `[]` when lat/lng are not provided.

---

### `server_time`

UTC timestamp for client clock synchronization (useful for flash offer countdowns).

```
"server_time": "2026-05-26T12:00:00Z"
```

---

## Endpoint 2: Explore Picks (Picked for You)

### `GET /api/v1/explore/picks`

Paginated personalized recommendations.

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `interest_id` | integer | No | Filter by product category ID |
| `activity_id` | integer | No | Filter by store category ID |
| `search` | string | No | Search term (max 200 chars) |
| `page` | integer | No | Page number (min: 1, default: 1) |
| `page_size` | integer | No | Items per page (min: 1, max: 50, default: 12) |
| `min_discount_percent` | integer | No | Minimum discount % filter (0–90) |
| `sort_by` | string | No | Sort order (default: `trending`) |

### Sort Options

| Value | Description |
|-------|-------------|
| `trending` | Trending score (default) |
| `newest` | Most recently created offers first |
| `most_saved` | Most favorited products first |
| `highest_discount` | Highest discount percentage first |

### Personalization Logic

- **Authenticated users** → ML-based recommendations using browsing history + interactions. Falls back to popular products if no history.
- **Guest users** → Popular products sorted by favorites count.

### Response

```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "product_id": "uuid",
      "store_id": "uuid",
      "image_url": "https://...",
      "title": "50% Off Headphones",
      "store_name": "TechStore",
      "discount_percent": 50.0,
      "original_price": 200.0,
      "discounted_price": 100.0,
      "saved_count": 42,
      "interest_id": 1,
      "activity_id": 2,
      "is_favorite": false
    }
  ],
  "pagination": {
    "page": 1,
    "page_size": 12,
    "total": 48,
    "total_pages": 4,
    "has_more": true
  }
}
```

---

## Offer Visibility Rules

An offer appears in explore results only when ALL of these are true:

| Condition | Check |
|-----------|-------|
| Product status | `active` |
| Product approval | `approved` |
| Store status | `active` |
| Offer status | `active` |
| Offer start date | `starts_at IS NULL` OR `starts_at <= NOW()` |
| Offer end date | `ends_at IS NULL` OR `ends_at > NOW()` |

Offers with NULL dates are treated as always valid (no time restriction).

---

## Filters Behavior

All filters (`interest_id`, `activity_id`, `search`) apply across these sections:
- `interests` (personalized)
- `trending`
- `flash`
- `top_stores`
- `nearby`

The `picks` endpoint additionally supports `min_discount_percent` and `sort_by`.

---

## Flutter Implementation Notes

### 1. Handling the `interests` Section

This section returns **product offer cards** (not category chips). Render them the same way as trending offers.

```dart
// interests is a List<OfferCard>, same model as trending
final interests = (data['interests'] as List)
    .map((e) => OfferCard.fromJson(e))
    .toList();
```

### 2. Flash Offer Countdown

Use `expires_at` with `server_time` for accurate countdowns:

```dart
final serverTime = DateTime.parse(data['server_time']);
final expiresAt = DateTime.parse(flash['expires_at']);
final remaining = expiresAt.difference(serverTime);
```

### 3. Nearby Section

Only populated when you send location. Request permission and pass coordinates:

```dart
final position = await Geolocator.getCurrentPosition();
final response = await api.get('/explore', queryParameters: {
  'lat': position.latitude.toString(),
  'lng': position.longitude.toString(),
});
```

### 4. Infinite Scroll for Picks

```dart
int _page = 1;
bool _hasMore = true;

Future<void> loadMore() async {
  if (!_hasMore) return;
  final response = await api.get('/explore/picks', queryParameters: {
    'page': _page.toString(),
    'page_size': '12',
    'sort_by': _selectedSort,
  });
  final pagination = response['pagination'];
  _hasMore = pagination['has_more'];
  _page++;
  // append response['data'] to list
}
```

### 5. Filter Chips

Use the `activities` section to build filter chips. When tapped, re-fetch with `activity_id`:

```dart
final response = await api.get('/explore', queryParameters: {
  'activity_id': selectedActivity.id.toString(),
});
```

### 6. Authentication (Optional)

Pass the Bearer token when available for personalized results:

```dart
// With auth → personalized interests, is_favorite flags, ML-based picks
// Without auth → popular products, is_favorite always false
```

---

## Error Responses

| Status | Scenario |
|--------|----------|
| 400 | Invalid `interest_id` (not an active category) |
| 400 | Invalid `activity_id` (not an active store category) |
| 422 | Validation errors (e.g., `lat` out of range, `page_size` > 50) |

```json
{
  "success": false,
  "message": "The selected interest_id is invalid. It must correspond to an active category."
}
```
