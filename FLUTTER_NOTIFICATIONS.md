# Flutter Notifications Integration

This document is for the Flutter developer integrating Coupony in-app, real-time, and native push notifications.

The backend stores notifications in the database, broadcasts new notifications in real time using Laravel Reverb, and sends Firebase Cloud Messaging push notifications for existing `in_app` and `email` notification rows. Reverb uses the Pusher protocol, so Flutter should use a Pusher-compatible client for foreground realtime updates.

---

## 1. What Flutter Needs To Implement

Flutter should support three flows:

1. REST API notifications
   - list notifications
   - list unread notifications
   - get unread count
   - mark one notification read/unread
   - mark all notifications as read
   - delete notifications

2. Real-time notifications
   - connect to Laravel Reverb
   - authenticate a private channel
   - subscribe to the current user's channel
   - listen for `notification.sent`
   - update UI immediately

3. Native push notifications
   - request OS notification permission
   - register the current FCM token with the backend
   - re-register when Firebase rotates the token
   - unregister the token on logout
   - handle tap navigation from the FCM data payload

---

## 2. Authentication

All notification endpoints require the user access token.

Use these headers for REST requests:

```http
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
```

The same bearer token is also required for the private WebSocket channel auth request to:

```http
POST /broadcasting/auth
```

---

## 3. REST API Endpoints

Base API prefix:

```txt
/api/v1
```

### List notifications

```http
GET /api/v1/me/notifications?per_page=20
```

`per_page` is optional. Minimum `1`, maximum `100`, default `20`.

Example response:

```json
{
  "data": [
    {
      "id": 1,
      "type": "offer_redeemed",
      "title": "Offer redeemed",
      "message": "Your offer was redeemed successfully.",
      "data": {
        "claim_id": "uuid",
        "product_id": "uuid",
        "store_id": "uuid",
        "redeemed_at": "2026-05-18T12:00:00+00:00"
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

Example response:

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

The backend only allows the authenticated user to access their own notifications. Otherwise, it returns `403`.

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

Example response:

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

This deletes read notifications only. Unread notifications remain.

### Delete one notification

```http
DELETE /api/v1/me/notifications/{notificationId}
```

---

## 4. Real-Time WebSocket Details

Backend channel name:

```txt
users.{userId}
```

Pusher/Reverb private channel name used by Flutter:

```txt
private-users.{userId}
```

Event name:

```txt
notification.sent
```

Important notes:

- Flutter must subscribe to `private-users.{loggedInUserId}`.
- The backend authorizes the channel only when the authenticated user ID equals `{userId}`.
- If using Laravel Echo syntax, the event is usually listened to as `.notification.sent`.
- If using a direct Pusher-compatible Flutter client, listen for `notification.sent`.

---

## 5. Reverb Config Needed By Flutter

Ask backend/devops for these values per environment:

```txt
REVERB_APP_KEY
REVERB_HOST
REVERB_PORT
REVERB_SCHEME
```

Example local values:

```txt
REVERB_APP_KEY=local-key
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

Example production values:

```txt
REVERB_APP_KEY=<production-key>
REVERB_HOST=api.example.com
REVERB_PORT=443
REVERB_SCHEME=https
```

If `REVERB_SCHEME=https`, use TLS/SSL for the WebSocket connection.

---

## 6. Private Channel Authentication

Flutter's Pusher-compatible client must authorize private channels through:

```http
POST /broadcasting/auth
```

Headers:

```http
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/x-www-form-urlencoded
```

Body sent by the Pusher client:

```txt
socket_id=<socket-id>&channel_name=private-users.<userId>
```

The backend rejects the auth request if the token user does not match the channel user ID.

---

## 7. Suggested Flutter Package

Use a Pusher-compatible Flutter package, for example:

```yaml
dependencies:
  pusher_channels_flutter: ^latest
```

Use the latest compatible version from `pub.dev`.

Another Pusher-compatible client is also fine as long as it supports:

- custom host/port/scheme
- private channel auth endpoint
- authorization headers
- event listening

---

## 8. Example Flutter Realtime Service

This is a reference example. Adapt it to the app architecture and the exact package version.

```dart
import 'dart:convert';

import 'package:pusher_channels_flutter/pusher_channels_flutter.dart';

class RealtimeNotificationService {
  final PusherChannelsFlutter _pusher = PusherChannelsFlutter.getInstance();

  Future<void> connect({
    required String userId,
    required String accessToken,
    required String apiBaseUrl,
    required String reverbAppKey,
    required String reverbHost,
    required int reverbPort,
    required bool useTls,
    required void Function(Map<String, dynamic> notification, int unreadCount) onNotification,
  }) async {
    await _pusher.init(
      apiKey: reverbAppKey,
      cluster: 'mt1',
      host: reverbHost,
      port: reverbPort,
      useTLS: useTls,
      authEndpoint: '$apiBaseUrl/broadcasting/auth',
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

        final payload = jsonDecode(event.data) as Map<String, dynamic>;
        final notification = payload['notification'] as Map<String, dynamic>;
        final unreadCount = payload['unread_count'] as int? ?? 0;

        onNotification(notification, unreadCount);
      },
    );

    await _pusher.subscribe(channelName: 'private-users.$userId');
    await _pusher.connect();
  }

  Future<void> disconnect({required String userId}) async {
    await _pusher.unsubscribe(channelName: 'private-users.$userId');
    await _pusher.disconnect();
  }
}
```

