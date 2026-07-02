# AI Daily Message Limits Design

## Goal

Add durable daily AI request quotas for customers and sellers. Customer quota is fixed and enabled only in production. Seller quota is defined by the store's active subscription plan.

## Scope

- Customer text chat and image search share one daily quota.
- Seller chat uses a daily quota shared by all authorized users of the store.
- Customer production quota is 15 requests per day.
- Customer quota is unlimited outside production, including development and testing.
- Seller plan quotas are Basic 15, Pro 30, and Enterprise 60 requests per day.
- Existing short-window Pony AI rate limiting remains independent of these daily quotas.

## Data Model

Add `max_ai_messages_per_day` to `subscription_plans` as a non-negative integer. Update the plan seeder and factory to supply the field.

Add an `ai_message_usages` table with:

- `id`
- `usage_date`, interpreted in the application timezone
- `subject_type`, restricted by application code to `customer` or `store`
- `subject_id`, containing the customer user ID or store ID
- `used`
- timestamps

Enforce a unique constraint across `usage_date`, `subject_type`, and `subject_id`. This row is the durable source of truth for a subject's daily consumption. Conversation deletion must not reduce usage.

## Quota Resolution

A dedicated AI quota service resolves limits and owns reservation and release operations.

For customer requests:

- In production, use the fixed configured limit of 15.
- Outside production, return an unlimited quota and do not create usage rows.
- Key usage by authenticated user ID.

For seller requests:

- Resolve the store's current subscription and plan.
- Use `max_ai_messages_per_day` from that plan.
- Key usage by store ID so employees and owners consume the same subscription quota.
- Continue relying on existing subscription middleware for subscription status and `ai_assistant` feature access.

Daily windows start and end at midnight in the configured application timezone. Quota responses expose the next midnight as `resets_at`.

## Reservation Flow

Controllers reserve one unit after authentication, authorization, request validation, and conversation validation, but before invoking an AI strategy.

Reservation uses a database transaction and locks the subject's daily usage row. If the row does not exist, the service creates it while respecting the unique constraint. The service increments only when `used < limit`, preventing concurrent requests from exceeding quota.

The customer text and image-search endpoints use the same customer subject key. The seller endpoint uses the store subject key.

Successful AI requests retain the reservation. If AI processing throws an exception, the controller releases the reservation without allowing usage to fall below zero. Requests rejected before reservation do not consume quota.

## API Behavior

When quota is exhausted, return HTTP 429 with localized messaging, the stable error code `AI_DAILY_LIMIT_REACHED`, and:

```json
{
  "limit": 15,
  "used": 15,
  "remaining": 0,
  "resets_at": "2026-07-03T00:00:00+03:00"
}
```

Successful customer and seller AI responses include the updated quota details. For an unlimited non-production customer quota, `limit` and `remaining` are `null`, `used` is `0`, and `resets_at` is `null`.

The seller subscription entitlement response adds `ai_messages` under numeric limits with `limit`, today's `usage`, and `remaining`. Subscription plan resources expose `max_ai_messages_per_day`. Customer quota is not a subscription entitlement.

## Error Handling

- Invalid requests, unauthorized access, and missing conversations do not reserve quota.
- AI strategy failures release the reservation before returning the existing AI error response.
- A missing or ineligible seller subscription continues to use existing subscription middleware errors.
- Database reservation failures fail closed and return a server error rather than allowing unmetered AI use.

## Configuration

Add a Pony AI quota configuration section with a customer production limit defaulting to 15. Environment detection uses Laravel's application environment rather than a separately maintained production flag.

Seller limits remain database plan attributes so plan changes can be managed without changing application logic.

## Testing

Tests cover:

- A production customer can consume 15 combined text and image-search requests; request 16 returns HTTP 429.
- Customer quotas are bypassed in development and testing.
- Seller limits resolve to Basic 15, Pro 30, and Enterprise 60.
- Seller usage is shared across store users and isolated between stores.
- Usage resets at midnight in the application timezone.
- Atomic reservations cannot exceed the quota under competing requests.
- AI failures release reservations.
- Conversation deletion does not reduce usage.
- Seller entitlements report AI limit, usage, and remaining values.
- Existing feature-access and short-window rate-limit behavior remains intact.

## Out Of Scope

- Token-based billing or separate quotas by model.
- Separate customer quotas for text and image search.
- Carrying unused quota into another day.
- Administrative quota overrides or usage adjustment endpoints.
- Historical usage analytics beyond durable daily counter rows.
