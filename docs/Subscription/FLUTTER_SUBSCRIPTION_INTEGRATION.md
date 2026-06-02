# Coupony Subscription System — Flutter Integration Guide

## Overview

The subscription system manages store plans, payment processing (via Paymob), entitlements, and lifecycle states. All endpoints require authentication via Laravel Sanctum (Bearer token).

**Base URL:** `https://api.coupony.shop/api/v1`

---

## Table of Contents

1. [Authentication](#authentication)
2. [Subscription Lifecycle](#subscription-lifecycle)
3. [API Endpoints](#api-endpoints)
4. [Payment Flow](#payment-flow)
5. [Handling Subscription States in UI](#handling-subscription-states-in-ui)
6. [Middleware & Error Codes](#middleware--error-codes)
7. [Flutter Code Examples](#flutter-code-examples)

---

## Authentication

All subscription endpoints require a valid Bearer token from Sanctum authentication.

```
Authorization: Bearer {token}
Accept: application/json
Accept-Language: en  (or "ar" for Arabic)
```

---

## Subscription Lifecycle

The subscription follows a state machine with these statuses:

```
none → trial / active
trial → active / none
active → grace
grace → active / degraded
degraded → active / suspended
suspended → active / archived
archived → (terminal)
```

| Status | Description |
|--------|-------------|
| `none` | No subscription. Store cannot access gated features. |
| `trial` | Free trial period. Full access until trial ends. |
| `active` | Paid and active. Full access to plan features. |
| `grace` | Payment expired. 7-day window to renew before degradation. |
| `degraded` | Grace period ended. Read-only access, writes blocked if over limits. 14-day window. |
| `suspended` | Degraded period ended. Store fully blocked. |
| `archived` | Terminal state. Store permanently deactivated. |

---

## API Endpoints

All endpoints are prefixed with: `/api/v1/stores/{store_id}/subscription`

### 1. Get Available Plans

```
GET /plans
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-plan-id",
      "name": "Pro Plan",
      "slug": "pro",
      "description": "Best for growing businesses",
      "prices": {
        "monthly": "99.00",
        "yearly": "999.00",
        "currency": "EGP"
      },
      "entitlements": {
        "max_products": 100,
        "max_employees": 10,
        "max_branches": 5
      },
      "features": {
        "ai_assistant": true,
        "analytics": true,
        "priority_support": false
      },
      "payment_config": {
        "is_review_mode": false,
        "supported_payment_methods": ["card", "wallet"]
      }
    }
  ]
}
```

---

### 2. Initiate Payment

```
POST /initiate-payment
```

**Request Body:**
```json
{
  "plan_id": "uuid-plan-id",
  "billing_cycle": "monthly"
}
```

| Field | Type | Required | Values |
|-------|------|----------|--------|
| `plan_id` | UUID string | Yes | Must be an active plan ID |
| `billing_cycle` | string | Yes | `monthly` or `yearly` |

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "session_id": "uuid-session-id",
    "client_secret": "pk_xxxxxxx_client_secret_xxxxxxx",
    "public_key": "egy_pk_live_xxxxxxxxxxxxxxx",
    "expires_at": "2025-01-15T14:30:00+00:00"
  }
}
```

The `client_secret` and `public_key` are used with the Paymob native SDK (`paymob_sdk` Flutter package) to present the payment sheet without needing a WebView or iframe.

**Error Responses:**

| Code | Error Code | Description |
|------|-----------|-------------|
| 409 | `PAYMENT_SESSION_ALREADY_USED` | A pending payment session already exists |
| 502 | — | Payment gateway error (Paymob unavailable) |

---

### 3. Confirm Payment

```
POST /confirm-payment
```

**Request Body:**
```json
{
  "session_id": "uuid-session-id"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": "uuid-subscription-id",
    "status": "active",
    "billing_cycle": "monthly",
    "current_period_start": "2025-01-15T12:00:00+00:00",
    "current_period_end": "2025-02-15T12:00:00+00:00",
    "grace_period_end": null,
    "degraded_period_end": null,
    "trial_ends_at": null,
    "cancelled_at": null,
    "plan": {
      "id": "uuid-plan-id",
      "name": "Pro Plan",
      "slug": "pro",
      "description": "Best for growing businesses",
      "price_monthly": "99.00",
      "price_yearly": "999.00",
      "currency": "EGP",
      "max_products": 100,
      "max_employees": 10,
      "max_branches": 5,
      "features": {
        "ai_assistant": true,
        "analytics": true
      }
    },
    "created_at": "2025-01-15T12:00:00+00:00",
    "updated_at": "2025-01-15T12:00:00+00:00"
  }
}
```

**Error Responses:**

| Code | Error Code | Description |
|------|-----------|-------------|
| 404 | `PAYMENT_SESSION_NOT_FOUND` | Session ID invalid or doesn't belong to store |
| 409 | `PAYMENT_SESSION_ALREADY_USED` | Session already consumed (failed) |
| 410 | `PAYMENT_SESSION_EXPIRED` | Session TTL exceeded (30 minutes) |

**Important:** If the webhook hasn't confirmed payment yet (session still `pending`), the response returns the subscription with status `none`. The client should poll or retry after a short delay.

---

### 4. Get Subscription Overview

```
GET /overview
```

**Response (with active subscription):**
```json
{
  "success": true,
  "data": {
    "id": "uuid-subscription-id",
    "status": "active",
    "billing_cycle": "monthly",
    "current_period_start": "2025-01-15T12:00:00+00:00",
    "current_period_end": "2025-02-15T12:00:00+00:00",
    "grace_period_end": null,
    "degraded_period_end": null,
    "trial_ends_at": null,
    "cancelled_at": null,
    "plan": {
      "id": "uuid-plan-id",
      "name": "Pro Plan",
      "slug": "pro",
      "description": "Best for growing businesses",
      "price_monthly": "99.00",
      "price_yearly": "999.00",
      "currency": "EGP",
      "max_products": 100,
      "max_employees": 10,
      "max_branches": 5,
      "features": { "ai_assistant": true }
    },
    "usage": {
      "products": 45,
      "employees": 3,
      "branches": 2
    },
    "created_at": "2025-01-15T12:00:00+00:00",
    "updated_at": "2025-01-15T12:00:00+00:00"
  }
}
```

**Response (no subscription):**
```json
{
  "success": true,
  "data": {
    "status": "none",
    "plan": null,
    "usage": null,
    "available_plans": [ /* array of plan objects */ ]
  }
}
```

---

### 5. Get Subscription Status (Lightweight)

```
GET /status
```

Use this for banners and quick status checks. Lighter than `/overview`.

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "grace",
    "days_remaining": 5,
    "message": "Your subscription has expired. Renew before 2025-01-22 to avoid losing access."
  }
}
```

| Status | Message |
|--------|---------|
| `none` | No active subscription. Subscribe to a plan to unlock features. |
| `trial` | You are on a trial period. Subscribe to a plan before your trial ends. |
| `grace` | Your subscription has expired. Renew before {date} to avoid losing access. |
| `degraded` | Your account is in degraded mode. Some features are restricted. Please renew your subscription to restore full access. |
| `suspended` | Your store is suspended. Payment is required to reactivate your subscription. |
| `active` | `null` (no message needed) |

---

### 6. Get Payment History

```
GET /history?status=active&per_page=15
```

**Query Parameters:**

| Param | Type | Required | Values |
|-------|------|----------|--------|
| `status` | string | No | `active`, `expired`, `refunded`, `failed`, `cancelled` |
| `per_page` | integer | No | 1–100 (default: 15) |

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid-history-id",
      "plan_name": "Pro Plan",
      "billing_cycle": "monthly",
      "amount": "99.00",
      "payment_method": "card",
      "status": "active",
      "period_start": "2025-01-15T12:00:00+00:00",
      "period_end": "2025-02-15T12:00:00+00:00",
      "created_at": "2025-01-15T12:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 15,
    "total": 42
  }
}
```

---

### 7. Get Entitlements

```
GET /entitlements
```

**Response:**
```json
{
  "success": true,
  "data": {
    "limits": {
      "products": {
        "limit": 100,
        "usage": 45,
        "remaining": 55
      },
      "employees": {
        "limit": 10,
        "usage": 3,
        "remaining": 7
      },
      "branches": {
        "limit": 5,
        "usage": 2,
        "remaining": 3
      }
    },
    "features": {
      "ai_assistant": true,
      "analytics": true,
      "priority_support": false
    }
  }
}
```

---

## Payment Flow

### Sequence Diagram

```
Flutter App                    Backend                      Paymob
    |                            |                            |
    |-- GET /plans ------------->|                            |
    |<-- plan list --------------|                            |
    |                            |                            |
    |-- POST /initiate-payment ->|                            |
    |                            |-- POST /v1/intention/ ---->|
    |                            |<-- client_secret ----------|
    |<-- session_id + secret ----|                            |
    |                            |                            |
    |-- PaymobSDK.pay(secret) --|----------------------------->|
    |                            |                            |
    |   [User completes payment via native SDK UI]            |
    |                            |                            |
    |                            |<-- webhook (paid) ---------|
    |                            |   (marks session as paid)  |
    |                            |                            |
    |-- POST /confirm-payment -->|                            |
    |<-- subscription (active) --|                            |
```

### Flutter Implementation Steps

1. **Fetch plans** — Display plan selection UI
2. **Initiate payment** — Get `client_secret`, `public_key`, and `session_id`
3. **Launch Paymob SDK** — Use `paymob_sdk` package with `client_secret` and `public_key`
4. **SDK handles payment** — Native payment UI (no WebView needed)
5. **Confirm payment** — Call `/confirm-payment` with `session_id`
6. **Handle result** — Update local state based on subscription status

### Payment Session TTL

Sessions expire after **30 minutes**. If the user doesn't complete payment within this window, the session becomes invalid and a new one must be initiated.

---

## Handling Subscription States in UI

### Recommended UI Behavior

| Status | UI Behavior |
|--------|-------------|
| `none` | Show paywall / plan selection screen |
| `trial` | Show trial banner with days remaining, CTA to subscribe |
| `active` | Normal app experience, no banners |
| `grace` | Warning banner: "Renew before {date}", allow full access |
| `degraded` | Alert banner, block write operations that exceed limits |
| `suspended` | Full-screen blocker, only allow payment flow |
| `archived` | Show "account deactivated" message, no actions available |

### Polling Strategy for Payment Confirmation

After the Paymob SDK reports success, the webhook may take a few seconds to process. Recommended approach:

```dart
// Poll confirm-payment every 2 seconds, up to 5 attempts
Future<Subscription?> pollPaymentConfirmation(String sessionId) async {
  for (int i = 0; i < 5; i++) {
    final result = await confirmPayment(sessionId);
    if (result.status == 'active') return result;
    await Future.delayed(Duration(seconds: 2));
  }
  return null; // Show "payment processing" message
}
```

---

## Middleware & Error Codes

The backend applies `subscription` middleware to resource-creation routes. Your app may receive these errors on any gated endpoint:

| HTTP Code | Error Code | Meaning |
|-----------|-----------|---------|
| 403 | `SUBSCRIPTION_REQUIRED` | No subscription exists (status = `none`) |
| 403 | `STORE_SUSPENDED` | Store is suspended |
| 403 | `STORE_ARCHIVED` | Store is archived |
| 403 | `SUBSCRIPTION_LIMIT_REACHED` | Resource limit exceeded for current plan |
| 403 | `SUBSCRIPTION_FEATURE_LOCKED` | Feature not available on current plan |

### Handling in Flutter

```dart
if (response.statusCode == 403) {
  final errorCode = responseBody['error_code'];
  switch (errorCode) {
    case 'SUBSCRIPTION_REQUIRED':
      // Navigate to plan selection
      break;
    case 'SUBSCRIPTION_LIMIT_REACHED':
      // Show upgrade prompt
      break;
    case 'SUBSCRIPTION_FEATURE_LOCKED':
      // Show feature locked dialog
      break;
    case 'STORE_SUSPENDED':
      // Show payment required screen
      break;
    case 'STORE_ARCHIVED':
      // Show deactivated message
      break;
  }
}
```

---

## Flutter Code Examples

### Subscription Service

```dart
import 'package:dio/dio.dart';

class SubscriptionService {
  final Dio _dio;
  final String _baseUrl;

  SubscriptionService(this._dio, this._baseUrl);

  String _storeUrl(String storeId) =>
      '$_baseUrl/api/v1/stores/$storeId/subscription';

  /// Fetch available subscription plans
  Future<List<SubscriptionPlan>> getPlans(String storeId) async {
    final response = await _dio.get('${_storeUrl(storeId)}/plans');
    final data = response.data['data'] as List;
    return data.map((json) => SubscriptionPlan.fromJson(json)).toList();
  }

  /// Initiate a payment session
  Future<PaymentSession> initiatePayment({
    required String storeId,
    required String planId,
    required String billingCycle,
  }) async {
    final response = await _dio.post(
      '${_storeUrl(storeId)}/initiate-payment',
      data: {
        'plan_id': planId,
        'billing_cycle': billingCycle,
      },
    );
    return PaymentSession.fromJson(response.data['data']);
  }

  /// Confirm payment and activate subscription
  Future<SubscriptionOverview> confirmPayment({
    required String storeId,
    required String sessionId,
  }) async {
    final response = await _dio.post(
      '${_storeUrl(storeId)}/confirm-payment',
      data: {'session_id': sessionId},
    );
    return SubscriptionOverview.fromJson(response.data['data']);
  }

  /// Get full subscription overview
  Future<SubscriptionOverview?> getOverview(String storeId) async {
    final response = await _dio.get('${_storeUrl(storeId)}/overview');
    return SubscriptionOverview.fromJson(response.data['data']);
  }

  /// Get lightweight status for banners
  Future<SubscriptionStatus> getStatus(String storeId) async {
    final response = await _dio.get('${_storeUrl(storeId)}/status');
    return SubscriptionStatus.fromJson(response.data['data']);
  }

  /// Get current entitlements and usage
  Future<Entitlements> getEntitlements(String storeId) async {
    final response = await _dio.get('${_storeUrl(storeId)}/entitlements');
    return Entitlements.fromJson(response.data['data']);
  }

  /// Get payment history
  Future<PaginatedHistory> getHistory(
    String storeId, {
    String? status,
    int perPage = 15,
  }) async {
    final response = await _dio.get(
      '${_storeUrl(storeId)}/history',
      queryParameters: {
        if (status != null) 'status': status,
        'per_page': perPage,
      },
    );
    return PaginatedHistory.fromJson(response.data);
  }
}
```

### Models

```dart
class SubscriptionPlan {
  final String id;
  final String name;
  final String slug;
  final String? description;
  final PlanPrices prices;
  final PlanEntitlements entitlements;
  final Map<String, bool> features;
  final PaymentConfig paymentConfig;

  SubscriptionPlan.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        name = json['name'],
        slug = json['slug'],
        description = json['description'],
        prices = PlanPrices.fromJson(json['prices']),
        entitlements = PlanEntitlements.fromJson(json['entitlements']),
        features = Map<String, bool>.from(json['features'] ?? {}),
        paymentConfig = PaymentConfig.fromJson(json['payment_config']);
}

class PlanPrices {
  final String monthly;
  final String yearly;
  final String currency;

  PlanPrices.fromJson(Map<String, dynamic> json)
      : monthly = json['monthly'],
        yearly = json['yearly'],
        currency = json['currency'];
}

class PlanEntitlements {
  final int maxProducts;
  final int maxEmployees;
  final int maxBranches;

  PlanEntitlements.fromJson(Map<String, dynamic> json)
      : maxProducts = json['max_products'],
        maxEmployees = json['max_employees'],
        maxBranches = json['max_branches'];
}

class PaymentConfig {
  final bool isReviewMode;
  final List<String> supportedPaymentMethods;

  PaymentConfig.fromJson(Map<String, dynamic> json)
      : isReviewMode = json['is_review_mode'] ?? false,
        supportedPaymentMethods =
            List<String>.from(json['supported_payment_methods'] ?? []);
}

class PaymentSession {
  final String sessionId;
  final String clientSecret;
  final String publicKey;
  final String? expiresAt;

  PaymentSession.fromJson(Map<String, dynamic> json)
      : sessionId = json['session_id'],
        clientSecret = json['client_secret'],
        publicKey = json['public_key'],
        expiresAt = json['expires_at'];
}

class SubscriptionOverview {
  final String? id;
  final String status;
  final String? billingCycle;
  final String? currentPeriodStart;
  final String? currentPeriodEnd;
  final String? gracePeriodEnd;
  final String? degradedPeriodEnd;
  final String? trialEndsAt;
  final Map<String, dynamic>? plan;
  final Map<String, int>? usage;

  SubscriptionOverview.fromJson(Map<String, dynamic> json)
      : id = json['id'],
        status = json['status'],
        billingCycle = json['billing_cycle'],
        currentPeriodStart = json['current_period_start'],
        currentPeriodEnd = json['current_period_end'],
        gracePeriodEnd = json['grace_period_end'],
        degradedPeriodEnd = json['degraded_period_end'],
        trialEndsAt = json['trial_ends_at'],
        plan = json['plan'],
        usage = json['usage'] != null
            ? Map<String, int>.from(json['usage'])
            : null;
}

class SubscriptionStatus {
  final String status;
  final int? daysRemaining;
  final String? message;

  SubscriptionStatus.fromJson(Map<String, dynamic> json)
      : status = json['status'],
        daysRemaining = json['days_remaining'],
        message = json['message'];
}

class Entitlements {
  final Map<String, ResourceLimit> limits;
  final Map<String, bool> features;

  Entitlements.fromJson(Map<String, dynamic> json)
      : limits = (json['limits'] as Map<String, dynamic>).map(
            (key, value) => MapEntry(key, ResourceLimit.fromJson(value)),
          ),
        features = Map<String, bool>.from(json['features'] ?? {});
}

class ResourceLimit {
  final int limit;
  final int usage;
  final int remaining;

  ResourceLimit.fromJson(Map<String, dynamic> json)
      : limit = json['limit'],
        usage = json['usage'],
        remaining = json['remaining'];
}
```

### Native SDK Payment Flow

```dart
// pubspec.yaml
// dependencies:
//   paymob_sdk: ^latest_version

import 'package:paymob_sdk/paymob_sdk.dart';

class PaymentHandler {
  final SubscriptionService _subscriptionService;

  PaymentHandler(this._subscriptionService);

  /// Full payment flow using Paymob native SDK
  Future<SubscriptionOverview?> processPayment({
    required String storeId,
    required String planId,
    required String billingCycle,
  }) async {
    // Step 1: Get client_secret from backend
    final session = await _subscriptionService.initiatePayment(
      storeId: storeId,
      planId: planId,
      billingCycle: billingCycle,
    );

    // Step 2: Initialize Paymob SDK with public key
    final paymob = PaymobSdk(publicKey: session.publicKey);

    // Step 3: Present payment sheet (native UI, no WebView)
    final result = await paymob.presentPaymentSheet(
      clientSecret: session.clientSecret,
    );

    // Step 4: Check SDK result
    if (result == null || !result.success) {
      // User cancelled or payment failed in SDK
      return null;
    }

    // Step 5: Confirm payment with backend (poll if webhook hasn't arrived)
    return await _pollPaymentConfirmation(storeId, session.sessionId);
  }

  /// Poll confirm-payment every 2 seconds, up to 5 attempts
  Future<SubscriptionOverview?> _pollPaymentConfirmation(
    String storeId,
    String sessionId,
  ) async {
    for (int i = 0; i < 5; i++) {
      try {
        final result = await _subscriptionService.confirmPayment(
          storeId: storeId,
          sessionId: sessionId,
        );
        if (result.status == 'active') return result;
      } catch (_) {}
      await Future.delayed(const Duration(seconds: 2));
    }
    return null; // Show "payment processing" message to user
  }
}
```

### Usage in a Widget

```dart
class SubscriptionScreen extends StatelessWidget {
  final String storeId;
  final PaymentHandler paymentHandler;

  Future<void> _subscribe(BuildContext context, SubscriptionPlan plan) async {
    final result = await paymentHandler.processPayment(
      storeId: storeId,
      planId: plan.id,
      billingCycle: 'monthly', // or 'yearly'
    );

    if (result != null && result.status == 'active') {
      // Success — navigate to dashboard or show success
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Subscription activated!')),
      );
    } else {
      // Payment still processing or failed
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Payment is being processed...')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    // Your plan selection UI here
    return Container();
  }
}
```

---

## Review Mode

When `is_review_mode` is `true` in the plan's `payment_config`, the app is in App Store review mode. In this mode, you should:

- Skip actual payment flow
- Auto-activate subscriptions for testing
- This is controlled server-side via `SUBSCRIPTION_REVIEW_MODE` env variable

---

## Localization

All endpoints support localized responses. Send the `Accept-Language` header:

- `en` — English (default)
- `ar` — Arabic

Error messages and validation errors will be returned in the requested language.
