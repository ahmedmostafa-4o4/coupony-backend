# Requirements Document

## Introduction

This feature implements the backend API endpoints for the Explore page in the Coupony Flutter app. The Explore page provides users with a discovery-driven experience including search, category-based filtering, trending offers, flash deals, top stores, nearby offers, and a paginated "Picked for You" feed. The API supports both authenticated and guest users, with personalized favorites tracking for authenticated users. A database migration adds a denormalized `favorites_count` column to the products table for efficient sorting and display.

## Glossary

- **Explore_Service**: The backend service responsible for aggregating and returning explore page data including trending offers, flash offers, top stores, nearby offers, and picked-for-you feeds.
- **Interest**: A product category from the `categories` table, linked to products via the `product_categories` pivot table. Used as a filter dimension on the Explore page.
- **Activity**: A store category from the `store_categories` table, linked to stores via the `store_store_category` pivot table. Used as a filter dimension on the Explore page.
- **Trending_Offer**: A product offer scored by a weighted formula combining campaign priority, saved count, recent views, discount percentage, and recency.
- **Flash_Offer**: A product offer with a time-limited window where `ends_at` is greater than the current time and within the next 24 hours.
- **Top_Store**: A store with the highest rating that includes a preview of its best coupon (highest discount active offer).
- **Nearby_Offer**: A product offer from a store with a physical address, sorted by geographic distance from the requesting user's coordinates.
- **Picked_Offer**: A product offer returned in the paginated "Picked for You" section, supporting full filtering and sorting.
- **Trending_Score**: A numeric score calculated as `active_campaign_priority * 3 + saved_count * 1 + views_last_7_days * 0.5 + discount_percent * 0.2 + recency_score`.
- **Active_Product**: A product with `status = active` and `approval_status = approved` belonging to a store with `status = active` or `status = approved`.
- **Active_Offer**: A ProductOffer with `status = active`.
- **Discount_Percent**: The percentage discount of an offer, derived from `percentage_value` for percentage-type offers or calculated from `fixed_amount` relative to the product's `base_price` for fixed-type offers.
- **Favorites_Count**: A denormalized unsigned integer column on the `products` table tracking the total number of users who have favorited the product.
- **Server_Time**: The current server timestamp returned in the bootstrap response, used by the Flutter client to synchronize flash offer countdown timers.

## Requirements

### Requirement 1: Explore Bootstrap Endpoint

**User Story:** As a mobile app user, I want to load the Explore page with all discovery sections in a single request, so that the page renders quickly without multiple API calls.

#### Acceptance Criteria

1. WHEN a GET request is made to `/api/v1/explore`, THE Explore_Service SHALL return a JSON response containing `interests`, `activities`, `trending`, `flash`, `top_stores`, `nearby`, and `server_time` sections.
2. THE Explore_Service SHALL return all active Interest records with `id`, `name`, and `icon_url` fields in the `interests` section.
3. THE Explore_Service SHALL return all active Activity records with `id`, `name`, and `icon_url` fields in the `activities` section.
4. THE Explore_Service SHALL include a `server_time` field containing the current UTC timestamp in ISO 8601 format.
5. WHEN the request includes a valid Bearer token, THE Explore_Service SHALL populate the `is_favorite` field with the authenticated user's actual favorite status for each applicable offer.
6. WHEN the request does not include a Bearer token, THE Explore_Service SHALL set `is_favorite` to `false` for all offers.
7. THE Explore_Service SHALL only include offers from Active_Products with an Active_Offer belonging to stores with an active or approved status.

### Requirement 2: Trending Offers Section

**User Story:** As a user, I want to see the most popular offers in a hero carousel, so that I can discover high-value deals quickly.

#### Acceptance Criteria

1. WHEN the explore bootstrap endpoint is requested, THE Explore_Service SHALL return between 5 and 10 Trending_Offer items in the `trending` section, sorted by Trending_Score in descending order.
2. THE Explore_Service SHALL calculate the Trending_Score for each offer using the formula: `active_campaign_priority * 3 + saved_count * 1 + views_last_7_days * 0.5 + discount_percent * 0.2 + recency_score`.
3. Each Trending_Offer item SHALL contain `id`, `product_id`, `store_id`, `image_url`, `title`, `store_name`, `discount_percent`, `original_price`, `discounted_price`, `saved_count`, `interest_id`, `activity_id`, and `is_favorite` fields.
4. WHEN fewer than 5 eligible trending offers exist, THE Explore_Service SHALL return all available eligible offers without error.
5. THE Explore_Service SHALL apply `interest_id`, `activity_id`, and `search` filters to the trending section when provided as query parameters.

