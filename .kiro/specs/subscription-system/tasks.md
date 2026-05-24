# Implementation Plan: Subscription System

## Overview

Implement a complete Subscription & Plans & Automated Payment System for the Coupony backend using Laravel. The system introduces a new `Subscription` domain following existing DDD patterns, integrates with Paymob for payment processing, enforces plan-based entitlements via middleware, and manages subscription lifecycle transitions through scheduled jobs.

## Tasks

- [x] 1. Set up domain structure, configuration, and database schema
  - [x] 1.1 Create configuration and enums
    - Create `config/subscription.php` with all settings (review mode, session TTL, Paymob credentials, grace/degraded period defaults)
    - Create `SubscriptionStatus`, `PaymentSessionStatus`, `BillingCycle`, and `HistoryStatus` enums in `app/Domain/Subscription/Enums/`
    - _Requirements: 9.1, 13.1, 13.3_

  - [x] 1.2 Create database migrations
    - Create migration for `subscription_plans` table with UUID PK, pricing columns, entitlement limits, features JSON, grace/degraded period days, sort_order
    - Create migration for `subscriptions` table with UUID PK, store_id FK (unique), plan_id FK, status enum, billing_cycle, period timestamps, grace/degraded period ends
    - Create migration for `payment_sessions` table with UUID PK, store_id FK, plan_id FK, amount, status enum, Paymob references, expires_at, composite index on store_id+status
    - Create migration for `subscription_history` table with UUID PK, store_id FK, plan_id FK, amount, status enum, period dates
    - Create migration for `subscription_audit_logs` table with UUID PK, store_id FK, subscription_id FK, event_type, previous/new status, reason, metadata JSON
    - _Requirements: 9.1, 7.1, 7.2, 11.6_

  - [x] 1.3 Create Eloquent models
    - Create `SubscriptionPlan` model with UUID trait, `features` array cast, `getPriceForCycle()` helper
    - Create `Subscription` model with UUID trait, status enum cast, relationships to Store, Plan, AuditLogs
    - Create `PaymentSession` model with UUID trait, `isExpired()`, `isPending()` methods, `scopePending` query scope
    - Create `SubscriptionHistory` model with UUID trait, relationships to Store, Plan
    - Create `SubscriptionAuditLog` model with UUID trait, `metadata` array cast
    - _Requirements: 9.1, 1.4, 7.1_

  - [x] 1.4 Create repositories
    - Create `SubscriptionRepository` with methods: `findByStore()`, `updateStatus()`
    - Create `PaymentSessionRepository` with methods: `findPendingByStore()`, `findBySessionId()`, `markAsPaid()`, `markAsFailed()`, `expireSessions()`
    - Create `SubscriptionPlanRepository` with methods: `findActive()`, `findById()`
    - _Requirements: 1.3, 11.5_

- [x] 2. Implement core services
  - [x] 2.1 Implement SubscriptionStateMachine service
    - Create `app/Domain/Subscription/Services/SubscriptionStateMachine.php`
    - Define allowed transitions map (none→trial/active, trial→active/none, active→grace, grace→active/degraded, degraded→active/suspended, suspended→active/archived)
    - Implement `canTransition()`, `transition()`, `getAllowedTransitions()` methods
    - Throw `InvalidStateTransitionException` for disallowed transitions
    - Record audit log entry on every transition
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5, 9.7_

  - [x] 2.2 Write property test for state machine transitions
    - **Property 11: Lifecycle state transitions**
    - **Validates: Requirements 9.2, 9.3, 9.4, 14.1, 14.2, 14.3**

  - [x] 2.3 Write property test for audit log completeness
    - **Property 13: Audit log completeness**
    - **Validates: Requirements 9.7, 11.6**

  - [x] 2.4 Implement PaymobService
    - Create `app/Domain/Subscription/Services/PaymobService.php`
    - Implement `authenticate()` — POST to Paymob auth endpoint, return token
    - Implement `createOrder()` — Register order with Paymob
    - Implement `generatePaymentKey()` — Generate payment key for iframe/WebView
    - Implement `getPaymentUrl()` — Construct WebView URL from payment key and iframe_id
    - Implement `validateHmac()` — Compute HMAC from payload fields and compare with signature
    - Throw `PaymobApiException` on network/API failures
    - _Requirements: 1.1, 3.1, 11.3_

  - [x] 2.5 Write property test for HMAC validation
    - **Property 7: HMAC validation gate**
    - **Validates: Requirements 3.1, 3.2, 11.3**

  - [x] 2.6 Implement EntitlementService
    - Create `app/Domain/Subscription/Services/EntitlementService.php`
    - Implement `getEntitlements()` — Returns plan limits, current usage, and remaining capacity
    - Implement `checkLimit()` — Compares current usage against plan limit for a resource type
    - Implement `checkFeatureAccess()` — Checks feature flag in plan's features JSON
    - Implement `getCurrentUsage()` — Queries actual counts (products, employees, branches) for a store
    - _Requirements: 8.1, 8.2, 8.3, 10.4, 10.5_

  - [x] 2.7 Write property test for entitlement arithmetic
    - **Property 18: Entitlement arithmetic invariant**
    - **Validates: Requirements 8.1**

