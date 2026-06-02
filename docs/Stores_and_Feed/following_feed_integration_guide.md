# Flutter Integration Guide: Following Feed API

This document details how to integrate the new **Following Feed** endpoint (`GET /api/v1/customer/home/following-feed`) into the Flutter mobile application.

---

## 1. Endpoint Overview

*   **URL**: `/api/v1/customer/home/following-feed`
*   **Method**: `GET`
*   **Headers**:
    *   `Authorization: Bearer <ACCESS_TOKEN>` (Required)
    *   `Accept: application/json`
    *   `Accept-Language: en` (or `ar`)
*   **Response Format**: JSON

---

## 2. Request Parameters

You can pass the following query parameters for pagination and location-based sorting:

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `page` | `int` | No | The page number to fetch (default: `1`). |
| `per_page` | `int` | No | Number of items per page (default: `10`, max: `50`). |
| `latitude` | `double` | No | Current latitude of the device (e.g., `30.0444`). |
| `longitude` | `double` | No | Current longitude of the device (e.g., `31.2357`). |

*Note: If `latitude` and `longitude` are provided, they will be used to serve nearby trending offers if the fallback system is activated.*

---

## 3. Dart Data Models

Use the following models to deserialize the JSON response.

### Feed Item Response Model

```dart
class FollowingFeedResponse {
  final bool success;
  final FeedData data;

  FollowingFeedResponse({required this.success, required this.data});

  factory FollowingFeedResponse.fromJson(Map<String, dynamic> json) {
    return FollowingFeedResponse(
      success: json['success'] ?? false,
      data: FeedData.fromJson(json['data'] ?? {}),
    );
  }
}

class FeedData {
  final List<FeedItem> items;
  final Pagination pagination;

  FeedData({required this.items, required this.pagination});

  factory FeedData.fromJson(Map<String, dynamic> json) {
    var itemsList = json['items'] as List? ?? [];
    return FeedData(
      items: itemsList.map((item) => FeedItem.fromJson(item)).toList(),
      pagination: Pagination.fromJson(json['pagination'] ?? {}),
    );
  }
}

class Pagination {
  final int currentPage;
  final int perPage;
  final int totalItems;
  final int totalPages;
  final bool hasNextPage;

  Pagination({
    required this.currentPage,
    required this.perPage,
    required this.totalItems,
    required this.totalPages,
    required this.hasNextPage,
  });

  factory Pagination.fromJson(Map<String, dynamic> json) {
    return Pagination(
      currentPage: json['current_page'] ?? 1,
      perPage: json['per_page'] ?? 10,
      totalItems: json['total_items'] ?? 0,
      totalPages: json['total_pages'] ?? 1,
      hasNextPage: json['has_next_page'] ?? false,
    );
  }
}
```

### Feed Item Model (`FeedItem`)

Matches the source type, recommendation tags, store information, and offer content:

```dart
class FeedItem {
  final String sourceType; // 'followed', 'recommended', or 'trending'
  final String? recommendationReason; // 'based_on_interests', 'similar_to_followed', 'popular_nearby', or null
  final FeedStore store;
  final FeedOffer offer;

  FeedItem({
    required this.sourceType,
    this.recommendationReason,
    required this.store,
    required this.offer,
  });

  factory FeedItem.fromJson(Map<String, dynamic> json) {
    return FeedItem(
      sourceType: json['source_type'] ?? 'trending',
      recommendationReason: json['recommendation_reason'],
      store: FeedStore.fromJson(json['store'] ?? {}),
      offer: FeedOffer.fromJson(json['offer'] ?? {}),
    );
  }
}

class FeedStore {
  final String id;
  final String name;
  final String? imageUrl;
  final bool isFollowed;

  FeedStore({
    required this.id,
    required this.name,
    this.imageUrl,
    required this.isFollowed,
  });

  factory FeedStore.fromJson(Map<String, dynamic> json) {
    return FeedStore(
      id: json['id'] ?? '',
      name: json['name'] ?? '',
      imageUrl: json['image_url'],
      isFollowed: json['is_followed'] ?? false,
    );
  }
}

class FeedOffer {
  final String id;
  final String? imageUrl;
  final String title;
  final double originalPrice;
  final double discountedPrice;
  final double savePercent;
  final String? category;
  final String? categoryAr; // Follows the HTTP Accept-Language locale header automatically
  final String storeName;
  final bool isLiked;
  final int likesCount;
  final int commentsCount;
  final bool isSaved;
  final DateTime? createdAt;

  FeedOffer({
    required this.id,
    this.imageUrl,
    required this.title,
    required this.originalPrice,
    required this.discountedPrice,
    required this.savePercent,
    this.category,
    this.categoryAr,
    required this.storeName,
    required this.isLiked,
    required this.likesCount,
    required this.commentsCount,
    required this.isSaved,
    this.createdAt,
  });

  factory FeedOffer.fromJson(Map<String, dynamic> json) {
    return FeedOffer(
      id: json['id'] ?? '',
      imageUrl: json['image_url'],
      title: json['title'] ?? '',
      originalPrice: (json['original_price'] as num?)?.toDouble() ?? 0.0,
      discountedPrice: (json['discounted_price'] as num?)?.toDouble() ?? 0.0,
      savePercent: (json['save_percent'] as num?)?.toDouble() ?? 0.0,
      category: json['category'],
      categoryAr: json['category_ar'],
      storeName: json['store_name'] ?? '',
      isLiked: json['is_liked'] ?? false,
      likesCount: json['likes_count'] ?? 0,
      commentsCount: json['comments_count'] ?? 0,
      isSaved: json['is_saved'] ?? false,
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at']) : null,
    );
  }
}
```

---

## 4. UI Customization Suggestions

Depending on the `source_type` and `recommendation_reason` attributes, you can dynamically display helpful badges or UI tags:

1.  **Followed Store Offer**:
    *   `source_type == 'followed'`
    *   *UI Recommendation*: Display normally in the feed as typical timeline content.
2.  **Based on Interests Recommendation**:
    *   `source_type == 'recommended' && recommendation_reason == 'based_on_interests'`
    *   *UI Recommendation*: Display a small badge saying `"Based on your interests"` or `"Recommended for you"`.
3.  **Similar to Followed Recommendation**:
    *   `source_type == 'recommended' && recommendation_reason == 'similar_to_followed'`
    *   *UI Recommendation*: Display a badge saying `"Similar to stores you follow"`.
4.  **Popular Nearby Fallback**:
    *   `source_type == 'trending' && recommendation_reason == 'popular_nearby'`
    *   *UI Recommendation*: Display a badge saying `"Popular near you"`.
5.  **General Trending Fallback**:
    *   `source_type == 'trending' && recommendation_reason == null`
    *   *UI Recommendation*: Display a badge saying `"Trending overall"`.