### Requirement 3: Flash Offers Section

**User Story:** As a user, I want to see time-limited deals with countdown timers, so that I can take advantage of expiring offers before they end.

#### Acceptance Criteria

1. WHEN the explore bootstrap endpoint is requested, THE Explore_Service SHALL return Flash_Offer items where `ends_at` is greater than the current server time and less than or equal to 24 hours from the current server time.
2. Each Flash_Offer item SHALL contain `id`, `product_id`, `store_id`, `image_url`, `title`, `store_name`, `discount_percent`, `expires_at`, `interest_id`, and `activity_id` fields.
3. THE Explore_Service SHALL map the `ends_at` database column to the `expires_at` response field.
4. THE Explore_Service SHALL sort flash offers by `ends_at` in ascending order so that the soonest-expiring offers appear first.
5. THE Explore_Service SHALL apply `interest_id`, `activity_id`, and `search` filters to the flash section when provided as query parameters.

### Requirement 4: Top Stores Section

**User Story:** As a user, I want to see the highest-rated stores with their best coupon preview, so that I can discover reputable stores with good deals.

#### Acceptance Criteria

1. WHEN the explore bootstrap endpoint is requested, THE Explore_Service SHALL return Top_Store items sorted by `rating_avg` in descending order.
2. Each Top_Store item SHALL contain `id`, `store_id`, `name`, `image_url`, `followers_count`, `rating`, `interest_id`, `activity_id`, `best_coupon_title`, `best_coupon_discount`, and `best_coupon_image_url` fields.
3. THE Explore_Service SHALL select the best coupon for each store as the active offer with the highest Discount_Percent.
4. THE Explore_Service SHALL only include stores that have at least one Active_Product with an Active_Offer.
5. THE Explore_Service SHALL apply `interest_id`, `activity_id`, and `search` filters to the top stores section when provided as query parameters.

### Requirement 5: Nearby Offers Section

**User Story:** As a user, I want to see offers from stores near my location, so that I can find deals I can physically visit.

#### Acceptance Criteria

1. WHEN the explore bootstrap endpoint is requested with `lat` and `lng` query parameters, THE Explore_Service SHALL return Nearby_Offer items sorted by geographic distance in ascending order.
2. Each Nearby_Offer item SHALL contain `id`, `product_id`, `store_id`, `image_url`, `title`, `store_name`, `original_price`, `discounted_price`, `save_percent`, `distance_km`, `interest_id`, and `activity_id` fields.
3. THE Explore_Service SHALL calculate `distance_km` using the Haversine formula based on the provided `lat`/`lng` and the store address `latitude`/`longitude` columns.
4. WHEN `lat` or `lng` query parameters are not provided, THE Explore_Service SHALL return an empty array for the `nearby` section.
5. THE Explore_Service SHALL apply `interest_id`, `activity_id`, and `search` filters to the nearby section when provided as query parameters.

### Requirement 6: Picked For You Paginated Endpoint

**User Story:** As a user, I want to browse a paginated feed of personalized offers with filtering and sorting options, so that I can discover relevant deals tailored to my preferences.

#### Acceptance Criteria

