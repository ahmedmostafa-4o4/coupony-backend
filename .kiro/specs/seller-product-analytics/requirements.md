# Requirements Document

## Introduction

This feature provides two analytics systems for the Coupony seller backend: a Seller Analytics Dashboard offering store-level performance metrics, and a Product Analytics endpoint delivering per-product engagement and audience insights. Both endpoints serve authenticated sellers with cached, period-filtered data and handle zero-data scenarios gracefully.

## Glossary

- **Analytics_Service**: The backend service responsible for computing and returning analytics data for stores and products.
- **Seller_Dashboard**: The API endpoint that returns aggregated store-level analytics including goals, followers, visits, offer distribution, redemption heatmap, and top offers.
- **Product_Analytics**: The API endpoint that returns per-product metrics including header stats, overview, engagement, and audience breakdown.
- **Period_Filter**: A query parameter that constrains analytics data to a time range. Valid values: `all`, `today`, `last_7_days`, `this_month`, `this_year`.
- **Monthly_Goal**: A numeric target set by the seller representing desired monthly redemptions or revenue.
- **Growth_Percent**: A calculated percentage representing change between the current period and the previous equivalent period.
- **Heatmap_Bucket**: One of 28 time slots (7 days × 4 time windows) representing redemption frequency.
- **Time_Window**: A 6-hour block within a day: morning (06:00–11:59), afternoon (12:00–17:59), evening (18:00–23:59), night (00:00–05:59).
- **Offer_Type**: The type of product offer. Values: `fixed`, `percentage`, `buy_x_get_y`.
- **Engagement_Rate**: The ratio of total interactions (likes + comments + saves) to total impressions, expressed as a percentage.
- **Traffic_Source**: The origin from which a user discovered a product. Values: `search`, `explore`, `profile`, `direct`, `recommendation`.
- **Store**: An active seller store in the Coupony platform, identified by UUID.
- **Product**: A product belonging to a store, identified by UUID.
- **Authenticated_Seller**: A user with a valid Bearer token who owns or manages the target store.

## Requirements

### Requirement 1: Seller Dashboard Retrieval

**User Story:** As a seller, I want to view my store's analytics dashboard, so that I can understand my store's performance across key metrics.

#### Acceptance Criteria

1. WHEN a GET request is made to `/api/v1/seller/analytics` with a valid `period` parameter, THE Analytics_Service SHALL return a JSON response containing monthly_goal, new_followers, store_visits, offer_distribution, peak_redemption_times, and top_performing_offers data.
2. WHEN the `period` parameter is omitted, THE Analytics_Service SHALL default to `all` and return analytics for the entire store lifetime.
3. WHEN the `period` parameter value is not one of `all`, `today`, `last_7_days`, `this_month`, or `this_year`, THE Analytics_Service SHALL return a 422 Unprocessable Entity response with a validation error message.
4. THE Analytics_Service SHALL cache the seller dashboard response for 15 minutes using the store ID and period as cache key components.
5. WHEN a cached response exists for the requested store and period, THE Analytics_Service SHALL return the cached response without recomputing metrics.

### Requirement 2: Monthly Goal Management

**User Story:** As a seller, I want to set and update my monthly goal, so that I can track my store's progress toward a target.

#### Acceptance Criteria

1. WHEN a PATCH request is made to `/api/v1/seller/analytics/monthly-goal` with a valid `goal` value, THE Analytics_Service SHALL persist the monthly goal for the authenticated seller's store.
2. THE Analytics_Service SHALL validate that the `goal` field is a positive integer greater than zero.
3. IF the `goal` field is missing or invalid, THEN THE Analytics_Service SHALL return a 422 Unprocessable Entity response with a descriptive validation error.
4. WHEN the monthly goal is updated, THE Analytics_Service SHALL invalidate the cached seller dashboard response for that store.
5. THE Analytics_Service SHALL include the current goal value and achievement percentage in the seller dashboard response.
6. WHEN no monthly goal has been set, THE Analytics_Service SHALL return `null` for the goal value and `0` for the achievement percentage.

### Requirement 3: Followers Analytics

**User Story:** As a seller, I want to see my new followers count and growth percentage, so that I can measure my store's audience growth.

#### Acceptance Criteria

1. WHEN the seller dashboard is requested, THE Analytics_Service SHALL return the count of new followers gained during the selected period.
2. THE Analytics_Service SHALL calculate the growth percentage by comparing the current period follower count to the previous equivalent period count.
3. IF the previous period follower count is zero, THEN THE Analytics_Service SHALL return `0.0` as the growth percentage instead of producing a division-by-zero error.
4. WHEN the store has no followers in the selected period, THE Analytics_Service SHALL return `0` for the count and `0.0` for the growth percentage.

