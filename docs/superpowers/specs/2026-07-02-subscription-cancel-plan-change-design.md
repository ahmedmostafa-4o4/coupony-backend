# Subscription Cancel And Plan Change Design

## Context

Store owners currently have subscription payment, overview, status, plan, history, and entitlement endpoints, but no store-owner cancellation endpoint. The database and overview resource already expose `cancelled_at`, but store-owner flows do not set it.

Plan changes are also not a supported flow. A store owner can initiate payment for any active plan and billing cycle, but successful processing is inconsistent for existing active subscriptions. Webhook handling attempts an invalid `active -> active` state transition before updating the subscription, while manual confirmation skips plan and cycle updates when the subscription is already active.

## Goals

- Add a store-owner cancellation endpoint.
- Treat store-owner cancellation as cancel-at-period-end.
- Fix paid plan changes for already-active subscriptions.
- Keep the first implementation intentionally simple: no proration, no scheduled next-cycle plan changes, and no new subscription-change table.

## API Changes

Add:

```text
POST /stores/{store}/subscription/cancel
```

The endpoint must authorize with the existing `manageSubscription` policy. It must require an existing subscription that is currently in a cancellable service state: `trial`, `active`, `grace`, or `degraded`.

On success, it sets `cancelled_at` to the current timestamp and returns the existing `SubscriptionOverviewResource`. It must not immediately archive or suspend the subscription, because the store owner keeps access through the paid period.

If there is no subscription, or the subscription is already archived, suspended, or already has `cancelled_at`, the endpoint should return a validation-style error response rather than silently succeeding.

## Cancellation Lifecycle

Cancellation means "do not renew after the current period." The subscription remains in its current service state until `current_period_end`.

When a canceled subscription reaches the end of its current period, lifecycle processing should transition it out of active service using the existing state model. For an active canceled subscription, the expected final state is `archived`, because the user explicitly canceled rather than merely missing payment and entering grace.

The implementation should avoid disrupting uncanceled expiry behavior. Existing grace, degraded, and suspended lifecycle rules should keep working for subscriptions without `cancelled_at`.

## Plan Changes

The existing `POST /initiate-payment` endpoint remains the supported entry point for choosing a different active plan and billing cycle.

When a paid session is confirmed by webhook or by manual `confirm-payment`, the subscription should:

- become `active` if it is currently not active and the state machine allows restoration;
- remain `active` if it is already active;
- update `plan_id`, `billing_cycle`, `current_period_start`, `current_period_end`, and clear `cancelled_at`;
- create the existing active subscription history entry;
- dispatch the existing payment-approved event.

There is no proration in this implementation. The paid plan change takes effect immediately after successful payment.

## Components

- `SubscriptionController`: add the store-owner cancel action.
- Store subscription routes: register `POST /cancel`.
- A small domain action or focused controller logic for cancel-at-period-end. Prefer an action if the logic is more than a few lines or needs direct unit tests.
- `ProcessWebhookAction`: make successful payment handling idempotent for already-active subscriptions and update plan data consistently.
- `ConfirmPaymentAction`: update plan data for already-active subscriptions after paid sessions.
- Scheduled lifecycle job(s): add explicit canceled-at behavior at period end while preserving existing expiry paths.

## Testing

Use test-first changes.

Required tests:

- Store-owner cancel route sets `cancelled_at` and does not archive immediately.
- Store-owner cancel route rejects missing, suspended, archived, and already-canceled subscriptions.
- Webhook success updates an already-active subscription to the paid plan without throwing an invalid `active -> active` transition.
- Manual `confirm-payment` updates an already-active subscription to the paid plan.
- Successful paid plan change clears `cancelled_at`.
- Canceled active subscriptions transition out of service at period end.
- Existing uncanceled expiration behavior remains covered.

## Non-Goals

- Proration.
- Refunds.
- Payment-provider subscription cancellation.
- Scheduling a plan change for the next billing cycle.
- A separate subscription-change or cancellation-request table.
