# Flutter Real-Time Notifications Guide (Pusher)

This guide explains how the Flutter app should implement Coupony notifications using:

1. REST APIs for notification history and read/unread state.
2. Pusher Channels for real-time in-app notifications.

The backend broadcasts Laravel private-channel events through Pusher.

---

## 1. Values Needed From Backend

The Flutter developer needs these values:

```txt
API_BASE_URL=https://api.coupony.shop
PUSHER_APP_KEY=b4bbaf7bfefabd7d2e3c
PUSHER_APP_CLUSTER=eu
AUTH_ENDPOINT=https://api.coupony.shop/broadcasting/auth
CHANNEL=private-users.{userId}
EVENT=notification.sent
```

Do **not** put this in Flutter:

```txt
PUSHER_APP_SECRET
```

`PUSHER_APP_SECRET` is backend-only.

---

## 2. Authentication

All REST API requests need:

```http
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
```

The Pusher private channel auth request must also send:

```http
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
```

Auth endpoint:

```http
POST /broadcasting/auth
```

Full URL example:

```txt
https://api.coupony.shop/broadcasting/auth
```

---

## 3. REST Notification APIs

Base prefix:

```txt
/api/v1
```

### List notifications

```http
GET /api/v1/me/notifications?per_page=20
```

Response example:

```json
{
    "data": [
        {
            "id": 123,
            "type": "points_earned",
            "title": "Points earned",
            "message": "You earned points for redeeming an offer.",
            "data": {
                "points": 20,
                "reason": "offer_redeemed",
                "claim_id": "uuid",
                "product_id": "uuid",
                "store_id": "uuid"
            },
            "channel": "in_app",
            "status": "sent",
            "is_read": false,
            "is_sent": true,
            "time_ago": "1 minute ago",
            "reference": {
                "type": "App\\Domain\\Product\\Models\\OfferClaim",
                "id": "uuid"
            },
            "sent_at": "2026-05-18T12:00:00+00:00",
            "read_at": null,
            "created_at": "2026-05-18T12:00:00+00:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 20,
        "total": 1,
        "unread_count": 1
    }
}
```

### List unread notifications

```http
GET /api/v1/me/notifications/unread?per_page=20
```

### Get unread count

```http
GET /api/v1/me/notifications/unread-count
```

Example:

```json
{
    "data": {
        "unread_count": 5
    }
}
```

### Show one notification

```http
GET /api/v1/me/notifications/{notificationId}
```

If the notification belongs to another user, the backend returns `403`.

### Mark as read

```http
PATCH /api/v1/me/notifications/{notificationId}/read
```

### Mark as unread

```http
PATCH /api/v1/me/notifications/{notificationId}/unread
```

### Mark all as read

```http
PATCH /api/v1/me/notifications/read-all
```

Example:

```json
{
    "data": {
        "updated_count": 4,
        "unread_count": 0
    }
}
```

### Delete all read notifications

```http
DELETE /api/v1/me/notifications/read
```

### Delete one notification

```http
DELETE /api/v1/me/notifications/{notificationId}
```

---

## 4. Pusher Channel And Event

Flutter should subscribe to this private channel:

```txt
private-users.{userId}
```

Example:

```txt
private-users.9f8d7c6b-0000-1111-2222-333344445555
```

Event name:

```txt
notification.sent
```

Backend authorization rule:

- the bearer token user ID must match the `{userId}` in `private-users.{userId}`.
- otherwise channel authorization fails.

---

## 5. Flutter Package

Use a Pusher-compatible package, for example:

```yaml
dependencies:
    pusher_channels_flutter: ^latest
```

Use the latest stable version from `pub.dev`.

---

## 6. Flutter Real-Time Service Example

Adjust this code to match the exact package version used in the app.

