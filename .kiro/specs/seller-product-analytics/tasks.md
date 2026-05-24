# Implementation Plan: Seller Product Analytics

## Overview

This plan implements the Seller Product Analytics feature for the Coupony platform. It covers the database migration, domain services (GrowthCalculator, PercentageNormalizer, PeriodResolver, HeatmapBuilder), action classes, controller, form requests, route registration, caching, and property-based/unit tests. Each task builds incrementally on the previous, ensuring no orphaned code.

## Tasks

- [x] 1. Database migration and model update
  - [x] 1.1 Create migration to add `monthly_goal` column to `stores` table
    - Create migration file adding `unsignedInteger('monthly_goal')->nullable()->after('followers_count')` to the `stores` table
    - Add `monthly_goal` to the Store model's `$fillable` array
    - _Requirements: 2.1, 2.5_

- [x] 2. Implement domain services
  - [x] 2.1 Implement `GrowthCalculator` service
    - Create `app/Domain/Analytics/Services/GrowthCalculator.php`
    - Implement static `calculate(int|float $current, int|float $previous): float` method
    - Return `0.0` when previous is zero; otherwise `round(((current - previous) / previous) * 100, 1)`
    - _Requirements: 3.2, 3.3, 4.2, 4.3, 14.1, 14.2, 14.3, 14.4_

  - [x] 2.2 Write property test for GrowthCalculator
    - Create `tests/Unit/Domain/Analytics/GrowthCalculatorTest.php`
    - Use `@dataProvider` with Faker to generate 100+ random (current, previous) pairs including zeros
    - **Property 1: Growth Percentage Calculation Safety**
    - **Validates: Requirements 3.2, 3.3, 4.2, 4.3, 14.1, 14.2, 14.3, 14.4**

  - [x] 2.3 Implement `PercentageNormalizer` service
    - Create `app/Domain/Analytics/Services/PercentageNormalizer.php`
    - Implement static `normalize(array $values): array` method
    - Round each value to 1 decimal place, adjust largest value so sum equals exactly `100.0`
    - Return empty array if input is empty or all zeros
    - _Requirements: 5.3, 9.3, 11.2, 11.5, 11.6, 15.1, 15.2, 15.3_

  - [x] 2.4 Write property test for PercentageNormalizer
    - Create `tests/Unit/Domain/Analytics/PercentageNormalizerTest.php`
    - Use `@dataProvider` with Faker to generate random arrays of 1-10 positive floats (100 iterations)
    - **Property 2: Percentage Array Normalization**
    - **Validates: Requirements 5.3, 9.3, 11.2, 11.5, 11.6, 15.1, 15.2, 15.3**

  - [x] 2.5 Implement `PeriodResolver` service
    - Create `app/Domain/Analytics/Services/PeriodResolver.php`
    - Implement static `resolve(string $period): array` returning `[currentStart, currentEnd, previousStart, previousEnd]` as Carbon instances
    - Handle `all`, `today`, `last_7_days`, `this_month`, `this_year` periods
    - For `all` period, return `null` for previous range dates
    - _Requirements: 1.1, 1.2_

  - [x] 2.6 Implement `HeatmapBuilder` service
    - Create `app/Domain/Analytics/Services/HeatmapBuilder.php`
    - Implement static `build(Collection $redemptions): array` method
    - Always return exactly 28 buckets (7 days × 4 time windows: morning, afternoon, evening, night)
    - Map each redemption timestamp to its day + time_window bucket and increment count
    - _Requirements: 6.1, 6.2, 6.3_

  - [x] 2.7 Write property test for HeatmapBuilder
    - Create `tests/Unit/Domain/Analytics/HeatmapBuilderTest.php`
    - Use `@dataProvider` with Faker to generate random collections of 0-500 timestamps (100 iterations)
    - **Property 3: Heatmap Structure Invariant**
    - **Validates: Requirements 6.1, 6.2, 6.3**

