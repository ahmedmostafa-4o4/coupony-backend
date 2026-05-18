# Pusher Connection Setup Guide

This document explains how to configure Coupony real-time notifications using **Pusher Channels** instead of Laravel Reverb.

This setup is recommended for **shared hosting environments**, such as Hostinger Shared Hosting, because Laravel Reverb requires a long-running WebSocket process and shared hosting usually does not support persistent background processes.

---

## 1. Why Pusher Instead Of Reverb?

Laravel Reverb requires this command to stay running all the time:

```bash
php artisan reverb:start
```

On shared hosting, long-running processes are usually not reliable or are blocked by the hosting provider.

Pusher solves this by hosting the WebSocket infrastructure externally. Laravel only sends broadcast events to Pusher, and Pusher delivers them to Flutter clients in real time.

---

## 2. Create A Pusher Channels App

1. Go to the Pusher dashboard.
2. Choose **Channels**.
3. Click **Create app**.
4. App name example:

```txt
coupony-production
```

5. Choose the closest cluster to Egypt:

```txt
eu (EU Ireland)
```

6. Backend tech can be Laravel/PHP.
7. Frontend tech can be anything; Flutter is not always listed.
8. Create the app.
9. Open **App Keys**.

Copy these values:

```env
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=eu
```

Important:

- `PUSHER_APP_KEY` can be shared with the Flutter developer.
- `PUSHER_APP_CLUSTER` can be shared with the Flutter developer.
- `PUSHER_APP_SECRET` must stay backend-only and must not be sent to the Flutter app.

---

## 3. Backend Package Installation

Install the Pusher PHP package:

```bash
composer require pusher/pusher-php-server
composer dump-autoload
```

Commit and deploy the updated `composer.json` and `composer.lock`.

If installing directly on Hostinger using SSH, run the same command from the Laravel project root.

---

## 4. Laravel `.env` Configuration

Use this production configuration:

```env
BROADCAST_CONNECTION=pusher
BROADCAST_ENABLED=true
QUEUE_CONNECTION=database

PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=eu

PUSHER_PORT=443
PUSHER_SCHEME=https
```

Do not set `PUSHER_HOST` for normal Pusher Channels usage.

Leave it missing or empty:

```env
PUSHER_HOST=
```

The Laravel config automatically resolves the Pusher API host as:

```txt
api-{PUSHER_APP_CLUSTER}.pusher.com
```

For cluster `eu`, that becomes:

```txt
api-eu.pusher.com
```

Only use `PUSHER_HOST` when using a self-hosted Pusher-compatible server such as Soketi.

---

## 5. Clear Laravel Config Cache

After changing `.env`, run:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

For production, you can then run:

```bash
php artisan config:cache
php artisan route:cache
```

---

## 6. Hostinger Shared Hosting Cron Jobs

Pusher removes the need to run `php artisan reverb:start`.

You still need the Laravel queue to process queued notification listeners.

### Queue Cron Job

In Hostinger hPanel:

```txt
Websites → Manage → Advanced → Cron Jobs
```

Add this cron job to run every minute:

```bash
cd /home/u123456789/domains/YOUR_DOMAIN/coupony-backend && /usr/bin/php artisan queue:work --stop-when-empty --tries=3 --timeout=60 >> storage/logs/queue-cron.log 2>&1
```

Replace this path:

```txt
/home/u123456789/domains/YOUR_DOMAIN/coupony-backend
```

with the real project path on Hostinger.

### Laravel Scheduler Cron Job

If the app uses Laravel scheduled tasks, add:

