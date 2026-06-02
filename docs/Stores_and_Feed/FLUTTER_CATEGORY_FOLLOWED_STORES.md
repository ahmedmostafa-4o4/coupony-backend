# Categories, Products & Followed Stores: Flutter Implementation Guide

This document provides integration details for fetching products based on categories and retrieving a user's followed stores filtered by category.

---

## 1. Get Products by Category

This public endpoint allows you to fetch a paginated list of products belonging to a specific category.

- **URL:** `/api/v1/categories/{category_id}/products`
- **Method:** `GET`
- **Auth Required:** No (Public endpoint)
- **Headers:** 
  - `Accept: application/json`
  - `Accept-Language: ar` (or `en` based on locale)

### Query Parameters

| Parameter | Type    | Description | Default |
|-----------|---------|-------------|---------|
| `per_page`| Integer | Number of products to return per page. | `15` |

### Response Schema

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
            "images": [
                 {
                     "image_url": "https://domain.com/storage/products/img1.jpg",
                     "is_primary": true
                 }
            ],
            "store": {
                 "id": "...",
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

**Flutter Implementation Note:**
- Use a `GridView` or `ListView` to display the products.
- Use a `ScrollController` to detect when the user reaches the end of the list and trigger fetching the next page using the `current_page + 1` logic based on the `meta.last_page`.

---

## 2. Get Followed Stores (Filtered by Category)

This endpoint returns a paginated list of stores the authenticated user follows. It has been recently updated to support category filtering.

- **URL:** `/api/v1/me/followed-stores`
- **Method:** `GET`
- **Auth Required:** Yes (Bearer Token)
- **Headers:**
  - `Accept: application/json`
  - `Authorization: Bearer <token>`
  - `Accept-Language: ar` (or `en`)

### Query Parameters

| Parameter     | Type    | Description | Default |
|---------------|---------|-------------|---------|
| `category_id` | Integer | (Optional) Filter followed stores to only those that belong to this category. | `null` |
| `per_page`    | Integer | (Optional) Number of stores to return per page. | `15` |

### Response Schema

```json
{
    "success": true,
    "message": "Followed stores retrieved successfully.",
    "data": [
        {
            "id": "e83f2a89-11c2-4a0f-b6e9-eab2a3c748da",
            "name": "Travel Agency Plus",
            "slug": "travel-agency-plus",
            "logo_url": "https://domain.com/storage/stores/logo.png",
            "followers_count": 1024,
            "categories": [
                {
                    "id": 5,
                    "name": "Travel",
                    "icon_url": "https://domain.com/storage/categories/travel.png"
                }
            ],
            "is_following": true,
            "followed_at": "2026-06-02T15:30:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 15,
        "total": 20
    }
}
```

**Flutter Implementation Note:**
- This endpoint is ideal for building a personalized "My Stores" section.
- You can place a horizontal list of categories at the top (e.g., using a `ListView.builder` horizontally). When the user taps a category, pass its `id` as `category_id` to this endpoint to filter the user's followed stores accordingly.
