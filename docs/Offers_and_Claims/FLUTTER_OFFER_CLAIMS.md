# Flutter Offer Claims API Documentation

This document outlines the endpoints and data models for integrating the Offer Claims functionality in the Flutter application.

## Endpoints

### 1. Create Offer Claim (Customer)
Creates a new offer claim for a specific product.
- **Endpoint:** `POST /api/v1/products/{product}/claims`
- **Auth:** Required (Sanctum)
- **Body:**
  - For standard offers (fixed/percentage):
    ```json
    {
      "variant_ids": ["uuid-of-variant-1"] 
    }
    ```
    *(Optional, required only if product has variants)*
  - For Buy X Get Y offers:
    ```json
    {
      "buy_variant_ids": ["uuid-of-buy-variant-1", "uuid-of-buy-variant-2"],
      "reward_variant_ids": ["uuid-of-reward-variant"]
    }
    ```
- **Response:**
  ```json
  {
    "success": true,
    "message": "Offer claim created successfully.",
    "data": { ...OfferClaimModel }
  }
  ```

### 2. List My Claims (Customer)
Retrieves a paginated list of claims belonging to the authenticated customer.
- **Endpoint:** `GET /api/v1/me/offer-claims`
- **Auth:** Required (Sanctum)
- **Query Parameters:**
  - `status`: (Optional) Filter by status (e.g., `active`, `redeemed`, `expired`)
  - `search`: (Optional) Search by product title, store name, or claim token
  - `category`: (Optional) Filter by product category ID
  - `per_page`: (Optional) Number of items per page (default: 15)
- **Response:**
  ```json
  {
    "success": true,
    "data": [ { ...OfferClaimModel }, ... ],
    "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 75 }
  }
  ```

### 3. View Claim Details (Customer)
Retrieves details of a specific claim.
- **Endpoint:** `GET /api/v1/me/offer-claims/{claim}`
- **Auth:** Required (Sanctum)
- **Response:**
  ```json
  {
    "success": true,
    "data": { ...OfferClaimModel }
  }
  ```

### 4. List Store Claims (Store Employee/Owner)
Retrieves a paginated list of claims for a specific store.
- **Endpoint:** `GET /api/v1/stores/{store}/offer-claims`
- **Auth:** Required (Sanctum), Requires Store Employee Permission `accessClaims`
- **Query Parameters:**
  - `status`: (Optional) Filter by status (`active`, `redeemed`, `expired`)
  - `per_page`: (Optional) Number of items per page (default: 15)
- **Response:**
  ```json
  {
    "success": true,
    "data": [ { ...OfferClaimModel }, ... ],
    "meta": { ...PaginationMeta }
  }
  ```

### 5. View Store Claim Details (Store Employee/Owner)
Retrieves details of a specific store claim.
- **Endpoint:** `GET /api/v1/stores/{store}/offer-claims/{claim}`
- **Auth:** Required (Sanctum), Requires Store Employee Permission `accessClaims`
- **Response:**
  ```json
  {
    "success": true,
    "data": { ...OfferClaimModel }
  }
  ```

### 6. Redeem Claim (Store Employee/Owner)
Redeems an offer claim using a QR code token.
- **Endpoint:** `POST /api/v1/stores/{store}/offer-claims/redeem`
- **Auth:** Required (Sanctum), Requires Store Employee Permission `redeemClaims`
- **Body:**
  ```json
  {
    "qr_code_token": "string"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "message": "Offer claim redeemed successfully.",
    "data": { ...OfferClaimModel }
  }
  ```

---

## Data Models (Dart)

### OfferClaimModel
```dart
class OfferClaimModel {
  final String id;
  final String userId;
  final String storeId;
  final String productId;
  final String offerId;
  final String claimToken;
  final String qrCodeToken;
  final String status; // active, redeemed, expired
  final DateTime? expiresAt;
  final DateTime? redeemedAt;
  final DateTime createdAt;
  final DateTime updatedAt;
  final ClaimCustomerModel? customer;
  final ClaimProductModel? product;
  final StoreModel? store;
  final int usageCount;
  
  // Example factory parsing
  factory OfferClaimModel.fromJson(Map<String, dynamic> json) {
    return OfferClaimModel(
      id: json['id'],
      status: json['status'],
      claimToken: json['claim_token'],
      qrCodeToken: json['qr_code_token'],
      customer: json['customer'] == null
          ? null
          : ClaimCustomerModel.fromJson(json['customer']),
      product: json['product'] == null
          ? null
          : ClaimProductModel.fromJson(json['product']),
      usageCount: json['usage_count'],
    );
  }
}

class ClaimCustomerModel {
  final String id;
  final String? name;

  ClaimCustomerModel.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        name = json['name'];
}

class ClaimProductModel {
  final String id;
  final String title;
  final String? imageUrl;

  ClaimProductModel.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        title = json['title'],
        imageUrl = json['image_url'];
}
```

`customer` and `product` may be `null` only for older claims whose related records were deleted. `usage_count` is the current total of redeemed claims for the offer, not a claim-time snapshot.