### Requirement 4: Store Visits Analytics

**User Story:** As a seller, I want to see my store visit count and growth percentage, so that I can understand traffic trends.

#### Acceptance Criteria

1. WHEN the seller dashboard is requested, THE Analytics_Service SHALL return the total product view count for the store during the selected period as the store visits metric.
2. THE Analytics_Service SHALL calculate the growth percentage by comparing the current period visit count to the previous equivalent period count.
3. IF the previous period visit count is zero, THEN THE Analytics_Service SHALL return `0.0` as the growth percentage instead of producing a division-by-zero error.
4. WHEN the store has no visits in the selected period, THE Analytics_Service SHALL return `0` for the count and `0.0` for the growth percentage.

### Requirement 5: Offer Distribution

**User Story:** As a seller, I want to see a breakdown of my offers by type, so that I can understand my offer mix.

#### Acceptance Criteria

1. WHEN the seller dashboard is requested, THE Analytics_Service SHALL return an array of objects containing `type` and `percentage` fields representing the distribution of active offers by Offer_Type.
2. THE Analytics_Service SHALL calculate each type's percentage as the count of offers of that type divided by the total offer count, multiplied by 100.
3. THE Analytics_Service SHALL ensure that all percentage values in the offer distribution array sum to exactly `100.0` when offers exist.
4. WHEN the store has no offers, THE Analytics_Service SHALL return an empty array for the offer distribution.

### Requirement 6: Peak Redemption Times Heatmap

**User Story:** As a seller, I want to see when my offers are most frequently redeemed, so that I can optimize my marketing timing.

#### Acceptance Criteria

1. WHEN the seller dashboard is requested, THE Analytics_Service SHALL return a heatmap array containing exactly 28 Heatmap_Bucket objects, one for each combination of 7 days (Monday through Sunday) and 4 Time_Windows.
2. Each Heatmap_Bucket SHALL contain `day`, `time_window`, and `count` fields representing the number of redemptions in that slot during the selected period.
3. WHEN the store has no redemptions in the selected period, THE Analytics_Service SHALL return all 28 buckets with a count of `0`.

### Requirement 7: Top Performing Offers

**User Story:** As a seller, I want to see my best-performing offers, so that I can replicate successful strategies.

#### Acceptance Criteria

1. WHEN the seller dashboard is requested, THE Analytics_Service SHALL return a list of top performing offers sorted by total usage count in descending order.
2. THE Analytics_Service SHALL limit the top performing offers list to a maximum of 10 items.
3. Each top performing offer item SHALL contain the product title, offer type, offer label, and total usage count.
4. WHEN the store has no redeemed offers in the selected period, THE Analytics_Service SHALL return an empty array.

### Requirement 8: Product Analytics Header Metrics

**User Story:** As a seller, I want to see key metrics for a specific product at a glance, so that I can quickly assess its performance.

#### Acceptance Criteria

1. WHEN a GET request is made to `/api/v1/seller/products/{productId}/analytics`, THE Product_Analytics SHALL return header metrics containing views count, likes count, comments count, and saves count for the specified product.
2. THE Product_Analytics SHALL cache the response for 1 hour using the product ID as a cache key component.
3. WHEN the product has no interactions, THE Product_Analytics SHALL return `0` for all header metric counts.

### Requirement 9: Product Analytics Overview

**User Story:** As a seller, I want to see an overview of my product's reach and discovery, so that I can understand how users find my product.

#### Acceptance Criteria

1. WHEN product analytics are requested, THE Product_Analytics SHALL return overview data containing impressions count, reached accounts count, profile visits count, new followers count, and top traffic sources.
2. THE Product_Analytics SHALL return traffic sources as an array of objects with `source` and `percentage` fields.
3. THE Product_Analytics SHALL ensure that all traffic source percentage values sum to exactly `100.0` when traffic data exists.
4. WHEN the product has no traffic data, THE Product_Analytics SHALL return an empty array for traffic sources and `0` for all overview counts.

### Requirement 10: Product Analytics Engagement

**User Story:** As a seller, I want to understand how users interact with my product over time, so that I can identify engagement patterns.

#### Acceptance Criteria

