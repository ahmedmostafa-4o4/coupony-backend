# Offer Claim Expiry Design

## Problem

Offer claims store an enum status and expose `expired` as a filter value. The customer claims endpoint filters the stored `offer_claims.status` column, while expiry is otherwise determined from `expires_at`. Consequently, an active claim whose expiry time has passed remains absent from `GET /api/v1/me/offer-claims?status=expired` until some code persists the expired status.

The redemption action attempts that persistence, but it throws the expiry error inside the same database transaction. The transaction rollback therefore also rolls back the status update.

## Design

Add an idempotent Artisan command named `offer-claims:expire`. It performs one set-based update of claims that:

- have status `active`;
- have a non-null `expires_at`; and
- have `expires_at` less than or equal to the current time.

The command changes only the status to `expired`, reports the affected row count, and succeeds when no rows qualify. Schedule it every minute with overlap protection so persisted status converges promptly without concurrent executions of the same command.

Correct the redemption expiry path so the locked claim is changed to `expired` within the transaction, the transaction commits, and the existing domain error is thrown afterward. No inventory, redemption count, points, revenue, or notification effects may occur for an expired claim.

## API Behavior

No endpoint contract changes. After expiry synchronization, `GET /api/v1/me/offer-claims?status=expired` returns claims whose persisted status was updated by the command or an attempted redemption. The `is_expired` resource field remains derived from `expires_at`.

There can be up to approximately one scheduler interval between `expires_at` passing and background persistence. Direct redemption during that interval persists the status immediately before returning the expiry error.

## Testing

Tests will verify:

- the command expires only overdue active claims;
- future, redeemed, cancelled, already-expired, and null-expiry claims are unchanged;
- repeated command execution is idempotent;
- the scheduled command is registered at the intended frequency with overlap protection;
- the customer status filter returns a claim after command synchronization; and
- attempting to redeem an overdue active claim persists `expired` while preserving the existing error response and avoiding redemption side effects.

## Operational Requirement

Production must run Laravel's scheduler (`php artisan schedule:run` once per minute, or an equivalent supported scheduler worker). Without that process, scheduled synchronization will not execute.
