# Public Stores API Implementation Details

This document outlines the exact implementation details for the Public Stores API (`GET /api/v1/public-stores`) as it is currently implemented in the backend.

## Endpoint
`GET /api/v1/public-stores`

## Query Parameters Supported

| Parameter | Type | Validation | Description |
| :--- | :--- | :--- | :--- |
| `category_id` | Integer | `exists:store_categories,id` | Filters stores by category ID. |
| `search` | String | `max:255` | Performs a case-insensitive `LIKE` search. Matches against: store `name`, `description`, category `name`, city, and `address_line_1`. |
| `city` | String | `max:255` | Filters stores by matching the related addresses' `city` field. |
| `is_verified` | Boolean (0/1)| `boolean` | Filters verified (`1`) or unverified (`0`) stores. |
| `min_rating` | Numeric | `between:0,5` | Returns stores with `rating_avg` greater than or equal to this value. |
| `sort_by` | String | `in:latest,rating,name,popular` | Defines the sorting criteria. (See **Sorting Details** below). |
| `sort_direction`| String | `in:asc,desc` | Defines sorting order. Defaults to `desc` (except for `name` which defaults to `asc`). |
| `page` | Integer | - | For pagination. |
| `per_page` | Integer | `min:1, max:100` | Items per page. Default is 15. |

## Sorting Details (`sort_by`)

The endpoint applies the following logic based on the `sort_by` parameter:

1.  **`latest` (Default)**
    *   **Logic:** `ORDER BY created_at DESC`
    *   **Fallback:** If `sort_by` is omitted, it defaults to this.

2.  **`rating` (Top Rated)**
    *   **Logic:** `ORDER BY rating_avg [direction], rating_count DESC`
    *   **Default Direction:** `desc`

3.  **`name` (Alphabetical)**
    *   **Logic:** `ORDER BY name [direction]`
    *   **Default Direction:** `asc`

4.  **`popular` (Most Followed)**
    *   **Logic:** `ORDER BY followers_count [direction], rating_avg DESC`
    *   **Default Direction:** `desc`

## Response Structure

The API returns a paginated JSON response adhering to the standard project format:

```json
{
  "success": true,
  "message": "Public stores retrieved successfully.",
  "data": [
    {
      "id": "uuid",
      "name": "Store Name",
      "description": "Store Description",
      "logo_url": "url",
      "banner_url": "url",
      ...
      "categories": [...],
      "addresses": [...],
      "hours": [...],
      "socials": [...]
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

## Implementation Notes
*   **Pagination Order:** Pagination is applied *after* all filtering and sorting have been evaluated.
*   **Active Stores Only:** The query implicitly filters for stores with `status = 'active'`.
*   **Relations Loaded:** The response efficiently loads `categories`, `addresses`, `hours`, and `socials.social` to avoid N+1 issues and expose public-safe relations only.