1. WHEN product analytics are requested, THE Product_Analytics SHALL return engagement data containing total interactions count, engagement rate, trend series, and action breakdown.
2. THE Product_Analytics SHALL calculate the engagement rate as total interactions divided by impressions, multiplied by 100, rounded to two decimal places.
3. IF the impressions count is zero, THEN THE Product_Analytics SHALL return `0.0` as the engagement rate instead of producing a division-by-zero error.
4. THE Product_Analytics SHALL return the trend series as an array of data points with `date` and `count` fields, grouped by day for periods up to 30 days and by month for longer periods.
5. THE Product_Analytics SHALL return the action breakdown as an object with `likes`, `comments`, `saves`, and `shares` counts.

### Requirement 11: Product Analytics Audience

**User Story:** As a seller, I want to understand the demographics of users engaging with my product, so that I can tailor my marketing.

#### Acceptance Criteria

1. WHEN product analytics are requested, THE Product_Analytics SHALL return audience data containing followers versus non-followers percentage split, age group distribution, and gender group distribution.
2. THE Product_Analytics SHALL return the followers versus non-followers split as an object with `followers_percent` and `non_followers_percent` fields that sum to exactly `100.0`.
3. THE Product_Analytics SHALL return age groups as an array of objects with `range` and `percentage` fields for the following 7 groups: `13-17`, `18-24`, `25-34`, `35-44`, `45-54`, `55-64`, `65+`.
4. THE Product_Analytics SHALL return gender groups as an array of objects with `gender` and `percentage` fields.
5. THE Product_Analytics SHALL ensure that all age group percentage values sum to exactly `100.0` when audience data exists.
6. THE Product_Analytics SHALL ensure that all gender group percentage values sum to exactly `100.0` when audience data exists.
7. WHEN the product has no audience data, THE Product_Analytics SHALL return `50.0` for both followers and non-followers percentages, equal distribution across age groups, and an empty array for gender groups.

### Requirement 12: Authentication and Authorization

**User Story:** As a platform operator, I want analytics endpoints to be secured, so that only authorized sellers can access their own data.

#### Acceptance Criteria

1. WHEN a request is made without a valid Bearer token, THE Analytics_Service SHALL return a 401 Unauthorized response.
2. WHEN an authenticated user requests analytics for a store they do not own or manage, THE Analytics_Service SHALL return a 403 Forbidden response.
3. WHEN an authenticated user requests product analytics for a product that does not belong to their store, THE Analytics_Service SHALL return a 403 Forbidden response.
4. WHEN a product analytics request references a non-existent product ID, THE Analytics_Service SHALL return a 404 Not Found response.

### Requirement 13: Zero-Data Handling

**User Story:** As a new seller, I want analytics endpoints to return meaningful responses even when my store has no data, so that the dashboard renders correctly.

#### Acceptance Criteria

1. WHEN a store has no historical data, THE Analytics_Service SHALL return a complete response structure with zero counts, empty arrays, and null goal values rather than an error.
2. WHEN a product has no historical data, THE Product_Analytics SHALL return a complete response structure with zero counts, empty arrays, and default percentage splits rather than an error.
3. THE Analytics_Service SHALL use consistent response shapes regardless of whether data exists, ensuring the client can parse responses without conditional logic.

### Requirement 14: Growth Percentage Calculation

**User Story:** As a seller, I want growth percentages to be calculated safely and consistently, so that I can trust the displayed trends.

#### Acceptance Criteria

1. THE Analytics_Service SHALL calculate growth percentage using the formula: `((current - previous) / previous) * 100`, rounded to one decimal place.
2. IF the previous period value is zero and the current period value is also zero, THEN THE Analytics_Service SHALL return `0.0` as the growth percentage.
3. IF the previous period value is zero and the current period value is greater than zero, THEN THE Analytics_Service SHALL return `0.0` as the growth percentage to avoid infinity.
4. THE Analytics_Service SHALL support negative growth percentages when the current period value is less than the previous period value.

### Requirement 15: Percentage Array Normalization

**User Story:** As a frontend developer, I want all percentage arrays to sum to exactly 100.0, so that charts render correctly without rounding discrepancies.

#### Acceptance Criteria

1. THE Analytics_Service SHALL normalize all percentage arrays (offer distribution, traffic sources, age groups, gender groups, follower split) so that their values sum to exactly `100.0`.
2. WHEN rounding individual percentages causes the sum to deviate from `100.0`, THE Analytics_Service SHALL adjust the largest value to compensate for the rounding difference.
3. THE Analytics_Service SHALL round all individual percentage values to one decimal place before applying normalization.