- [x] 3. Implement payment actions
  - [x] 3.1 Implement InitiatePaymentAction
    - Create `app/Domain/Subscription/Actions/InitiatePaymentAction.php`
    - Check for existing pending session (reject with 409 if found)
    - Calculate amount server-side from plan's configured price for the billing cycle
    - Call PaymobService to authenticate, create order, generate payment key, get URL
    - Create PaymentSession record with TTL (expires_at = now + configured minutes)
    - Return PaymentSession with session_id and payment_url
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 11.5_

  - [x] 3.2 Write property test for server-side price calculation
    - **Property 1: Server-side price calculation**
    - **Validates: Requirements 1.2**

  - [x] 3.3 Write property test for pending session uniqueness
    - **Property 2: Pending session uniqueness**
    - **Validates: Requirements 1.3, 11.5**

  - [x] 3.4 Write property test for session TTL invariant
    - **Property 3: Session TTL invariant**
    - **Validates: Requirements 1.4**

  - [x] 3.5 Implement ConfirmPaymentAction
    - Create `app/Domain/Subscription/Actions/ConfirmPaymentAction.php`
    - Validate session belongs to store
    - Reject if session already consumed (409, `PAYMENT_SESSION_ALREADY_USED`)
    - Reject if session expired (410, `PAYMENT_SESSION_EXPIRED`)
    - If webhook already confirmed (session status=paid), activate subscription via StateMachine
    - Create SubscriptionHistory entry with `active` status
    - Dispatch `SubscriptionPaymentApproved` event
    - Return updated Subscription
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 3.6 Write property test for session single-use enforcement
    - **Property 5: Session single-use enforcement**
    - **Validates: Requirements 2.2, 11.4**

  - [x] 3.7 Write property test for expired session rejection
    - **Property 6: Expired session rejection**
    - **Validates: Requirements 2.3**

  - [x] 3.8 Implement ProcessWebhookAction
    - Create `app/Domain/Subscription/Actions/ProcessWebhookAction.php`
    - Validate HMAC signature via PaymobService (reject with 401 if invalid)
    - Find PaymentSession by Paymob order/transaction reference
    - Handle idempotency: if session already processed, return 200 without changes
    - On success: mark session as paid, transition subscription to active, dispatch `SubscriptionPaymentApproved` event
    - On failure: mark session as failed, create history entry with `failed` status, dispatch `SubscriptionPaymentFailed` event
    - Log security violations for invalid HMAC attempts
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 3.9 Write property test for webhook idempotency
    - **Property 10: Webhook idempotency**
    - **Validates: Requirements 3.5**

  - [x] 3.10 Write property test for successful payment activation
    - **Property 8: Successful payment activates subscription**
    - **Validates: Requirements 2.1, 3.3**

  - [x] 3.11 Write property test for failed payment recording
    - **Property 9: Failed payment records failure**
    - **Validates: Requirements 3.4**

  - [x] 3.12 Implement TransitionSubscriptionAction
    - Create `app/Domain/Subscription/Actions/TransitionSubscriptionAction.php`
    - Delegate to SubscriptionStateMachine for validation and transition
    - Dispatch `SubscriptionStatusChanged` event with previous and new status
    - _Requirements: 9.2, 9.3, 9.4, 9.5_

  - [x] 3.13 Write property test for payment restoring active status
    - **Property 12: Payment restores active status**
    - **Validates: Requirements 9.5**