```bash
cd /home/u123456789/domains/YOUR_DOMAIN/coupony-backend && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

Run it every minute.

---

## 7. Flutter Configuration Values

Send only these values to the Flutter developer:

```txt
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_CLUSTER=eu
AUTH_ENDPOINT=https://your-domain.com/broadcasting/auth
CHANNEL=private-users.{userId}
EVENT=notification.sent
```

Do not send:

```txt
PUSHER_APP_SECRET
```

The Flutter app should authenticate private channels using the logged-in user's bearer token:

```http
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
```

---

## 8. Real-Time Channel And Event

The backend broadcasts to this Laravel private channel:

```txt
users.{userId}
```

Pusher clients subscribe to it as:

```txt
private-users.{userId}
```

The event name is:

```txt
notification.sent
```

The Flutter app should listen for:

```txt
notification.sent
```

---

## 9. Private Channel Auth Endpoint

Flutter's Pusher client will call:

```http
POST /broadcasting/auth
```

Example full URL:

```txt
https://your-domain.com/broadcasting/auth
```

The request must include:

```http
Authorization: Bearer <ACCESS_TOKEN>
```

The request body is handled by the Pusher Flutter package and usually contains:

```txt
socket_id=<socket-id>&channel_name=private-users.<userId>
```

The backend will approve the subscription only if the authenticated user ID matches the channel user ID.

---

## 10. Test From Laravel Tinker

Run:

```bash
php artisan tinker
```

Then send a test notification:

```php
$user = \App\Domain\User\Models\User::first();

app(\App\Domain\Notification\Services\NotificationService::class)->send(
    $user,
    'test_notification',
    'Test notification',
    'This is a test notification from Pusher.',
    'in_app',
    ['source' => 'tinker']
);
```

Then check:

1. Pusher Dashboard → App → Debug Console.
2. Flutter client listening on `private-users.{userId}`.
3. Database `notifications` table.

---

## 11. Expected Real-Time Payload

Flutter receives an event like this:

```json
{
  "notification": {
    "id": 123,
    "type": "test_notification",
    "title": "Test notification",
    "message": "This is a test notification from Pusher.",
    "data": {
      "source": "tinker"
    },
    "channel": "in_app",
    "status": "sent",
    "reference_type": null,
    "reference_id": null,
    "read_at": null,
    "created_at": "2026-05-18T12:00:00+00:00"
  },
  "unread_count": 1
}
```

---

## 12. Troubleshooting

### Events are saved in database but not received by Flutter

Check:

```env
BROADCAST_CONNECTION=pusher
BROADCAST_ENABLED=true
QUEUE_CONNECTION=database
```

Then make sure queue cron is running.

### Pusher Debug Console does not show events

Check:

- `pusher/pusher-php-server` is installed.
- `.env` values are correct.
- `php artisan optimize:clear` was run after `.env` changes.
- `PUSHER_APP_CLUSTER=eu` matches the Pusher app cluster.
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, and `PUSHER_APP_SECRET` are correct.

### Private channel auth fails

Check:

- Flutter sends `Authorization: Bearer <ACCESS_TOKEN>`.
- User subscribes to `private-users.{loggedInUserId}`.
- The token belongs to the same user ID in the channel.
- `/broadcasting/auth` is reachable on production.

### Wrong cluster

For Egypt, use:

```env
PUSHER_APP_CLUSTER=eu
```

Do not use `ap2`, `mt1`, or another cluster unless the Pusher app was created with that cluster.

---

## 13. Deployment Checklist

1. Create Pusher Channels app.
2. Choose cluster `eu`.
3. Install package:

```bash
composer require pusher/pusher-php-server
```

4. Set `.env`:

```env
BROADCAST_CONNECTION=pusher
BROADCAST_ENABLED=true
QUEUE_CONNECTION=database
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=eu
PUSHER_PORT=443
PUSHER_SCHEME=https
```

5. Clear/cache config:

```bash
php artisan optimize:clear
php artisan config:cache
```

6. Add Hostinger cron for queue.
7. Send Flutter developer:

```txt
PUSHER_APP_KEY
PUSHER_APP_CLUSTER=eu
AUTH_ENDPOINT=https://your-domain.com/broadcasting/auth
CHANNEL=private-users.{userId}
EVENT=notification.sent
```

8. Test with Laravel Tinker.
9. Confirm Pusher Debug Console receives events.
10. Confirm Flutter receives `notification.sent`.
