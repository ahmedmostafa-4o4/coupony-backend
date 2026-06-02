# Travel Banners: Flutter Implementation Guide

This document outlines the implementation details for the Flutter team to integrate the "Travel Banners" feature into the customer mobile app.

## Endpoint Details

### 1. Fetch Travel Banners

- **URL:** `/api/v1/customer/travel-banners`
- **Method:** `GET`
- **Headers:** 
  - `Accept: application/json`
  - `Accept-Language: ar` (or `en` based on locale)
- **Auth:** Publicly accessible (no token required).

### Response Schema

The response returns an array of active travel banners. Each banner includes an embedded `product` object.

```json
{
    "success": true,
    "message": "Travel banners retrieved successfully.",
    "data": [
        {
            "id": "e83f2a89-11c2-4a0f-b6e9-eab2a3c748da",
            "image_url": "https://yourdomain.com/storage/travel_banners/banner_image.jpg",
            "cta_text": "Book Now",
            "save_percent": "Up to 50%",
            "priority": 1,
            "start_date": "2026-06-01T00:00:00.000000Z",
            "end_date": "2026-07-01T00:00:00.000000Z",
            "product": {
                "id": "c1f7b822-2a54-4f81-8b07-fb252684b3f8",
                "title": "Maldives Resort 5 Days",
                "base_price": "2000.00",
                "compare_at_price": "4000.00",
                "rating_avg": "4.80",
                "image": "products/images/main.jpg",
                "has_offer": true
            }
        }
    ]
}
```

## Flutter Implementation Workflow

### 1. Models & Parsing
Create a `TravelBanner` model and an inner `TravelBannerProduct` model to parse the `data` array correctly.

### 2. UI Presentation
- **Banner Layout**: Use a PageView or a horizontal scrolling ListView to display these banners (since there can be more than one).
- **Banner Image**: Load the `image_url` using a network image library (e.g., `cached_network_image`). 
- **CTA & Save Percent**: Overlay the `save_percent` tag (e.g., as a badge at the top corner) and the `cta_text` (as a prominent button or text overlay) on the banner.
- **Product Details**: Optionally show the `title`, `base_price`, and `rating_avg` on the banner overlay, or reserve these details for when the user clicks.

### 3. Navigation
When a user taps the banner, extract the `product.id` and navigate them directly to the Product Details Screen for that specific offer.

```dart
// Example routing
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => ProductDetailsScreen(productId: banner.product.id),
  ),
);
```