```dart
import 'dart:convert';

import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

class NotificationRealtimeService {
  final PusherChannelsFlutter _pusher = PusherChannelsFlutter.getInstance();

  Future<void> connect({
    required String userId,
    required String accessToken,
    required String pusherAppKey,
    required String pusherCluster,
    required String authEndpoint,
    required void Function(Map<String, dynamic> notification, int unreadCount) onNotification,
  }) async {
    await _pusher.init(
      apiKey: pusherAppKey,
      cluster: pusherCluster,
      useTLS: true,
      authEndpoint: authEndpoint,
      onAuthorizer: (channelName, socketId, options) async {
        return {
          'headers': {
            'Authorization': 'Bearer $accessToken',
            'Accept': 'application/json',
          },
        };
      },
      onConnectionStateChange: (currentState, previousState) {
        print('Pusher state: $previousState -> $currentState');
      },
      onError: (message, code, exception) {
        print('Pusher error: $message | code: $code | exception: $exception');
      },
      onEvent: (event) {
        if (event.eventName != 'notification.sent') {
          return;
        }

        final decoded = jsonDecode(event.data) as Map<String, dynamic>;
        final notification = decoded['notification'] as Map<String, dynamic>;
        final unreadCount = decoded['unread_count'] as int? ?? 0;

        onNotification(notification, unreadCount);
      },
    );

    await _pusher.subscribe(channelName: 'private-users.$userId');
    await _pusher.connect();
  }

  Future<void> disconnect(String userId) async {
    await _pusher.unsubscribe(channelName: 'private-users.$userId');
    await _pusher.disconnect();
  }
}
```

Important notes:

- Some versions of `pusher_channels_flutter` use different auth APIs.
- If `onAuthorizer` is not available, configure auth headers using the package-supported method.
- The auth request must include `Authorization: Bearer <ACCESS_TOKEN>`.

---

## 7. Real-Time Payload

When `notification.sent` is received:

```json
{
    "notification": {
        "id": 123,
        "type": "points_earned",
        "title": "Points earned",
        "message": "You earned points for redeeming an offer.",
        "data": {
            "points": 20,
            "reason": "offer_redeemed",
            "claim_id": "uuid",
            "product_id": "uuid",
            "store_id": "uuid"
        },
        "channel": "in_app",
        "status": "sent",
        "reference_type": "App\\Domain\\Product\\Models\\OfferClaim",
        "reference_id": "uuid",
        "read_at": null,
        "created_at": "2026-05-18T12:00:00+00:00"
    },
    "unread_count": 3
}
```

Recommended Flutter behavior:

1. Set notification badge count to `unread_count`.
2. Add the notification to the top of the local notification list.
3. Show snackbar/toast using `title` and `message`.
4. Use `type` and `data` for navigation.

---

## 8. Suggested Flutter Model

```dart
class AppNotification {
  final int id;
  final String type;
  final String title;
  final String message;
  final Map<String, dynamic> data;
  final String channel;
  final String status;
  final bool isRead;
  final String? referenceType;
  final String? referenceId;
  final DateTime? sentAt;
  final DateTime? readAt;
  final DateTime createdAt;

  AppNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.message,
    required this.data,
    required this.channel,
    required this.status,
    required this.isRead,
    required this.referenceType,
    required this.referenceId,
    required this.sentAt,
    required this.readAt,
    required this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    final reference = json['reference'] as Map<String, dynamic>?;

    return AppNotification(
      id: json['id'] as int,
      type: json['type'] as String,
      title: json['title'] as String,
      message: json['message'] as String,
      data: Map<String, dynamic>.from(json['data'] ?? {}),
      channel: json['channel'] as String? ?? 'in_app',
      status: json['status'] as String? ?? 'sent',
      isRead: json['is_read'] as bool? ?? json['read_at'] != null,
      referenceType: reference?['type'] as String? ?? json['reference_type'] as String?,
      referenceId: reference?['id']?.toString() ?? json['reference_id']?.toString(),
      sentAt: json['sent_at'] == null ? null : DateTime.parse(json['sent_at'] as String),
      readAt: json['read_at'] == null ? null : DateTime.parse(json['read_at'] as String),
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}
```

REST payload uses:

```txt
reference: { type, id }
```

Real-time payload uses:

```txt
reference_type
reference_id
```

The model above supports both formats.

---

## 9. Notification Types And Tap Actions

### `offer_claim_created`

Sent to customer when an offer claim is created.

```json
{
    "claim_id": "uuid",
    "product_id": "uuid",
    "store_id": "uuid",
    "expires_at": "2026-05-18T12:30:00+00:00"
}
```

Tap action: open claim details / QR code screen.

### `new_offer_claim`

Sent to store owner or employees when a customer claims an offer.

```json
{
    "claim_id": "uuid",
    "product_id": "uuid",
    "store_id": "uuid",
    "customer_id": "uuid"
}
```