Package APIs can differ between versions. If the package supports auth headers directly instead of `onAuthorizer`, configure `Authorization: Bearer <ACCESS_TOKEN>` there.

---

## 9. Real-Time Event Payload

When a notification is sent, Flutter receives:

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

Recommended behavior when this event arrives:

1. Update the notification bell count from `unread_count`.
2. Prepend the notification to the current notification list if loaded.
3. Show a snackbar/toast using `title` and `message`.
4. Use `type` and `data` to navigate to the correct screen.

---

## 10. Notification Types And Tap Actions

### `offer_claim_created`

Sent to customer when an offer claim is created.

Data:

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

Sent to store owner/employees when a customer claims an offer.

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

Sent to customer when their offer is redeemed.

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

Sent to store owner when an employee redeems an offer claim.

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

Tap action: open user points/wallet screen.

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

Sent to store owner when a product/revision is approved.

```json
{
  "product_id": "uuid",
  "store_id": "uuid",
  "revision_id": "uuid"
}
```

Tap action: open product details.

### `product_rejected`

Sent to store owner when a product/revision is rejected.

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

## 11. Suggested Flutter Model

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
      readAt: json['read_at'] == null ? null : DateTime.parse(json['read_at'] as String),
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }
}
```

Note:

- REST API uses `reference: { type, id }`.
- Real-time payload uses `reference_type` and `reference_id`.
- The model above supports both shapes.

---

## 12. Suggested State Handling

On app start:

1. Call `GET /api/v1/me/notifications/unread-count`.
2. Connect to Reverb.
3. Subscribe to `private-users.{userId}`.

On notification screen open:

1. Call `GET /api/v1/me/notifications`.
2. Store paginated results.

On real-time notification:

1. Decode event JSON.
2. Parse `notification`.
3. Set unread counter to `unread_count`.
4. Add notification to top of list.
5. Show snackbar/toast.

On logout:

1. Unsubscribe from `private-users.{userId}`.
2. Disconnect WebSocket.
3. Clear local notification state.

On token refresh:

1. Reconnect WebSocket using the new token.

---

## 13. Error Handling

### 401 Unauthorized

Token expired or invalid.

Action: refresh token or logout.

### 403 Forbidden

User tried to access another user's notification/channel.

Action: do not retry automatically; log/report the issue.

### WebSocket disconnected

Action: rely on auto-reconnect if supported and refresh via REST API when app returns online.

### Private channel auth failed

Check:

- bearer token is sent to `/broadcasting/auth`
- channel is `private-users.{loggedInUserId}`
- API base URL is correct
- Reverb server is running

---

## 14. Manual Testing Checklist

1. Login and get access token.
2. Call `GET /api/v1/me/notifications/unread-count`.
3. Connect to Reverb.
4. Subscribe to `private-users.{loggedInUserId}`.
5. Trigger one backend action:
   - create offer claim
   - redeem offer claim
   - approve/reject store
   - approve/reject product revision
6. Confirm Flutter receives `notification.sent`.
7. Confirm notification bell count updates.
8. Open notifications screen and compare with REST list.
9. Mark a notification as read.
10. Confirm unread count decreases.

---

## 15. Native Push Notifications

The backend sends Firebase Cloud Messaging push notifications for existing `in_app` and `email` notification rows. Reverb remains the realtime foreground channel; FCM is for OS-level notifications while the app is backgrounded, closed, or outside the foreground experience.

### Register FCM token

```http
POST /api/v1/me/device-tokens
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
```

```json
{
  "token": "<FCM_TOKEN>",
  "platform": "android",
  "device_id": "optional-device-id",
  "app_version": "1.2.3"
}
```

Response:

```json
{
  "data": {
    "token": "<FCM_TOKEN>",
    "platform": "android",
    "device_id": "optional-device-id",
    "app_version": "1.2.3",
    "last_used_at": "2026-07-03T00:00:00+00:00"
  }
}
```

Supported `platform` values are `ios`, `android`, `web`, and `unknown`.

### Unregister FCM token

```http
DELETE /api/v1/me/device-tokens
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Content-Type: application/json
```

```json
{
  "token": "<FCM_TOKEN>"
}
```

The backend returns `204 No Content`. Flutter should call this during logout for the current token.

### FCM data payload

The push data payload includes string values:

```json
{
  "notification_id": "123",
  "type": "offer_redeemed",
  "reference_type": "App\\Domain\\Product\\Models\\OfferClaim",
  "reference_id": "uuid",
  "channel": "in_app",
  "data": "{\"claim_id\":\"uuid\",\"product_id\":\"uuid\"}"
}
```

Flutter should decode `data` as JSON and use `type`, `reference_type`, and `reference_id` for tap navigation. The existing REST notification APIs remain the source for syncing notification history and unread counts.

---

## 16. Backend Local Commands

Backend developer should run:

```bash
php artisan reverb:start
php artisan queue:listen
```

If the queue worker is not running, queued domain notification listeners and queued FCM push delivery may not process.

---

## 17. Important Notes

- Database notifications remain the source of truth for notification history and unread counts.
- FCM push is sent for existing `in_app` and `email` notification rows when the user has push notifications enabled.
- If the app is killed or offline, fetch missed notifications using REST on next app start.
- Flutter must still configure Firebase Cloud Messaging client-side and request OS notification permission where required.
- Current real-time event: `notification.sent`.
- Current private channel: `private-users.{userId}`.