- [x] 4. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Implement HTTP layer (controllers, requests, resources)
  - [x] 5.1 Create Form Requests
    - Create `InitiatePaymentRequest` — validates `plan_id` (exists in subscription_plans, is_active=true), `billing_cycle` (in: monthly, yearly)
    - Create `ConfirmPaymentRequest` — validates `session_id` (required, uuid)
    - _Requirements: 1.1, 2.1_

  - [x] 5.2 Create API Resources
    - Create `SubscriptionOverviewResource` — serializes subscription status, plan details, billing cycle, renewal date, usage
    - Create `SubscriptionStatusResource` — serializes lightweight status, days remaining, actionable message
    - Create `SubscriptionPlanResource` — serializes plan name, description, prices, entitlements, payment_config with is_review_mode
    - Create `SubscriptionHistoryResource` — serializes history record with plan name, amount, method, status, dates
    - Create `EntitlementResource` — serializes entitlements with limit, usage, remaining for each type
    - _Requirements: 4.1, 5.1, 6.1, 7.2, 8.1_

  - [x] 5.3 Implement SubscriptionController
    - Create `app/Application/Http/Controllers/API/V1/SubscriptionController.php`
    - `initiatePayment()` — authorize store ownership, call InitiatePaymentAction, return session_id + payment_url
    - `confirmPayment()` — authorize store ownership, call ConfirmPaymentAction, return subscription data
    - `overview()` — authorize, return SubscriptionOverviewResource with current usage
    - `status()` — authorize, return SubscriptionStatusResource with contextual messages for grace/degraded/suspended
    - `plans()` — authorize, return SubscriptionPlanResource collection with payment_config (is_review_mode, supported methods)
    - `history()` — authorize, return paginated SubscriptionHistoryResource with optional status filter
    - `entitlements()` — authorize, return EntitlementResource
    - _Requirements: 1.1, 2.1, 4.1, 4.2, 4.3, 5.1, 5.2, 5.3, 5.4, 6.1, 6.2, 6.3, 7.1, 7.3, 8.1_

  - [x] 5.4 Implement WebhookController
    - Create `app/Application/Http/Controllers/API/V1/WebhookController.php`
    - `paymob()` — call ProcessWebhookAction, return appropriate HTTP status
    - No authentication middleware (HMAC-verified internally)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 5.5 Register API routes
    - Add subscription routes under `stores/{store}/subscription` prefix with auth middleware
    - Add webhook route at `/webhooks/paymob` without auth middleware
    - _Requirements: 1.6, 11.1_

  - [x] 5.6 Write property test for store ownership authorization
    - **Property 4: Store ownership authorization**
    - **Validates: Requirements 1.5, 4.3, 11.2**

  - [x] 5.7 Write property test for history filter correctness
    - **Property 19: History filter correctness**
    - **Validates: Requirements 7.3**

  - [x] 5.8 Write property test for history ordering
    - **Property 20: History ordering**
    - **Validates: Requirements 7.1**

  - [x] 5.9 Write property test for review mode logic invariance
    - **Property 22: Review mode logic invariance**
    - **Validates: Requirements 13.2**

- [x] 6. Implement enforcement middleware
  - [x] 6.1 Create CheckSubscription middleware
    - Create `app/Http/Middleware/CheckSubscription.php`
    - Resolve store from route parameter
    - Check subscription status: reject `none` (SUBSCRIPTION_REQUIRED), `suspended` (STORE_SUSPENDED), `archived` (STORE_ARCHIVED)
    - If `resourceType` parameter provided, call `EntitlementService::checkLimit()` — reject with SUBSCRIPTION_LIMIT_REACHED if exceeded
    - If `feature` parameter provided, call `EntitlementService::checkFeatureAccess()` — reject with SUBSCRIPTION_FEATURE_LOCKED if not included
    - In `degraded` status: allow GET requests, block POST/PUT/DELETE that exceed free-tier limits
    - Register middleware in kernel/route service provider
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

  - [x] 6.2 Write property test for blocked status enforcement
    - **Property 14: Blocked status enforcement**
    - **Validates: Requirements 10.1, 10.2, 10.3**

  - [x] 6.3 Write property test for limit enforcement
    - **Property 15: Limit enforcement**
    - **Validates: Requirements 10.4**

  - [x] 6.4 Write property test for feature lock enforcement
    - **Property 16: Feature lock enforcement**
    - **Validates: Requirements 10.5**

  - [x] 6.5 Write property test for degraded mode read-only access
    - **Property 17: Degraded mode read-only access**
    - **Validates: Requirements 10.6**

- [x] 7. Implement events, listeners, and notifications
  - [x] 7.1 Create events and listeners
    - Create `SubscriptionPaymentApproved` event with subscription and store data
    - Create `SubscriptionPaymentFailed` event with session and store data
    - Create `SubscriptionStatusChanged` event with previous status, new status, store data
    - Create `SendSubscriptionNotification` listener that dispatches appropriate notification based on event type
    - Register events and listeners in `EventServiceProvider`
    - _Requirements: 2.5, 3.4, 12.1, 12.2, 12.4, 12.5, 12.6_

  - [x] 7.2 Create notification classes
    - Create notification for `subscription_payment_approved`
    - Create notification for `subscription_payment_failed`
    - Create notification for `subscription_expiring_soon`
    - Create notification for `subscription_grace_started`
    - Create notification for `subscription_degraded`
    - Create notification for `subscription_suspended`
    - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 12.6_

