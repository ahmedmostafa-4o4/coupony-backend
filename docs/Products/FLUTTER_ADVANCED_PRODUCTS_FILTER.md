# Advanced Products Filter: Flutter Implementation Guide

This document provides implementation details for the Flutter team to integrate the advanced filtering capabilities on the public products endpoint.

## Endpoint Details

### Fetch Filtered Products

- **URL:** `/api/v1/products`
- **Method:** `GET`
- **Auth Required:** No (Public endpoint)
- **Headers:** 
  - `Accept: application/json`
  - `Accept-Language: ar` (or `en` based on locale)

### Query Parameters

You can combine any of the following query parameters to build a rich filtering interface for the user.

| Parameter          | Type    | Description | Default |
|--------------------|---------|-------------|---------|
| `category`         | Integer | Filter products by a specific category ID. | `null` |
| `search`           | String  | Keyword search for title or description. | `null` |
| `featured`         | Boolean | Set to `1` or `0` to filter featured products. | `null` |
| `min_price`        | Numeric | Minimum base price for the product. | `null` |
| `max_price`        | Numeric | Maximum base price for the product. | `null` |
| `min_review_score` | Numeric | Minimum average rating (e.g., `4` or `4.5`). | `null` |
| `sort_by`          | String  | Defines the sorting behavior. See below. | `newest` |
| `per_page`         | Integer | Number of products returned per page. | `15` |

### Allowed `sort_by` Values

- `newest`: Sorts by the latest added products (Default).
- `trending`: Sorts by most saved/favorited products.
- `highest_price`: Sorts from highest to lowest base price.
- `lowest_price`: Sorts from lowest to highest base price.
- `most_seller`: Sorts by highest sales count.

---

## Response Schema

The response returns a paginated list of public products.

```json
{
    "success": true,
    "message": "Products retrieved successfully.",
    "data": [
        {
            "id": "c1f7b822-2a54-4f81-8b07-fb252684b3f8",
            "title": "Maldives Resort 5 Days",
            "slug": "maldives-resort-5-days",
            "short_description": "A wonderful stay...",
            "base_price": "2000.00",
            "compare_at_price": "4000.00",
            "rating_avg": "4.80",
            "favorites_count": 150,
            "sale_count": 300,
            "images": [
                 {
                     "image_url": "https://yourdomain.com/storage/products/img1.jpg",
                     "is_primary": true
                 }
            ],
            "store": {
                 "id": "a9b7b822-...",
                 "name": "Travel Agency"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
```

## Flutter Implementation Guidelines

### 1. Building the Filter UI
Create a persistent filter bottom sheet or a dedicated filter screen that contains:
- **Price Range Slider:** Maps to `min_price` and `max_price`.
- **Rating Selector (Stars):** Maps to `min_review_score`.
- **Sort Dropdown/Chips:** Maps to the `sort_by` parameter values (`trending`, `lowest_price`, etc.).

### 2. State Management
Store the active filters in your state (e.g., Bloc, Riverpod, Provider). When a user clicks "Apply Filters", construct a `Map<String, dynamic>` containing only the non-null active filters and pass it to your API client (like `Dio` or `http`).

```dart
// Example using Dio
Future<void> fetchProducts() async {
  final Map<String, dynamic> queryParams = {
    'sort_by': selectedSortBy, // e.g. 'lowest_price'
    'per_page': 15,
  };

  if (minPrice != null) queryParams['min_price'] = minPrice;
  if (maxPrice != null) queryParams['max_price'] = maxPrice;
  if (minReview != null) queryParams['min_review_score'] = minReview;
  if (categoryId != null) queryParams['category'] = categoryId;

  final response = await dio.get(
    '/api/v1/products',
    queryParameters: queryParams,
  );
  
  // Handle response...
}
```