1. WHEN a GET request is made to `/api/v1/explore/picks`, THE Explore_Service SHALL return a paginated list of Picked_Offer items sourced from the same recommendation logic used by `/api/v1/me/recommendations/products`.
2. WHEN the user is authenticated, THE Explore_Service SHALL use the ProductRecommendationService to generate personalized product recommendations based on the user's interaction history and preferences.
3. WHEN the user is not authenticated, THE Explore_Service SHALL fall back to popular Active_Products with Active_Offers as the data source for picked offers.
4. Each Picked_Offer item SHALL contain `id`, `product_id`, `store_id`, `image_url`, `title`, `store_name`, `original_price`, `discounted_price`, `save_percent`, `interest_id`, `activity_id`, `is_favorite`, `created_at`, and `saved_count` fields.
5. THE Explore_Service SHALL return pagination metadata containing `page`, `page_size`, `total`, `total_pages`, and `has_more` fields.
6. THE Explore_Service SHALL default to page 1 with a page size of 12 when pagination parameters are not provided.
7. THE Explore_Service SHALL only include offers from Active_Products with an Active_Offer belonging to stores with an active or approved status.
8. WHEN the request includes a valid Bearer token, THE Explore_Service SHALL populate the `is_favorite` field with the authenticated user's actual favorite status.
9. WHEN the request does not include a Bearer token, THE Explore_Service SHALL set `is_favorite` to `false` for all offers.
10. THE Explore_Service SHALL apply `interest_id`, `activity_id`, `search`, `min_discount_percent`, and `sort_by` filters on top of the recommendation results when provided as query parameters.

### Requirement 7: Filter by Interest

**User Story:** As a user, I want to filter explore results by product category, so that I can see offers relevant to my interests.

#### Acceptance Criteria

1. WHEN the `interest_id` query parameter is provided, THE Explore_Service SHALL filter results to only include products that belong to the specified category via the `product_categories` pivot table.
2. THE Explore_Service SHALL apply the `interest_id` filter to all sections: trending, flash, top stores, nearby, and picked-for-you.
3. IF the provided `interest_id` does not correspond to an existing active category, THEN THE Explore_Service SHALL return a 400 Bad Request response with a descriptive error message.

### Requirement 8: Filter by Activity

**User Story:** As a user, I want to filter explore results by store category, so that I can see offers from specific types of businesses.

#### Acceptance Criteria

1. WHEN the `activity_id` query parameter is provided, THE Explore_Service SHALL filter results to only include products from stores that belong to the specified store category via the `store_store_category` pivot table.
2. THE Explore_Service SHALL apply the `activity_id` filter to all sections: trending, flash, top stores, nearby, and picked-for-you.
3. IF the provided `activity_id` does not correspond to an existing active store category, THEN THE Explore_Service SHALL return a 400 Bad Request response with a descriptive error message.

### Requirement 9: Search Filter

**User Story:** As a user, I want to search for offers by name or store, so that I can find specific deals quickly.

#### Acceptance Criteria

1. WHEN the `search` query parameter is provided, THE Explore_Service SHALL filter results to include only offers where the offer title or store name contains the search term.
2. THE Explore_Service SHALL perform case-insensitive matching for the search filter.
3. THE Explore_Service SHALL support Arabic text in the search filter without transliteration or normalization issues.
4. THE Explore_Service SHALL apply the search filter to all sections: trending, flash, top stores, nearby, and picked-for-you.

### Requirement 10: Minimum Discount Filter

**User Story:** As a user, I want to filter offers by minimum discount percentage, so that I can find deals that meet my savings threshold.

#### Acceptance Criteria

1. WHEN the `min_discount_percent` query parameter is provided on the `/api/v1/explore/picks` endpoint, THE Explore_Service SHALL filter results to include only offers where the calculated Discount_Percent is greater than or equal to the specified value.
2. THE Explore_Service SHALL validate that `min_discount_percent` is an integer between 0 and 90 inclusive.
3. IF the `min_discount_percent` value is outside the valid range, THEN THE Explore_Service SHALL return a 422 Unprocessable Entity response with a validation error message.

### Requirement 11: Sort Options

**User Story:** As a user, I want to sort the picked-for-you feed by different criteria, so that I can prioritize the type of deals I care about most.

#### Acceptance Criteria

1. WHEN the `sort_by` query parameter is set to `trending`, THE Explore_Service SHALL sort results by Trending_Score in descending order.
2. WHEN the `sort_by` query parameter is set to `newest`, THE Explore_Service SHALL sort results by offer `created_at` timestamp in descending order.
3. WHEN the `sort_by` query parameter is set to `most_saved`, THE Explore_Service SHALL sort results by `favorites_count` in descending order.
4. WHEN the `sort_by` query parameter is set to `highest_discount`, THE Explore_Service SHALL sort results by Discount_Percent in descending order.
5. IF the `sort_by` value is not one of `trending`, `newest`, `most_saved`, or `highest_discount`, THEN THE Explore_Service SHALL return a 422 Unprocessable Entity response with a validation error message.
6. WHEN the `sort_by` parameter is not provided, THE Explore_Service SHALL default to `trending` sort order.

