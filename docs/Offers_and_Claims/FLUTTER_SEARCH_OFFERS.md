# Flutter Integration Guide: Search Offers

This document outlines how the Flutter app should interact with the newly created backend Search Offers API (`/api/v1/search/offers`).

## 1. Search Offers Endpoint

Use this endpoint to fetch paginated search results based on complex filter combinations.

- **URL:** `/api/v1/search/offers`
- **Method:** `GET`
- **Auth Required:** Optional (If Bearer token is provided, `is_favorite` will reflect the user's actual saved state. Otherwise, it defaults to `false`).

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | String | No | The search keyword (e.g. "nike"). Can be empty to just filter. |
| `category` | Integer | No | The ID of the category to filter by (e.g. `1` or `5`). |
| `sort_by` | String | No | Must be `popular`, `newest`, `price_high`, or `price_low`. |
| `min_rating` | Integer | No | From `1` to `5`. |
| `min_price` | Numeric | No | Minimum base price for the offer. |
| `max_price` | Numeric | No | Maximum base price for the offer. |
| `quick_filter` | String | No | Must be `all`, `newest`, `nearby`, or `all_offers`. |
| `lat` | Numeric | **Yes** if `quick_filter=nearby` | User's latitude. |
| `lng` | Numeric | **Yes** if `quick_filter=nearby` | User's longitude. |
| `page` | Integer | No | Current pagination page (default `1`). |
| `page_size` | Integer | No | Results per page (default `20`). |

### Response Schema

```json
{
  "success": true,
  "message": "Search results loaded",
  "data": {
    "query": "nike",
    "items": [
      {
        "id": "e44d372c-88ab...",
        "product_id": "e44d372c-88ab...",
        "store_id": "893d372b-11cd...",
        "image_url": "https://example.com/image.jpg",
        "title": "Air Max 90",
        "store_name": "Nike Official Store",
        "original_price": 500.00,
        "discounted_price": 400.00,
        "discount_percent": 20,
        "rating": 4.5,
        "is_favorite": false,
        "category": 1,
        "location": "Cairo",
        "time_left": "1d",
        "expires_at": null,
        "distance_km": 3.42
      }
    ],
    "pagination": {
      "page": 1,
      "page_size": 20,
      "total": 1,
      "total_pages": 1,
      "has_more": false
    },
    "facets": {
      "categories": [
        { "id": "shoes", "label_key": "search_category_shoes", "count": 0 },
        { "id": "watches", "label_key": "search_category_watches", "count": 0 },
        { "id": "accessories", "label_key": "search_category_accessories", "count": 0 }
      ],
      "price": {
        "min": 120.0,
        "max": 3200.0
      }
    }
  }
}
```

---

## 2. Toggle Favorite Endpoint

Use these endpoints to toggle the favorite state of a search result item.

- **URL:** `/api/v1/search/offers/{offerId}/favorite`
- **Method:** `POST` (to favorite) / `DELETE` (to unfavorite)
- **Auth Required:** Yes (Requires Bearer token).

### Response Schema

```json
{
  "success": true,
  "message": "Favorite updated",
  "data": {
    "offer_id": "e44d372c...",
    "product_id": "e44d372c...",
    "is_favorite": true
  }
}
```

---

## 3. Flutter Action Items

1. **Update `SearchCubit`**: When the user changes the "Quick Tabs" (e.g. from `all` to `nearby`), ensure the cubit adds `quick_filter=nearby`, `lat=XX.X`, and `lng=YY.Y` to the query and calls the API to refresh the results.
2. **Migrate Mock to Remote**: Create a `SearchRemoteDataSource` and implement `search()` using the `dio` package to map the request to the new endpoint instead of `SearchMockDatasourceImpl`.
3. **Handle Price Filter**: The backend now expects `min_price` and `max_price` instead of a relative `max_price_percent`. In Flutter, multiply the `max` value from `data.facets.price.max` by the slider percent to pass down `max_price` securely.
