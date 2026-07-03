# FCM Push Notifications Design

## Context

Coupony already has in-app notifications stored in the `notifications` table, exposed through `/api/v1/me/notifications`, and broadcast in real time through Reverb/Pusher-compatible private channels. Flutter now needs native mobile push notifications so users receive notification alerts while outside the application.

Current backend state:

- `NotificationService::send()` creates notification rows and dispatches `NotificationSent`.
- Flutter integration docs explicitly describe the current flow as in-app/realtime only.
- `UserPreference` already has `push_notifications`.
- `PushNotifier` is not production-ready: it has no device-token storage and uses the legacy FCM server-key endpoint.
- The project already includes `kreait/laravel-firebase`, so FCM should use Firebase Admin SDK credentials instead of legacy server-key HTTP calls.

## Goal

Send native FCM push notifications for every existing in-app notification type without changing the existing notification API, database notification list, unread count behavior, or Reverb realtime behavior.

## Non-Goals

- Do not replace Reverb realtime notifications.
- Do not migrate existing notification rows to Laravel's built-in notification tables.
- Do not introduce a third-party push provider beyond Firebase Cloud Messaging.
- Do not require changing each existing domain notification trigger to call a separate push channel.

## Recommended Approach

Use the existing in-app notification row as the source of truth. After a notification is successfully sent through the existing `in_app` channel, dispatch a queued FCM delivery job for the same notification.

This keeps current notification behavior stable while adding native push as a side effect. If FCM fails, the in-app notification remains available through the API and Reverb.

## Data Model

Add `user_device_tokens`:

- `id`
- `user_id` foreign key to `users.id`, cascade delete
- `token` FCM registration token, unique
- `platform` enum/string: `ios`, `android`, `web`, or `unknown`
- `device_id` nullable client-provided device identifier
- `app_version` nullable string
- `last_used_at` timestamp
- `revoked_at` nullable timestamp
- timestamps

Useful indexes:

- unique `token`
- index `user_id, revoked_at`
- optional unique `user_id, device_id, platform` when `device_id` is present is not required for the first version because FCM token uniqueness is enough.

Add a `UserDeviceToken` model under the user or notification domain. The user model should expose `deviceTokens()` for active-token queries.

## API Contract

All endpoints require `auth:sanctum` and `UseAuthenticatedUserLocale`.

### Register Device Token

`POST /api/v1/me/device-tokens`

Request:

```json
{
  "token": "fcm-registration-token",
  "platform": "android",
  "device_id": "optional-stable-device-id",
  "app_version": "1.2.3"
}
```

Behavior:

- Validate `token` as required string.
- Validate `platform` as nullable `ios`, `android`, `web`, or `unknown`.
- Upsert by `token`.
- Assign the token to the authenticated user.
- Clear `revoked_at`.
- Update metadata and `last_used_at`.

Response:

```json
{
  "data": {
    "token": "fcm-registration-token",
    "platform": "android",
    "device_id": "optional-stable-device-id",
    "app_version": "1.2.3",
    "last_used_at": "2026-07-03T00:00:00+00:00"
  }
}
```

### Unregister Device Token

`DELETE /api/v1/me/device-tokens`

Request:

```json
{
  "token": "fcm-registration-token"
}
```

Behavior:

- Only affects a token owned by the authenticated user.
- Sets `revoked_at` instead of deleting, preserving basic delivery history and preventing immediate reuse ambiguity.
- Returns `204 No Content`.

## Delivery Flow

1. Domain code calls `NotificationService::send(..., channel: 'in_app')` as it does today.
2. The notification row is created.
3. The in-app notifier marks the notification as sent and broadcasts the existing `NotificationSent` event.
4. If the channel is `in_app` and user push preferences allow it, dispatch `SendFcmPushNotificationJob`.
5. The job loads the notification and active device tokens.
6. The job sends a multicast FCM message with notification title/body and tap-navigation data.
7. Invalid or unregistered tokens are revoked.
8. Temporary FCM failures are logged without changing the in-app notification status.

Push delivery should not run for notification rows whose channel is already `push`, `email`, or `sms` in this first version. The goal is native push for the existing in-app notification stream.

## FCM Payload

Use the Firebase Admin SDK through `kreait/laravel-firebase`.

Notification:

```json
{
  "title": "Notification title",
  "body": "Notification message"
}
```

Data:

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

FCM data values must be strings, so nested notification data should be JSON encoded. Flutter can decode `data` and use `type`, `reference_type`, and `reference_id` for navigation.

## Preferences

Respect the existing `user_preferences.push_notifications` flag.

- If no preferences row exists, keep the current default behavior: push allowed.
- If `push_notifications` is false, do not dispatch the FCM job.
- In-app notifications remain unaffected by this preference.

## Error Handling

- Missing Firebase credentials should fail inside the queued job and be logged.
- A user with no active device tokens should produce no exception and no failed notification row.
- Invalid/unregistered FCM tokens should be marked revoked.
- Temporary send failures should be logged with notification ID, user ID, token count, and exception class.
- FCM failures must not mark the in-app notification as failed.

## Testing

Feature tests:

- Authenticated user can register an FCM token.
- Registering the same token updates ownership/metadata and clears `revoked_at`.
- Authenticated user can unregister only their own token.
- Sending any in-app notification dispatches the FCM job when push preference allows it.
- Sending an in-app notification does not dispatch FCM when `push_notifications` is false.

Unit tests:

- FCM payload includes title, body, notification ID, type, reference fields, and JSON-encoded data.
- Delivery service ignores revoked tokens.
- Delivery service revokes invalid/unregistered token responses.

Manual verification:

- Set `FIREBASE_CREDENTIALS` or `GOOGLE_APPLICATION_CREDENTIALS`.
- Flutter registers token after login.
- Trigger a known backend notification, such as offer claim creation.
- Confirm notification appears when the app is backgrounded or closed.
- Tap notification and confirm Flutter can navigate from the payload.

## Flutter Notes

Flutter should:

- Request notification permission on iOS and Android versions that require it.
- Register the FCM token after login.
- Re-register when FCM rotates the token.
- Unregister on logout.
- Continue using existing REST notification APIs to sync missed notifications and unread counts.
- Continue using Reverb while the app is foregrounded if desired.