### Requirement 12: Pagination Validation

**User Story:** As a platform operator, I want pagination parameters to be validated, so that the API rejects invalid requests and prevents excessive resource usage.

#### Acceptance Criteria

1. THE Explore_Service SHALL validate that the `page` parameter is an integer greater than or equal to 1.
2. THE Explore_Service SHALL validate that the `page_size` parameter is an integer between 1 and 50 inclusive.
3. WHEN the `page_size` parameter is not provided, THE Explore_Service SHALL default to 12.
4. IF the `page` or `page_size` values are outside valid ranges, THEN THE Explore_Service SHALL return a 422 Unprocessable Entity response with a validation error message.

### Requirement 13: Favorites Count Denormalization

**User Story:** As a platform operator, I want a denormalized favorites count on products, so that sorting by popularity does not require expensive join queries.

#### Acceptance Criteria

1. THE Explore_Service SHALL add a `favorites_count` column (unsigned integer, default 0) to the `products` table via a database migration.
2. WHEN a user adds a product to favorites via `POST /api/v1/products/{productId}/favorites`, THE Explore_Service SHALL increment the `favorites_count` column on the corresponding product by 1.
3. WHEN a user removes a product from favorites via `DELETE /api/v1/products/{productId}/favorites`, THE Explore_Service SHALL decrement the `favorites_count` column on the corresponding product by 1.
4. THE Explore_Service SHALL provide a one-time artisan command to synchronize the `favorites_count` column with the actual count from the `product_favorites` table for all products.
5. THE Explore_Service SHALL ensure `favorites_count` is never decremented below 0.

### Requirement 14: Optional Authentication

**User Story:** As a guest user, I want to browse the Explore page without logging in, so that I can discover offers before creating an account.

#### Acceptance Criteria

1. WHEN a request to `/api/v1/explore` or `/api/v1/explore/picks` does not include a Bearer token, THE Explore_Service SHALL return a successful response with `is_favorite` set to `false` for all items.
2. WHEN a request includes an invalid or expired Bearer token, THE Explore_Service SHALL treat the request as unauthenticated and return a successful response with `is_favorite` set to `false` for all items.
3. THE Explore_Service SHALL not return a 401 error for explore endpoints when authentication is missing.

### Requirement 15: Combined Filter Logic

**User Story:** As a user, I want to apply multiple filters simultaneously, so that I can narrow down results to exactly what I am looking for.

#### Acceptance Criteria

1. WHEN multiple filter parameters are provided simultaneously, THE Explore_Service SHALL apply all filters using AND logic, returning only results that satisfy every active filter condition.
2. THE Explore_Service SHALL support combining `interest_id`, `activity_id`, `search`, `min_discount_percent`, and `sort_by` parameters in a single request.
3. WHEN combined filters produce zero results, THE Explore_Service SHALL return an empty data array with valid pagination metadata showing `total: 0` and `has_more: false`.

### Requirement 16: Response Field Mapping

**User Story:** As a Flutter developer, I want consistent field names in API responses, so that the app can deserialize data without transformation logic.

#### Acceptance Criteria

1. THE Explore_Service SHALL map the `ProductOffer.ends_at` database column to `expires_at` in Flash_Offer response objects.
2. THE Explore_Service SHALL derive `image_url` from the primary product image (the first image by sort order) for offer response objects.
3. THE Explore_Service SHALL derive `store_name` from the related store's `name` field for all offer response objects.
4. THE Explore_Service SHALL derive `interest_id` as the first category ID from the product's categories relationship.
5. THE Explore_Service SHALL derive `activity_id` as the first store category ID from the product's store's categories relationship.
6. THE Explore_Service SHALL calculate `discounted_price` by applying the offer discount to the product's `base_price`.
7. THE Explore_Service SHALL set `original_price` to the product's `base_price` value.