- [x] 3. Implement form requests
  - [x] 3.1 Create `SellerDashboardRequest` form request
    - Create `app/Application/Http/Requests/SellerDashboardRequest.php`
    - Validate optional `period` query parameter with rule `in:all,today,last_7_days,this_month,this_year`
    - Default `period` to `all` when omitted (use `prepareForValidation`)
    - _Requirements: 1.2, 1.3_

  - [x] 3.2 Create `UpdateMonthlyGoalRequest` form request
    - Create `app/Application/Http/Requests/UpdateMonthlyGoalRequest.php`
    - Validate `goal` field as required, integer, min:1
    - _Requirements: 2.2, 2.3_

  - [x] 3.3 Create `ProductAnalyticsRequest` form request
    - Create `app/Application/Http/Requests/ProductAnalyticsRequest.php`
    - Validate optional `period` query parameter with same rules as dashboard request
    - Default `period` to `all` when omitted
    - _Requirements: 8.1, 9.1_

- [x] 4. Checkpoint - Ensure services and requests compile
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement actions
  - [x] 5.1 Implement `GetSellerDashboardAction`
    - Create `app/Domain/Analytics/Actions/GetSellerDashboardAction.php`
    - Accept Store model and period string as inputs
    - Check cache with key `seller_analytics:{store_id}:{period}`, return cached if hit
    - On cache miss: compute monthly_goal (from store column), new_followers (StoreFollowers count in period with growth), store_visits (ProductView count in period with growth), offer_distribution (ProductOffer types normalized), peak_redemption_times (HeatmapBuilder from OfferClaim), top_performing_offers (top 10 by usage_count)
    - Cache result for 15 minutes
    - Handle zero-data by returning complete structure with zeros/nulls/empty arrays
    - _Requirements: 1.1, 1.4, 1.5, 2.5, 2.6, 3.1, 3.4, 4.1, 4.4, 5.1, 5.2, 5.4, 6.1, 7.1, 7.2, 7.3, 7.4, 13.1, 13.3_

  - [x] 5.2 Write property test for top performing offers ordering
    - Add test in `tests/Feature/Analytics/SellerDashboardTest.php`
    - Use `@dataProvider` with Faker to generate random offer sets of 0-50 items
    - **Property 4: Top Performing Offers Ordering and Limit**
    - **Validates: Requirements 7.1, 7.2**

  - [x] 5.3 Implement `UpdateMonthlyGoalAction`
    - Create `app/Domain/Analytics/Actions/UpdateMonthlyGoalAction.php`
    - Accept Store model and goal integer
    - Persist goal to `stores.monthly_goal` column
    - Invalidate all cached dashboard entries for the store (all period variants)
    - Return updated goal value
    - _Requirements: 2.1, 2.4_

  - [x] 5.4 Write property test for monthly goal round-trip
    - Add test in `tests/Feature/Analytics/UpdateMonthlyGoalTest.php`
    - Use `@dataProvider` with Faker to generate random positive integers (100 iterations)
    - **Property 8: Monthly Goal Persistence Round-Trip**
    - **Validates: Requirements 2.1**

  - [x] 5.5 Implement `GetProductAnalyticsAction`
    - Create `app/Domain/Analytics/Actions/GetProductAnalyticsAction.php`
    - Accept Product model and period string as inputs
    - Check cache with key `product_analytics:{product_id}:{period}`, return cached if hit
    - On cache miss: compute header (views, likes, comments, saves counts), overview (impressions, reached_accounts, profile_visits, new_followers, traffic_sources normalized), engagement (total_interactions, engagement_rate, trend series, action_breakdown), audience (followers_percent, non_followers_percent, age_groups, gender_groups normalized)
    - Cache result for 1 hour
    - Handle zero-data with complete structure, default 50/50 follower split, equal age distribution
    - _Requirements: 8.1, 8.2, 8.3, 9.1, 9.2, 9.3, 9.4, 10.1, 10.2, 10.3, 10.4, 10.5, 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.7, 13.2, 13.3_