Tap action: open store offer claims screen.

### `offer_redeemed`

Sent to customer when an offer is redeemed.

```json
{
    "claim_id": "uuid",
    "product_id": "uuid",
    "store_id": "uuid",
    "redeemed_at": "2026-05-18T12:00:00+00:00"
}
```

Tap action: open claim history or product details.

### `offer_redeemed_by_employee`

Sent to store owner when an employee redeems an offer.

```json
{
    "claim_id": "uuid",
    "product_id": "uuid",
    "store_id": "uuid",
    "customer_id": "uuid",
    "redeemed_by": "uuid"
}
```

Tap action: open store redemption history.

### `points_earned`

Sent to customer when points are earned.

```json
{
    "points": 20,
    "reason": "offer_redeemed",
    "claim_id": "uuid",
    "product_id": "uuid",
    "store_id": "uuid"
}
```

Tap action: open user points screen.

### `seller_points_earned`

Sent to store owner when store points are earned.

```json
{
    "points": 10,
    "reason": "offer_redeemed",
    "claim_id": "uuid",
    "product_id": "uuid",
    "store_id": "uuid"
}
```

Tap action: open store points screen.

### `store_approved`

Sent to store owner when the store is approved.

```json
{
    "store_id": "uuid",
    "status": "active",
    "approved_at": "2026-05-18T12:00:00+00:00"
}
```

Tap action: open store dashboard.

### `store_rejected`

Sent to store owner when the store is rejected.

```json
{
    "store_id": "uuid",
    "status": "rejected",
    "rejection_reason": "Missing documents"
}
```

Tap action: open store verification screen.

### `product_approved`

Sent to store owner when a product or revision is approved.

```json
{
    "product_id": "uuid",
    "store_id": "uuid",
    "revision_id": "uuid"
}
```

Tap action: open product details.

### `product_rejected`

Sent to store owner when a product or revision is rejected.

```json
{
    "product_id": "uuid",
    "store_id": "uuid",
    "revision_id": "uuid",
    "rejection_reason": "Invalid image"
}
```

Tap action: open product edit/revision screen.

---

## 10. Recommended App Flow

On app startup:

1. Get unread count.
2. Connect to Pusher.
3. Subscribe to `private-users.{userId}`.

On notification screen open:

1. Fetch `/api/v1/me/notifications?per_page=20`.
2. Render list.
3. Support pagination.

On receiving real-time event:

1. Decode event data.
2. Parse `notification`.
3. Set unread badge to `unread_count`.
4. Insert notification at top of list.
5. Show toast/snackbar.

On logout:

1. Unsubscribe from `private-users.{userId}`.
2. Disconnect Pusher.
3. Clear notification state.

On token refresh:

1. Reconnect Pusher with the new token.

---

## 11. Error Handling

### 401 Unauthorized

Token expired or invalid. Refresh token or logout.

### 403 Forbidden

User tried to access another user's notification/channel. Do not retry automatically.

### Pusher auth fails

Check:

- bearer token is sent to `/broadcasting/auth`
- token is valid
- channel name is `private-users.{loggedInUserId}`
- logged-in user ID matches channel ID
- API domain is correct

### Events not received

Check:

- user subscribed to the correct private channel
- event name is `notification.sent`
- backend queue is running
- backend `BROADCAST_CONNECTION=pusher`
- Pusher app key and cluster are correct

---

## 12. Manual Testing Checklist

1. Login to Flutter.
2. Save access token and user ID.
3. Call `GET /api/v1/me/notifications/unread-count`.
4. Connect to Pusher.
5. Subscribe to `private-users.{userId}`.
6. Trigger a backend action:
    - create offer claim
    - redeem offer claim
    - approve/reject store
    - approve/reject product revision
7. Confirm Flutter receives `notification.sent`.
8. Confirm unread count updates.
9. Open notifications screen and compare with REST list.
10. Mark notification as read.
11. Confirm unread count decreases.

---

## 13. Important Notes

- These are in-app notifications, not native push notifications.
- If the app is killed or offline, it will miss WebSocket events.
- On app start, always fetch notification list/unread count from REST API.
- Native push notifications require a separate FCM/APNs integration.
- Never store `PUSHER_APP_SECRET` in Flutter.
