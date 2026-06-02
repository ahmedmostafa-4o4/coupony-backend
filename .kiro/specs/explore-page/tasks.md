# Implementation Plan: Explore Page API

## Overview

This plan implements the backend API endpoints for the Explore page in the Coupony Flutter app. The implementation follows the existing domain-driven architecture, creating a new `Explore` domain with services, actions, utility classes, and two public endpoints (`GET /api/v1/explore` and `GET /api/v1/explore/picks`). A database migration adds the denormalized `favorites_count` column, and the existing favorites actions are updated to maintain it.

## Tasks

- [x] 1. Database migration and favorites count infrastructure
  - [x] 1.1 Create migration to add `favorites_count` column to `products` table
    - Add unsigned integer column `favorites_count` with default 0 after `rating_count`
    - Add index on `favorites_count` for efficient sorting
    - _Requirements: 13.1_

  - [x] 1.2 Create `SyncFavoritesCount` artisan command
    - Create `app/Application/Console/Commands/SyncFavoritesCount.php`
    - Signature: `explore:sync-favorites-count`
    - Synchronize `favorites_count` with actual count from `product_favorites` table for all products
    - Use chunked updates for memory efficiency
    - _Requirements: 13.4_

  - [x] 1.3 Modify existing favorite/unfavorite actions to maintain `favorites_count`
    - Update `FavoriteProduct` action to increment `favorites_count` atomically
    - Update `UnfavoriteProduct` action to decrement using `GREATEST(favorites_count - 1, 0)` to prevent negative values
    - _Requirements: 13.2, 13.3, 13.5_

- [x] 2. Utility classes and calculators
  - [x] 2.1 Create `TrendingScoreCalculator` utility class
    - Create `app/Domain/Explore/Support/TrendingScoreCalculator.php`
    - Implement static `calculate()` method with formula: `campaign_priority * 3 + saved_count * 1 + views_last_7_days * 0.5 + discount_percent * 0.2 + recency_score`
    - _Requirements: 2.2_

  - [x] 2.2 Write property test for TrendingScoreCalculator
    - **Property 4: Trending Score Calculation**
    - **Validates: Requirements 2.2**
    - Use `@dataProvider` with Faker to generate 100 random (priority, saved, views, discount, recency) tuples
    - Verify formula output matches expected calculation for all inputs

  - [x] 2.3 Create `HaversineCalculator` utility class
    - Create `app/Domain/Explore/Support/HaversineCalculator.php`
    - Implement static `distanceKm()` method using the Haversine formula
    - Return distance in kilometers between two coordinate pairs
    - _Requirements: 5.3_

  - [x] 2.4 Write property test for HaversineCalculator
    - **Property 8: Haversine Distance Calculation**
    - **Validates: Requirements 5.3**
    - Use `@dataProvider` with Faker to generate random coordinate pairs
    - Verify non-negative results, zero for identical points, and triangle inequality

  - [x] 2.5 Create `DiscountCalculator` utility class
    - Create `app/Domain/Explore/Support/DiscountCalculator.php`
    - Implement static `calculate()` method handling percentage-type and fixed-type offers
    - Return `[discount_percent, discounted_price]` array
    - _Requirements: 16.6, 16.7_

  - [x] 2.6 Write property test for DiscountCalculator
    - **Property 11: Discount Calculation Correctness**
    - **Validates: Requirements 16.6, 16.7**
    - Use `@dataProvider` with Faker to generate random (base_price, percentage_value, fixed_amount) combinations
    - Verify percentage-type and fixed-type calculations produce correct results