- [x] 8. Implement scheduled lifecycle jobs
  - [x] 8.1 Create TransitionToGraceJob
    - Query active subscriptions where `current_period_end < now`
    - For each, call TransitionSubscriptionAction to move to `grace` status
    - Set `grace_period_end` based on plan's `grace_period_days`
    - Ensure idempotency (skip already-transitioned subscriptions)
    - _Requirements: 9.2, 14.1, 14.5_

  - [x] 8.2 Create TransitionToDegradedJob
    - Query grace subscriptions where `grace_period_end < now`
    - For each, call TransitionSubscriptionAction to move to `degraded` status
    - Set `degraded_period_end` based on plan's `degraded_period_days`
    - Ensure idempotency
    - _Requirements: 9.3, 14.2, 14.5_

  - [x] 8.3 Create TransitionToSuspendedJob
    - Query degraded subscriptions where `degraded_period_end < now`
    - For each, call TransitionSubscriptionAction to move to `suspended` status
    - Ensure idempotency
    - _Requirements: 9.4, 14.3, 14.5_

  - [x] 8.4 Create SendExpiringNotificationJob
    - Query active subscriptions where days until `current_period_end` ≤ configured `expiring_soon_days` threshold
    - Send `subscription_expiring_soon` notification for each
    - Track sent notifications to avoid duplicates (idempotent)
    - _Requirements: 9.6, 12.3, 14.4_

  - [x] 8.5 Register scheduled jobs in Console Kernel
    - Register all four jobs to run daily
    - _Requirements: 14.1, 14.2, 14.3, 14.4_

  - [x] 8.6 Write property test for scheduled job idempotency
    - **Property 21: Scheduled job idempotency**
    - **Validates: Requirements 14.5**

  - [x] 8.7 Write property test for expiring soon notification threshold
    - **Property 23: Expiring soon notification threshold**
    - **Validates: Requirements 9.6, 14.4**

- [-] 9. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 10. Integration wiring and final verification
  - [x] 10.1 Create database seeder for subscription plans
    - Create `SubscriptionPlanSeeder` with sample plans (Basic, Premium, Enterprise) with realistic limits and pricing
    - _Requirements: 6.1_

  - [x] 10.2 Apply middleware to existing store routes
    - Add `CheckSubscription` middleware to product creation, employee management, and other subscription-protected routes
    - Ensure existing routes that should be gated are properly protected
    - _Requirements: 10.1, 10.4, 10.5_

  - [-] 10.3 Write integration tests for full payment flow
    - Test initiate → webhook → confirm → active lifecycle
    - Test initiate → failed webhook → failed history entry
    - Mock PaymobService for external API calls
    - _Requirements: 1.1, 2.1, 3.3, 3.4_

  - [-] 10.4 Write integration tests for subscription lifecycle
    - Test active → grace → degraded → suspended via scheduled jobs
    - Test payment during grace/degraded restores active
    - Verify notifications sent at each transition
    - _Requirements: 9.2, 9.3, 9.4, 9.5, 14.1, 14.2, 14.3_

  - [-] 10.5 Write integration tests for enforcement middleware
    - Test middleware blocks access for none/suspended/archived statuses
    - Test middleware enforces resource limits
    - Test middleware enforces feature access
    - Test degraded mode allows reads, blocks writes
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_

- [~] 11. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties from the design document using PHPUnit data providers with Faker-generated inputs (100+ iterations)
- Unit tests validate specific examples and edge cases
- The PaymobService should be mocked in all tests except dedicated Paymob integration tests
- All models use UUID primary keys following the existing project pattern
- The implementation follows the existing DDD structure: Domain layer for business logic, Application layer for HTTP concerns

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "1.4"] },
    { "id": 2, "tasks": ["2.1", "2.4", "2.6"] },
    { "id": 3, "tasks": ["2.2", "2.3", "2.5", "2.7", "3.1", "3.5", "3.8", "3.12"] },
    { "id": 4, "tasks": ["3.2", "3.3", "3.4", "3.6", "3.7", "3.9", "3.10", "3.11", "3.13"] },
    { "id": 5, "tasks": ["5.1", "5.2"] },
    { "id": 6, "tasks": ["5.3", "5.4", "5.5", "6.1", "7.1", "7.2"] },
    { "id": 7, "tasks": ["5.6", "5.7", "5.8", "5.9", "6.2", "6.3", "6.4", "6.5", "8.1", "8.2", "8.3", "8.4", "8.5"] },
    { "id": 8, "tasks": ["8.6", "8.7", "10.1", "10.2"] },
    { "id": 9, "tasks": ["10.3", "10.4", "10.5"] }
  ]
}
```