- [x] 6. Implement controller and routes
  - [x] 6.1 Create `SellerAnalyticsController`
    - Create `app/Application/Http/Controllers/API/V1/SellerAnalyticsController.php`
    - Implement `dashboard()` method: resolve seller's store, call `GetSellerDashboardAction`, return JSON
    - Implement `updateMonthlyGoal()` method: resolve seller's store, call `UpdateMonthlyGoalAction`, return JSON
    - Implement `productAnalytics()` method: resolve product, verify ownership, call `GetProductAnalyticsAction`, return JSON
    - Handle authorization: 403 if user doesn't own/manage store, 403 if product not in store, 404 if product not found
    - _Requirements: 12.1, 12.2, 12.3, 12.4_

  - [x] 6.2 Register routes in `routes/api.php`
    - Add seller analytics routes within the `auth:sanctum` middleware group
    - `GET /api/v1/seller/analytics` → `dashboard`
    - `PATCH /api/v1/seller/analytics/monthly-goal` → `updateMonthlyGoal`
    - `GET /api/v1/seller/products/{productId}/analytics` → `productAnalytics`
    - _Requirements: 1.1, 2.1, 8.1_

- [x] 7. Checkpoint - Ensure endpoints are functional
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Write feature tests
  - [x] 8.1 Write feature tests for seller dashboard endpoint
    - Create `tests/Feature/Analytics/SellerDashboardTest.php`
    - Test: 401 without token, 403 for wrong store, valid response structure, period default to `all`, invalid period returns 422, cache hit behavior, zero-data response
    - **Property 5: Invalid Period Rejection** (use `@dataProvider` with random invalid strings)
    - **Property 7: Response Shape Consistency** (use `@dataProvider` with stores having random data presence)
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 12.1, 12.2, 13.1, 13.3**

  - [x] 8.2 Write feature tests for monthly goal endpoint
    - Create `tests/Feature/Analytics/UpdateMonthlyGoalTest.php`
    - Test: valid goal update, invalid goal returns 422, cache invalidation, null goal returns 0 achievement
    - **Property 6: Invalid Goal Rejection** (use `@dataProvider` with random non-positive-integer values)
    - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6**

  - [x] 8.3 Write feature tests for product analytics endpoint
    - Create `tests/Feature/Analytics/ProductAnalyticsTest.php`
    - Test: 401 without token, 403 for product not in store, 404 for missing product, valid response structure, cache hit, zero-data response, engagement rate calculation, audience defaults
    - **Validates: Requirements 8.1, 8.2, 8.3, 9.1, 9.4, 10.1, 10.3, 11.7, 12.1, 12.3, 12.4, 13.2**

- [x] 9. Write unit tests for PeriodResolver
  - [x] 9.1 Write unit tests for PeriodResolver
    - Create `tests/Unit/Domain/Analytics/PeriodResolverTest.php`
    - Test each period value returns correct Carbon date ranges
    - Test `all` period returns null for previous range
    - _Requirements: 1.1, 1.2_

- [x] 10. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document
- Unit tests validate specific examples and edge cases
- The project uses Laravel's domain-driven architecture under `app/Domain/Analytics/`
- Caching uses Laravel's Cache facade (Redis or File driver depending on environment)
- All services are stateless with static methods for simplicity

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1"] },
    { "id": 1, "tasks": ["2.1", "2.3", "2.5", "2.6", "3.1", "3.2", "3.3"] },
    { "id": 2, "tasks": ["2.2", "2.4", "2.7"] },
    { "id": 3, "tasks": ["5.1", "5.3", "5.5"] },
    { "id": 4, "tasks": ["5.2", "5.4", "6.1"] },
    { "id": 5, "tasks": ["6.2"] },
    { "id": 6, "tasks": ["8.1", "8.2", "8.3", "9.1"] }
  ]
}
```