- [~] 3. Checkpoint - Ensure utility classes and migration work correctly
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Form requests and validation
  - [x] 4.1 Create `ExploreBootstrapRequest` form request
    - Create `app/Application/Http/Requests/ExploreBootstrapRequest.php`
    - Validate: `interest_id` (nullable, integer, exists in categories where is_active=true), `activity_id` (nullable, integer, exists in store_categories where is_active=true), `search` (nullable, string, max:200), `lat` (nullable, numeric, between:-90,90), `lng` (nullable, numeric, between:-180,180, required_with:lat)
    - Return 400 for invalid interest_id/activity_id, 422 for other validation failures
    - _Requirements: 7.3, 8.3, 5.4_

  - [x] 4.2 Create `ExplorePicksRequest` form request
    - Create `app/Application/Http/Requests/ExplorePicksRequest.php`
    - Extend validation from bootstrap request plus: `page` (nullable, integer, min:1), `page_size` (nullable, integer, min:1, max:50), `min_discount_percent` (nullable, integer, min:0, max:90), `sort_by` (nullable, string, in:trending,newest,most_saved,highest_discount)
    - _Requirements: 10.2, 10.3, 11.5, 12.1, 12.2, 12.4_

- [x] 5. Explore service and actions
  - [x] 5.1 Create `ExploreService` with base query and section methods
    - Create `app/Domain/Explore/Services/ExploreService.php`
    - Implement base query scope for active products with active offers from active stores
    - Implement `getInterests()` returning all active categories with id, name, icon_url
    - Implement `getActivities()` returning all active store categories with id, name, icon_url
    - _Requirements: 1.2, 1.3, 1.7, 6.7_

  - [x] 5.2 Implement `getTrendingOffers()` in ExploreService
    - Query active products with trending score calculation (SQL computed column)
    - Apply interest_id, activity_id, and search filters
    - Sort by trending_score descending, limit between 5-10 results
    - Map response fields including is_favorite for authenticated users
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 5.3 Implement `getFlashOffers()` in ExploreService
    - Query offers where `ends_at > NOW()` and `ends_at <= NOW() + 24 hours`
    - Apply interest_id, activity_id, and search filters
    - Sort by ends_at ascending
    - Map `ends_at` to `expires_at` in response
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 5.4 Implement `getTopStores()` in ExploreService
    - Query stores with active products/offers, sorted by rating_avg descending
    - Select best coupon per store (highest discount active offer)
    - Apply interest_id, activity_id, and search filters
    - Include followers_count, rating, best_coupon_title, best_coupon_discount, best_coupon_image_url
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 5.5 Implement `getNearbyOffers()` in ExploreService
    - Use Haversine formula in SQL to calculate distance_km
    - Join with addresses table via addressables polymorphic relationship
    - Filter to stores with non-null latitude/longitude
    - Sort by distance ascending
    - Return empty array when lat/lng not provided
    - Apply interest_id, activity_id, and search filters
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 5.6 Implement `getPickedOffers()` in ExploreService
    - Use `ProductRecommendationService` for authenticated users
    - Fall back to popular active products for guest users
    - Apply filters: interest_id, activity_id, search, min_discount_percent
    - Apply sort_by options: trending, newest, most_saved, highest_discount (default: trending)
    - Return paginated results with metadata (page, page_size, total, total_pages, has_more)
    - Default page=1, page_size=12
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 10.1, 11.1, 11.2, 11.3, 11.4, 11.6, 12.3_

- [x] 6. Actions and controller
  - [x] 6.1 Create `GetExploreBootstrapAction`
    - Create `app/Domain/Explore/Actions/GetExploreBootstrapAction.php`
    - Orchestrate ExploreService to build response with all sections
    - Include server_time as UTC ISO 8601 timestamp
    - Handle optional user for is_favorite resolution
    - _Requirements: 1.1, 1.4, 1.5, 1.6_

  - [x] 6.2 Create `GetExplorePicksAction`
    - Create `app/Domain/Explore/Actions/GetExplorePicksAction.php`
    - Orchestrate ExploreService for paginated picks with filters and sorting
    - Handle optional user for is_favorite resolution
    - _Requirements: 6.1, 6.8, 6.9, 15.1, 15.2, 15.3_

  - [x] 6.3 Create `ExploreController` with bootstrap and picks methods
    - Create `app/Application/Http/Controllers/API/V1/ExploreController.php`
    - Implement `bootstrap()` method using GetExploreBootstrapAction
    - Implement `picks()` method using GetExplorePicksAction
    - Resolve optional authenticated user from Bearer token without throwing 401
    - _Requirements: 14.1, 14.2, 14.3_

  - [x] 6.4 Register explore routes in `routes/api.php`
    - Add `GET /api/v1/explore` route mapped to `ExploreController@bootstrap`
    - Add `GET /api/v1/explore/picks` route mapped to `ExploreController@picks`
    - Routes must NOT be wrapped in auth:sanctum middleware
    - _Requirements: 1.1, 6.1, 14.3_

