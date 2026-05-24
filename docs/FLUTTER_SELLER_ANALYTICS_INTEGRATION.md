# Seller Product Analytics — Flutter Integration Guide

## Overview

The Seller Analytics system provides store-level performance metrics (dashboard) and per-product engagement insights. All endpoints require authentication via Laravel Sanctum (Bearer token) and are scoped to the authenticated seller's store.

**Base URL:** `https://api.coupony.shop/api/v1`

---

## Table of Contents

1. [Authentication](#authentication)
2. [API Endpoints](#api-endpoints)
3. [Period Filter](#period-filter)
4. [Response Structures](#response-structures)
5. [Zero-Data Handling](#zero-data-handling)
6. [Traffic Source Tracking](#traffic-source-tracking)
7. [Store Profile Show](#store-profile-show)
8. [Product Share Tracking](#product-share-tracking)
9. [Caching Behavior](#caching-behavior)
10. [Error Handling](#error-handling)
11. [Flutter Code Examples](#flutter-code-examples)
12. [UI Recommendations](#ui-recommendations)

---

## Authentication

All analytics endpoints require a valid Bearer token from Sanctum authentication. The seller must own or manage the store (employees need `ANALYTICS_VIEW` permission).

```
Authorization: Bearer {token}
Accept: application/json
Accept-Language: en  (or "ar" for Arabic)
```

---

## API Endpoints

All endpoints are prefixed with: `/api/v1/stores/{storeId}/analytics`

The `{storeId}` is the UUID of the store you want to view analytics for. The authenticated user must own or manage that store.

### 1. Get Seller Dashboard

```
GET /api/v1/stores/{storeId}/analytics?period={period}
```

Returns aggregated store-level analytics including monthly goal progress, follower growth, store visits, offer distribution, peak redemption heatmap, and top performing offers.

**Query Parameters:**

| Param | Type | Required | Default | Values |
|-------|------|----------|---------|--------|
| `period` | string | No | `all` | `all`, `today`, `last_7_days`, `this_month`, `this_year` |

**Response (200):**
```json
{
  "monthly_goal": {
    "goal": 100,
    "current": 45,
    "achievement_percent": 45.0
  },
  "new_followers": {
    "count": 23,
    "growth_percent": 15.2
  },
  "store_visits": {
    "count": 1250,
    "growth_percent": -3.4
  },
  "offer_distribution": [
    { "type": "fixed", "percentage": 50.0 },
    { "type": "percentage", "percentage": 33.3 },
    { "type": "buy_x_get_y", "percentage": 16.7 }
  ],
  "peak_redemption_times": [
    { "day": "monday", "time_window": "morning", "count": 12 },
    { "day": "monday", "time_window": "afternoon", "count": 8 },
    { "day": "monday", "time_window": "evening", "count": 5 },
    { "day": "monday", "time_window": "night", "count": 1 }
  ],
  "top_performing_offers": [
    {
      "product_title": "Premium Coffee",
      "offer_type": "percentage",
      "offer_label": "20% Off",
      "usage_count": 145
    }
  ]
}
```

**Response Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `monthly_goal.goal` | int \| null | The seller's monthly target (null if not set) |
| `monthly_goal.current` | int | Redemptions this month |
| `monthly_goal.achievement_percent` | float | Progress percentage (0.0 if no goal set) |
| `new_followers.count` | int | New followers in the selected period |
| `new_followers.growth_percent` | float | Growth vs previous period (rounded to 1 decimal) |
| `store_visits.count` | int | Total product views in the period |
| `store_visits.growth_percent` | float | Growth vs previous period |
| `offer_distribution` | array | Active offers grouped by type (percentages sum to 100.0) |
| `peak_redemption_times` | array | Always exactly 28 buckets (7 days × 4 time windows) |
| `top_performing_offers` | array | Top 10 offers sorted by usage_count descending |

---

### 2. Update Monthly Goal

```
PATCH /api/v1/stores/{storeId}/analytics/monthly-goal
```

Sets or updates the seller's monthly redemption goal. Invalidates the cached dashboard.

**Request Body:**
```json
{
  "goal": 150
}
```

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `goal` | integer | Yes | Must be a positive integer (min: 1) |

**Response (200):**
```json
{
  "goal": 150
}
```

---

### 3. Get Product Analytics

```
GET /api/v1/stores/{storeId}/analytics/products/{productId}?period={period}
```

Returns detailed analytics for a specific product. The product must belong to the seller's store.

**Path Parameters:**

| Param | Type | Description |
|-------|------|-------------|
| `productId` | UUID string | The product's unique identifier |

**Query Parameters:**

| Param | Type | Required | Default | Values |
|-------|------|----------|---------|--------|
| `period` | string | No | `all` | `all`, `today`, `last_7_days`, `this_month`, `this_year` |

**Response (200):**
```json
{
  "header": {
    "views": 1500,
    "likes": 230,
    "comments": 45,
    "saves": 89
  },
  "overview": {
    "impressions": 5000,
    "reached_accounts": 3200,
    "profile_visits": 180,
    "new_followers": 12,
    "traffic_sources": [
      { "source": "search", "percentage": 45.0 },
      { "source": "explore", "percentage": 30.0 },
      { "source": "profile", "percentage": 15.0 },
      { "source": "direct", "percentage": 7.0 },
      { "source": "recommendation", "percentage": 3.0 }
    ]
  },
  "engagement": {
    "total_interactions": 364,
    "engagement_rate": 7.28,
    "trend": [
      { "date": "2025-01-15", "count": 12 },
      { "date": "2025-01-16", "count": 18 }
    ],
    "action_breakdown": {
      "likes": 230,
      "comments": 45,
      "saves": 89,
      "shares": 0
    }
  },
  "audience": {
    "followers_percent": 65.0,
    "non_followers_percent": 35.0,
    "age_groups": [
      { "range": "13-17", "percentage": 5.0 },
      { "range": "18-24", "percentage": 35.0 },
      { "range": "25-34", "percentage": 30.0 },
      { "range": "35-44", "percentage": 15.0 },
      { "range": "45-54", "percentage": 8.0 },
      { "range": "55-64", "percentage": 5.0 },
      { "range": "65+", "percentage": 2.0 }
    ],
    "gender_groups": [
      { "gender": "male", "percentage": 55.0 },
      { "gender": "female", "percentage": 42.0 },
      { "gender": "other", "percentage": 3.0 }
    ]
  }
}
```

**Response Fields:**

| Section | Field | Type | Description |
|---------|-------|------|-------------|
| header | views | int | Total product views |
| header | likes | int | Total likes |
| header | comments | int | Total comments |
| header | saves | int | Total saves/favorites |
| overview | impressions | int | Total impressions (same as views) |
| overview | reached_accounts | int | Unique users who viewed |
| overview | profile_visits | int | Unique visitors by IP |
| overview | new_followers | int | Store followers gained in period |
| overview | traffic_sources | array | Source breakdown (sums to 100.0) |
| engagement | total_interactions | int | likes + comments + saves + shares |
| engagement | engagement_rate | float | (interactions / impressions) × 100, rounded to 2 decimals |
| engagement | trend | array | Daily or monthly data points |
| engagement | action_breakdown | object | Individual action counts |
| audience | followers_percent | float | % of interactors who follow the store |
| audience | non_followers_percent | float | % of interactors who don't follow |
| audience | age_groups | array | 7 age ranges (sums to 100.0) |
| audience | gender_groups | array | Gender breakdown (sums to 100.0) |

---

## Period Filter

All analytics endpoints accept an optional `period` query parameter:

| Value | Current Range | Compared Against |
|-------|--------------|------------------|
| `all` | Store/product creation → now | No comparison (growth = 0.0) |
| `today` | Today 00:00 → now | Yesterday |
| `last_7_days` | Past 7 days | Previous 7 days (days 8-14) |
| `this_month` | Month start → now | Previous month |
| `this_year` | Year start → now | Previous year |

When omitted, defaults to `all`.

---

## Zero-Data Handling

The API always returns a complete response structure, even for new stores with no data. You never need conditional parsing logic.

**Dashboard zero-data:**
- `monthly_goal`: `{ goal: null, current: 0, achievement_percent: 0.0 }`
- `new_followers`: `{ count: 0, growth_percent: 0.0 }`
- `store_visits`: `{ count: 0, growth_percent: 0.0 }`
- `offer_distribution`: `[]` (empty array)
- `peak_redemption_times`: 28 buckets all with `count: 0`
- `top_performing_offers`: `[]` (empty array)

**Product analytics zero-data:**
- `header`: all counts are `0`
- `overview`: all counts are `0`, `traffic_sources` is `[]`
- `engagement`: `total_interactions: 0`, `engagement_rate: 0.0`, `trend: []`
- `audience`: `followers_percent: 50.0`, `non_followers_percent: 50.0`, 7 equal age groups, `gender_groups: []`

---

## Traffic Source Tracking

To get real traffic source data in product analytics, the Flutter app must send a `source` query parameter when viewing a product:

```
GET /api/v1/products/{productId}?source={source}
```

**Valid source values:**

| Value | When to send |
|-------|-------------|
| `search` | User found the product via search results |
| `explore` | User found it on the explore/discover feed |
| `profile` | User navigated from the store profile page |
| `direct` | User opened a deep link, notification, or shared link |
| `recommendation` | User clicked a recommendation card |

**Example:**
```dart
// When user taps a product from search results:
await dio.get('/api/v1/products/$productId?source=search');

// When user taps a product from explore feed:
await dio.get('/api/v1/products/$productId?source=explore');

// When user taps a product from store profile:
await dio.get('/api/v1/products/$productId?source=profile');
```

The `source` parameter is optional. Views without a source are still counted in total views/impressions but won't appear in the traffic sources breakdown.

---

## Store Profile Show

```
GET /api/v1/public-stores/{storeId}
```

A public endpoint (no authentication required) that returns the store's profile and automatically records a profile view for analytics. If the user is authenticated, their user ID is captured.

**Response (200):**
```json
{
  "success": true,
  "message": "...",
  "data": {
    "id": "uuid",
    "name": "Store Name",
    "description": "Store description",
    "logo_url": "https://...",
    "banner_url": "https://...",
    "email": "store@example.com",
    "phone": "+201234567890",
    "subscription_tier": "pro",
    "is_verified": true,
    "verified_at": "2025-01-15T12:00:00+00:00",
    "rating_avg": 4.5,
    "rating_count": 120,
    "followers_count": 500,
    "is_following": false,
    "created_at": "2024-06-01T10:00:00+00:00",
    "categories": [...],
    "addresses": [...],
    "socials": [...],
    "hours": [...]
  }
}
```

**Error:** Returns 404 if the store is not active.

**Analytics integration:** Each call automatically records a `store_profile_views` entry. No separate tracking call needed. This data feeds into the `profile_visits` metric in product analytics.

**Flutter usage:**
```dart
// When user navigates to a store profile page:
final response = await dio.get('/api/v1/public-stores/$storeId');
// Profile view is tracked automatically
```

---

## Product Share Tracking

```
POST /api/v1/products/{productId}/shares
```

Records a product share event. Requires authentication.

**Request Body:**
```json
{
  "platform": "whatsapp"
}
```

| Field | Type | Required | Values |
|-------|------|----------|--------|
| `platform` | string | No | `whatsapp`, `facebook`, `twitter`, `instagram`, `copy_link`, `other` |

**Response (201):**
```json
{
  "message": "Share recorded."
}
```

**Errors:**
- 401 if not authenticated
- 422 if `platform` value is invalid

**Flutter usage:**
```dart
// When user taps the share button and selects a platform:
await dio.post(
  '/api/v1/products/$productId/shares',
  data: {'platform': 'whatsapp'},
);

// If you don't know the platform (e.g. native share sheet):
await dio.post('/api/v1/products/$productId/shares');
```

This data feeds into the `shares` count in the product analytics `action_breakdown`.

---

## Caching Behavior

| Endpoint | Cache Duration | Invalidation |
|----------|---------------|--------------|
| Seller Dashboard | 15 minutes | Invalidated when monthly goal is updated |
| Product Analytics | 1 hour | Not manually invalidated |

Data may be up to 15 minutes stale for the dashboard and up to 1 hour for product analytics. Consider showing a "last updated" indicator in the UI.

---

## Error Handling

| HTTP Code | Scenario | Response |
|-----------|----------|----------|
| 401 | Missing or invalid Bearer token | `{"message": "Unauthenticated."}` |
| 403 | User doesn't own/manage a store | `{"message": "Forbidden."}` |
| 403 | Product doesn't belong to user's store | `{"message": "Forbidden."}` |
| 404 | Product ID not found | `{"message": "Not Found."}` |
| 422 | Invalid `period` parameter | `{"message": "...", "errors": {"period": [...]}}` |
| 422 | Invalid `goal` value | `{"message": "...", "errors": {"goal": [...]}}` |

### Handling in Flutter

```dart
if (response.statusCode == 401) {
  // Token expired — redirect to login
} else if (response.statusCode == 403) {
  // User doesn't have analytics access
  // Show "You don't have permission to view analytics"
} else if (response.statusCode == 404) {
  // Product not found — navigate back
} else if (response.statusCode == 422) {
  // Validation error — show field-level errors
  final errors = responseBody['errors'];
}
```

---

## Flutter Code Examples

### Analytics Service

```dart
import 'package:dio/dio.dart';

class SellerAnalyticsService {
  final Dio _dio;
  final String _baseUrl;

  SellerAnalyticsService(this._dio, this._baseUrl);

  String _storeAnalyticsUrl(String storeId) =>
      '$_baseUrl/api/v1/stores/$storeId/analytics';

  /// Get seller dashboard analytics
  Future<SellerDashboard> getDashboard({
    required String storeId,
    String period = 'all',
  }) async {
    final response = await _dio.get(
      _storeAnalyticsUrl(storeId),
      queryParameters: {'period': period},
    );
    return SellerDashboard.fromJson(response.data);
  }

  /// Update monthly goal
  Future<int> updateMonthlyGoal({
    required String storeId,
    required int goal,
  }) async {
    final response = await _dio.patch(
      '${_storeAnalyticsUrl(storeId)}/monthly-goal',
      data: {'goal': goal},
    );
    return response.data['goal'];
  }

  /// Get product analytics
  Future<ProductAnalytics> getProductAnalytics({
    required String storeId,
    required String productId,
    String period = 'all',
  }) async {
    final response = await _dio.get(
      '${_storeAnalyticsUrl(storeId)}/products/$productId',
      queryParameters: {'period': period},
    );
    return ProductAnalytics.fromJson(response.data);
  }
}
```

### Models

```dart
// ─── Seller Dashboard ────────────────────────────────────────────────────────

class SellerDashboard {
  final MonthlyGoal monthlyGoal;
  final GrowthMetric newFollowers;
  final GrowthMetric storeVisits;
  final List<OfferDistributionItem> offerDistribution;
  final List<HeatmapBucket> peakRedemptionTimes;
  final List<TopOffer> topPerformingOffers;

  SellerDashboard.fromJson(Map<String, dynamic> json)
      : monthlyGoal = MonthlyGoal.fromJson(json['monthly_goal']),
        newFollowers = GrowthMetric.fromJson(json['new_followers']),
        storeVisits = GrowthMetric.fromJson(json['store_visits']),
        offerDistribution = (json['offer_distribution'] as List)
            .map((e) => OfferDistributionItem.fromJson(e))
            .toList(),
        peakRedemptionTimes = (json['peak_redemption_times'] as List)
            .map((e) => HeatmapBucket.fromJson(e))
            .toList(),
        topPerformingOffers = (json['top_performing_offers'] as List)
            .map((e) => TopOffer.fromJson(e))
            .toList();
}

class MonthlyGoal {
  final int? goal;
  final int current;
  final double achievementPercent;

  MonthlyGoal.fromJson(Map<String, dynamic> json)
      : goal = json['goal'],
        current = json['current'],
        achievementPercent = (json['achievement_percent'] as num).toDouble();

  bool get hasGoal => goal != null;
  double get progress => hasGoal ? (current / goal!).clamp(0.0, 1.0) : 0.0;
}

class GrowthMetric {
  final int count;
  final double growthPercent;

  GrowthMetric.fromJson(Map<String, dynamic> json)
      : count = json['count'],
        growthPercent = (json['growth_percent'] as num).toDouble();

  bool get isPositiveGrowth => growthPercent > 0;
  bool get isNegativeGrowth => growthPercent < 0;
}

class OfferDistributionItem {
  final String type;
  final double percentage;

  OfferDistributionItem.fromJson(Map<String, dynamic> json)
      : type = json['type'],
        percentage = (json['percentage'] as num).toDouble();

  /// Human-readable label for the offer type
  String get label {
    switch (type) {
      case 'fixed':
        return 'Fixed Discount';
      case 'percentage':
        return 'Percentage Off';
      case 'buy_x_get_y':
        return 'Buy X Get Y';
      default:
        return type;
    }
  }
}

class HeatmapBucket {
  final String day;
  final String timeWindow;
  final int count;

  HeatmapBucket.fromJson(Map<String, dynamic> json)
      : day = json['day'],
        timeWindow = json['time_window'],
        count = json['count'];

  /// Day index (0 = Monday, 6 = Sunday) for grid positioning
  int get dayIndex {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    return days.indexOf(day);
  }

  /// Time window index (0 = morning, 3 = night) for grid positioning
  int get timeWindowIndex {
    const windows = ['morning', 'afternoon', 'evening', 'night'];
    return windows.indexOf(timeWindow);
  }
}

class TopOffer {
  final String productTitle;
  final String offerType;
  final String offerLabel;
  final int usageCount;

  TopOffer.fromJson(Map<String, dynamic> json)
      : productTitle = json['product_title'],
        offerType = json['offer_type'],
        offerLabel = json['offer_label'],
        usageCount = json['usage_count'];
}

// ─── Product Analytics ───────────────────────────────────────────────────────

class ProductAnalytics {
  final ProductHeader header;
  final ProductOverview overview;
  final ProductEngagement engagement;
  final ProductAudience audience;

  ProductAnalytics.fromJson(Map<String, dynamic> json)
      : header = ProductHeader.fromJson(json['header']),
        overview = ProductOverview.fromJson(json['overview']),
        engagement = ProductEngagement.fromJson(json['engagement']),
        audience = ProductAudience.fromJson(json['audience']);
}

class ProductHeader {
  final int views;
  final int likes;
  final int comments;
  final int saves;

  ProductHeader.fromJson(Map<String, dynamic> json)
      : views = json['views'],
        likes = json['likes'],
        comments = json['comments'],
        saves = json['saves'];

  int get totalInteractions => likes + comments + saves;
}

class ProductOverview {
  final int impressions;
  final int reachedAccounts;
  final int profileVisits;
  final int newFollowers;
  final List<TrafficSource> trafficSources;

  ProductOverview.fromJson(Map<String, dynamic> json)
      : impressions = json['impressions'],
        reachedAccounts = json['reached_accounts'],
        profileVisits = json['profile_visits'],
        newFollowers = json['new_followers'],
        trafficSources = (json['traffic_sources'] as List)
            .map((e) => TrafficSource.fromJson(e))
            .toList();
}

class TrafficSource {
  final String source;
  final double percentage;

  TrafficSource.fromJson(Map<String, dynamic> json)
      : source = json['source'],
        percentage = (json['percentage'] as num).toDouble();
}

class ProductEngagement {
  final int totalInteractions;
  final double engagementRate;
  final List<TrendPoint> trend;
  final ActionBreakdown actionBreakdown;

  ProductEngagement.fromJson(Map<String, dynamic> json)
      : totalInteractions = json['total_interactions'],
        engagementRate = (json['engagement_rate'] as num).toDouble(),
        trend = (json['trend'] as List)
            .map((e) => TrendPoint.fromJson(e))
            .toList(),
        actionBreakdown = ActionBreakdown.fromJson(json['action_breakdown']);
}

class TrendPoint {
  final String date;
  final int count;

  TrendPoint.fromJson(Map<String, dynamic> json)
      : date = json['date'],
        count = json['count'];
}

class ActionBreakdown {
  final int likes;
  final int comments;
  final int saves;
  final int shares;

  ActionBreakdown.fromJson(Map<String, dynamic> json)
      : likes = json['likes'],
        comments = json['comments'],
        saves = json['saves'],
        shares = json['shares'];
}

class ProductAudience {
  final double followersPercent;
  final double nonFollowersPercent;
  final List<AgeGroup> ageGroups;
  final List<GenderGroup> genderGroups;

  ProductAudience.fromJson(Map<String, dynamic> json)
      : followersPercent = (json['followers_percent'] as num).toDouble(),
        nonFollowersPercent = (json['non_followers_percent'] as num).toDouble(),
        ageGroups = (json['age_groups'] as List)
            .map((e) => AgeGroup.fromJson(e))
            .toList(),
        genderGroups = (json['gender_groups'] as List)
            .map((e) => GenderGroup.fromJson(e))
            .toList();
}

class AgeGroup {
  final String range;
  final double percentage;

  AgeGroup.fromJson(Map<String, dynamic> json)
      : range = json['range'],
        percentage = (json['percentage'] as num).toDouble();
}

class GenderGroup {
  final String gender;
  final double percentage;

  GenderGroup.fromJson(Map<String, dynamic> json)
      : gender = json['gender'],
        percentage = (json['percentage'] as num).toDouble();
}
```

### Period Selector Widget

```dart
enum AnalyticsPeriod {
  all('all', 'All Time'),
  today('today', 'Today'),
  last7Days('last_7_days', 'Last 7 Days'),
  thisMonth('this_month', 'This Month'),
  thisYear('this_year', 'This Year');

  final String value;
  final String label;
  const AnalyticsPeriod(this.value, this.label);
}

class PeriodSelector extends StatelessWidget {
  final AnalyticsPeriod selected;
  final ValueChanged<AnalyticsPeriod> onChanged;

  const PeriodSelector({
    required this.selected,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return SegmentedButton<AnalyticsPeriod>(
      segments: AnalyticsPeriod.values
          .map((p) => ButtonSegment(value: p, label: Text(p.label)))
          .toList(),
      selected: {selected},
      onSelectionChanged: (set) => onChanged(set.first),
    );
  }
}
```

### Dashboard Screen Example

```dart
class SellerDashboardScreen extends StatefulWidget {
  @override
  State<SellerDashboardScreen> createState() => _SellerDashboardScreenState();
}

class _SellerDashboardScreenState extends State<SellerDashboardScreen> {
  final _analyticsService = getIt<SellerAnalyticsService>();
  AnalyticsPeriod _period = AnalyticsPeriod.all;
  SellerDashboard? _dashboard;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() => _loading = true);
    try {
      final data = await _analyticsService.getDashboard(period: _period.value);
      setState(() {
        _dashboard = data;
        _loading = false;
      });
    } catch (e) {
      setState(() => _loading = false);
      // Handle error
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Analytics')),
      body: Column(
        children: [
          PeriodSelector(
            selected: _period,
            onChanged: (p) {
              _period = p;
              _loadDashboard();
            },
          ),
          if (_loading)
            const CircularProgressIndicator()
          else if (_dashboard != null)
            Expanded(child: _buildDashboard(_dashboard!)),
        ],
      ),
    );
  }

  Widget _buildDashboard(SellerDashboard dashboard) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Monthly Goal Progress
        if (dashboard.monthlyGoal.hasGoal)
          _GoalCard(goal: dashboard.monthlyGoal),

        // Growth Metrics Row
        Row(
          children: [
            Expanded(child: _MetricCard(
              title: 'New Followers',
              metric: dashboard.newFollowers,
            )),
            const SizedBox(width: 12),
            Expanded(child: _MetricCard(
              title: 'Store Visits',
              metric: dashboard.storeVisits,
            )),
          ],
        ),

        // Offer Distribution Pie Chart
        if (dashboard.offerDistribution.isNotEmpty)
          _OfferDistributionChart(items: dashboard.offerDistribution),

        // Heatmap
        _RedemptionHeatmap(buckets: dashboard.peakRedemptionTimes),

        // Top Offers List
        if (dashboard.topPerformingOffers.isNotEmpty)
          _TopOffersSection(offers: dashboard.topPerformingOffers),
      ],
    );
  }
}
```

---

## UI Recommendations

### Heatmap Visualization

The `peak_redemption_times` array always contains exactly 28 items. Render as a 7×4 grid:

| | Morning (06-12) | Afternoon (12-18) | Evening (18-24) | Night (00-06) |
|---|---|---|---|---|
| Mon | count | count | count | count |
| Tue | count | count | count | count |
| ... | ... | ... | ... | ... |
| Sun | count | count | count | count |

Use color intensity based on count relative to the maximum count in the dataset.

### Growth Indicators

- Positive growth (`growth_percent > 0`): Green arrow up, e.g. "↑ 15.2%"
- Negative growth (`growth_percent < 0`): Red arrow down, e.g. "↓ 3.4%"
- Zero growth (`growth_percent == 0`): Gray dash, e.g. "— 0.0%"

### Percentage Arrays

All percentage arrays (`offer_distribution`, `traffic_sources`, `age_groups`, `gender_groups`) are guaranteed to sum to exactly 100.0. You can safely use them directly for pie charts and bar charts without additional normalization.

### Trend Chart

The `trend` array in product engagement uses:
- **Daily grouping** for periods ≤ 30 days (`today`, `last_7_days`, `this_month`)
- **Monthly grouping** for periods > 30 days (`this_year`, `all`)

The `date` field format is `YYYY-MM-DD` for daily and `YYYY-MM` for monthly.

---

## Localization

All endpoints support localized responses. Send the `Accept-Language` header:

- `en` — English (default)
- `ar` — Arabic

Error messages and validation errors will be returned in the requested language.
