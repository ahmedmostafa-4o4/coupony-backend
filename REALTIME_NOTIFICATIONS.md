# Real-Time Notifications

## Overview

- Notifications are stored in the database using the custom notification model.
- Notifications are broadcast in real time via Laravel Reverb.
- Users listen on a private per-user channel: `users.{userId}`.
- Real-time notification events use the broadcast event name: `notification.sent`.

## Backend `.env` Example

```env
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
REVERB_APP_ID=local-app
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Backend Commands

```bash
php artisan reverb:start
php artisan queue:listen
```

## API Endpoints

All endpoints require authentication.

```http
GET    /api/v1/me/notifications
GET    /api/v1/me/notifications/unread
GET    /api/v1/me/notifications/unread-count
PATCH  /api/v1/me/notifications/read-all
DELETE /api/v1/me/notifications/read
GET    /api/v1/me/notifications/{notification}
PATCH  /api/v1/me/notifications/{notification}/read
PATCH  /api/v1/me/notifications/{notification}/unread
DELETE /api/v1/me/notifications/{notification}
```

## Frontend Echo Example

```js
Echo.private(`users.${user.id}`)
  .listen('.notification.sent', (event) => {
    console.log(event.notification);
    console.log(event.unread_count);
  });
```

## Notification Types

- `offer_claim_created`
- `new_offer_claim`
- `offer_redeemed`
- `offer_redeemed_by_employee`
- `points_earned`
- `seller_points_earned`
- `store_approved`
- `store_rejected`
- `product_approved`
- `product_rejected`

## Troubleshooting

- Reverb server not running: start it with `php artisan reverb:start`.
- Wrong `BROADCAST_CONNECTION`: set `BROADCAST_CONNECTION=reverb`.
- Private channel auth failed: confirm the user is authenticated and listens on `users.{userId}` for their own ID.
- Queue worker not running: start it with `php artisan queue:listen`.
- Sanctum auth/cookie/token issue: confirm the frontend sends the correct Sanctum cookie or bearer token to `/broadcasting/auth`.