- [~] 7. Checkpoint - Ensure core implementation compiles and basic routes work
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Response field mapping and integration
  - [x] 8.1 Implement response field mapping logic
    - Map `image_url` from primary product image (first by sort_order)
    - Map `store_name` from related store's name field
    - Map `interest_id` as first category ID from product's categories
    - Map `activity_id` as first store category ID from store's categories
    - Calculate `discounted_price` using DiscountCalculator
    - Set `original_price` to product's base_price
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5, 16.6, 16.7_

  - [x] 8.2 Implement search filter with Arabic text support
    - Case-insensitive LIKE matching on offer title and store name
    - Ensure UTF-8 collation handles Arabic text correctly
    - Apply search filter across all sections
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

  - [x] 8.3 Implement combined filter AND logic
    - Ensure all filters (interest_id, activity_id, search, min_discount_percent, sort_by) can be combined
    - Return empty data array with valid pagination metadata when no results match
    - _Requirements: 15.1, 15.2, 15.3_

- [ ] 9. Testing
  - [-] 9.1 Write feature tests for explore bootstrap endpoint
    - Test full response structure with all sections
    - Test optional auth (no token, valid token, expired token)
    - Test empty state returns empty arrays
    - Test filter application across all sections
    - _Requirements: 1.1, 1.5, 1.6, 14.1, 14.2, 14.3_

  - [-] 9.2 Write feature tests for explore picks endpoint
    - Test pagination with defaults and custom values
    - Test sort options (trending, newest, most_saved, highest_discount)
    - Test min_discount_percent filter
    - Test combined filters with AND logic
    - Test authenticated vs guest responses
    - _Requirements: 6.1, 6.5, 6.6, 10.1, 11.1, 11.2, 11.3, 11.4, 15.1_

  - [-] 9.3 Write property test for favorites count round-trip
    - **Property 14: Favorites Count Increment/Decrement Round-Trip**
    - **Validates: Requirements 13.2, 13.3, 13.4, 13.5**
    - Generate random sequences of favorite/unfavorite operations
    - Verify count never goes below 0 and sync command produces correct counts

  - [-] 9.4 Write feature tests for validation and error responses
    - Test invalid interest_id returns 400
    - Test invalid activity_id returns 400
    - Test invalid min_discount_percent returns 422
    - Test invalid sort_by returns 422
    - Test invalid page/page_size returns 422
    - _Requirements: 7.3, 8.3, 10.3, 11.5, 12.4_

- [~] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- The implementation uses PHP/Laravel following the existing domain-driven architecture
- Routes are public (no auth middleware) with optional user resolution from Bearer token
- The `favorites_count` column is maintained atomically to prevent race conditions

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "2.1", "2.3", "2.5"] },
    { "id": 1, "tasks": ["1.2", "1.3", "2.2", "2.4", "2.6", "4.1", "4.2"] },
    { "id": 2, "tasks": ["5.1"] },
    { "id": 3, "tasks": ["5.2", "5.3", "5.4", "5.5", "5.6"] },
    { "id": 4, "tasks": ["6.1", "6.2"] },
    { "id": 5, "tasks": ["6.3"] },
    { "id": 6, "tasks": ["6.4", "8.1", "8.2", "8.3"] },
    { "id": 7, "tasks": ["9.1", "9.2", "9.3", "9.4"] }
  ]
}
```
