# Requirements Document

## Introduction

This document defines the requirements for a complete Subscription & Plans & Automated Payment System for the Coupony backend. The system replaces the previous manual payment review process with automated payment gateway integration (Paymob/Fawry), instant subscription activation, and enforcement middleware. Each store has an independent subscription lifecycle with plan-based limits and entitlements.

## Glossary

- **Subscription_System**: The backend module responsible for managing store subscriptions, payment sessions, plan enforcement, and lifecycle transitions.
- **Payment_Gateway**: The external payment service (Paymob or Fawry) that processes card and wallet payments via WebView.
- **Store**: A merchant entity in the Coupony platform that holds an independent subscription.
- **Plan**: A predefined package of limits and entitlements that a store subscribes to.
- **Billing_Cycle**: The recurring interval for a plan (monthly or yearly).
- **Payment_Session**: A single-use, time-limited record linking a store to a pending payment transaction.
- **Subscription_Status**: One of seven lifecycle states: none, trial, active, grace, degraded, suspended, archived.
- **History_Status**: The outcome status of a subscription record: active, expired, refunded, failed, cancelled.
- **Entitlement**: A specific limit or permission granted by a plan (e.g., max products, max employees).
- **Webhook**: An HTTP callback from the Payment_Gateway confirming payment outcome.
- **HMAC**: Hash-based Message Authentication Code used to verify webhook authenticity.
- **Enforcement_Middleware**: Laravel middleware that checks subscription status and entitlements before allowing access to protected endpoints.
- **Review_Mode**: A configuration flag that hides payment UI elements during app store review periods.
- **Audit_Log**: A record of all subscription state changes for traceability.

## Requirements

### Requirement 1: Payment Session Initiation

**User Story:** As a store owner, I want to initiate a payment session for a subscription plan, so that I can proceed to pay via the in-app WebView.

#### Acceptance Criteria

1. WHEN a store owner sends a POST request to `/api/v1/stores/{store_id}/subscription/initiate-payment` with a valid `plan_id` and `billing_cycle`, THE Subscription_System SHALL create a Payment_Session and return a `session_id` and `payment_url` for the WebView.
2. THE Subscription_System SHALL calculate the payment amount server-side from the `plan_id` and `billing_cycle`, ignoring any price sent by the client.
3. IF a Payment_Session already exists for the same store in a pending state, THEN THE Subscription_System SHALL return an error with code `PAYMENT_SESSION_ALREADY_USED` and HTTP status 409.
4. THE Subscription_System SHALL set a time-to-live on each Payment_Session, after which the session expires automatically.
5. WHEN the authenticated user does not own the specified `store_id`, THE Subscription_System SHALL return HTTP 403 Forbidden.
6. THE Subscription_System SHALL require authentication (Bearer token) for the initiate-payment endpoint.

### Requirement 2: Payment Confirmation

**User Story:** As a store owner, I want to confirm my payment after the WebView redirects back, so that my subscription activates instantly.

#### Acceptance Criteria

1. WHEN a store owner sends a POST request to `/api/v1/stores/{store_id}/subscription/confirm-payment` with a valid `session_id`, THE Subscription_System SHALL verify the session status and activate the subscription if the Payment_Gateway confirmed success.
2. IF the `session_id` has already been used, THEN THE Subscription_System SHALL return an error with code `PAYMENT_SESSION_ALREADY_USED` and HTTP status 409.
3. IF the `session_id` has expired, THEN THE Subscription_System SHALL return an error with code `PAYMENT_SESSION_EXPIRED` and HTTP status 410.
4. WHEN the subscription activates successfully, THE Subscription_System SHALL transition the Subscription_Status to `active` and record the activation in the Audit_Log.
5. WHEN the subscription activates successfully, THE Subscription_System SHALL send a `subscription_payment_approved` notification to the store owner.

### Requirement 3: Webhook Processing

**User Story:** As the system, I want to receive and process payment gateway webhooks, so that subscription status updates happen reliably regardless of client-side confirmation.

#### Acceptance Criteria

1. WHEN the Payment_Gateway sends a POST request to `/api/v1/webhooks/paymob`, THE Subscription_System SHALL validate the request using HMAC verification before processing.
2. IF the HMAC signature is invalid, THEN THE Subscription_System SHALL reject the webhook with HTTP status 401 and log the attempt.
3. WHEN a webhook indicates successful payment, THE Subscription_System SHALL mark the associated Payment_Session as paid and transition the Subscription_Status to `active`.
4. WHEN a webhook indicates failed payment, THE Subscription_System SHALL mark the Payment_Session as failed, record a `failed` History_Status entry, and send a `subscription_payment_failed` notification.
5. THE Subscription_System SHALL process each webhook idempotently, producing the same result if the same webhook is received multiple times.

### Requirement 4: Subscription Overview

**User Story:** As a store owner, I want to view my full subscription overview, so that I can understand my current plan, status, and usage.

#### Acceptance Criteria

1. WHEN a store owner sends a GET request to `/api/v1/stores/{store_id}/subscription/overview`, THE Subscription_System SHALL return the current Subscription_Status, active Plan details, billing cycle, renewal date, and current usage against entitlement limits.
2. WHEN the store has no subscription (status `none`), THE Subscription_System SHALL return the status as `none` with available plans information.
3. THE Subscription_System SHALL require authentication and verify store ownership before returning data.

### Requirement 5: Subscription Status (Banner Data)

**User Story:** As a mobile app, I want to fetch lightweight subscription status, so that I can display contextual banners to the store owner.

#### Acceptance Criteria

1. WHEN a store owner sends a GET request to `/api/v1/stores/{store_id}/subscription/status`, THE Subscription_System SHALL return the current Subscription_Status, days remaining, and any actionable message.
2. WHILE the Subscription_Status is `grace`, THE Subscription_System SHALL include a warning message indicating the grace period end date.
3. WHILE the Subscription_Status is `degraded`, THE Subscription_System SHALL include a message listing restricted features.
4. WHILE the Subscription_Status is `suspended`, THE Subscription_System SHALL include a message indicating the store is suspended and payment is required.

### Requirement 6: Available Plans

**User Story:** As a store owner, I want to view available subscription plans, so that I can choose the right plan for my store.

#### Acceptance Criteria

1. WHEN a store owner sends a GET request to `/api/v1/stores/{store_id}/subscription/plans`, THE Subscription_System SHALL return all available plans with their names, descriptions, prices per billing cycle, and entitlement limits.
2. THE Subscription_System SHALL include a `payment_config` object containing the `is_review_mode` flag and supported payment methods.
3. WHILE `is_review_mode` is true, THE Subscription_System SHALL indicate that payment buttons should be hidden in the response metadata.

### Requirement 7: Subscription History

**User Story:** As a store owner, I want to view my subscription history, so that I can track past payments and plan changes.

#### Acceptance Criteria

1. WHEN a store owner sends a GET request to `/api/v1/stores/{store_id}/subscription/history`, THE Subscription_System SHALL return a paginated list of subscription records ordered by creation date descending.
2. THE Subscription_System SHALL include for each record: plan name, billing cycle, amount paid, payment method, History_Status, start date, and end date.
3. THE Subscription_System SHALL support filtering history by History_Status (active, expired, refunded, failed, cancelled).

### Requirement 8: Entitlements and Limits

**User Story:** As a store owner, I want to view my current entitlements and usage, so that I know how much capacity I have remaining.

#### Acceptance Criteria

1. WHEN a store owner sends a GET request to `/api/v1/stores/{store_id}/subscription/entitlements`, THE Subscription_System SHALL return the current plan entitlements with usage counts and remaining capacity for each limit.
2. THE Subscription_System SHALL include both numeric limits (max products, max employees) and boolean permissions (feature access flags).
3. WHEN the store has no active subscription, THE Subscription_System SHALL return the free-tier entitlements (if applicable) or empty entitlements with zero limits.

### Requirement 9: Subscription Lifecycle State Machine

**User Story:** As the system, I want to manage subscription status transitions according to defined rules, so that stores experience predictable subscription behavior.

#### Acceptance Criteria

1. THE Subscription_System SHALL support exactly seven Subscription_Status values: none, trial, active, grace, degraded, suspended, archived.
2. WHEN a subscription period expires without renewal, THE Subscription_System SHALL transition the status from `active` to `grace` and send a `subscription_grace_started` notification.
3. WHEN the grace period expires without payment, THE Subscription_System SHALL transition the status from `grace` to `degraded` and send a `subscription_degraded` notification.
4. WHEN the degraded period expires without payment, THE Subscription_System SHALL transition the status from `degraded` to `suspended` and send a `subscription_suspended` notification.
5. WHEN a store owner pays during `grace` or `degraded` status, THE Subscription_System SHALL transition the status back to `active`.
6. THE Subscription_System SHALL send a `subscription_expiring_soon` notification before the subscription period ends.
7. THE Subscription_System SHALL record every status transition in the Audit_Log with timestamp, previous status, new status, and trigger reason.

### Requirement 10: Enforcement Middleware

**User Story:** As the system, I want to enforce subscription limits on protected endpoints, so that stores cannot exceed their plan entitlements.

#### Acceptance Criteria

1. WHEN a store attempts to access a subscription-protected endpoint and the Subscription_Status is `none`, THE Enforcement_Middleware SHALL return HTTP 403 with error code `SUBSCRIPTION_REQUIRED`.
2. WHEN a store attempts to access a subscription-protected endpoint and the Subscription_Status is `suspended`, THE Enforcement_Middleware SHALL return HTTP 403 with error code `STORE_SUSPENDED`.
3. WHEN a store attempts to access a subscription-protected endpoint and the Subscription_Status is `archived`, THE Enforcement_Middleware SHALL return HTTP 403 with error code `STORE_ARCHIVED`.
4. WHEN a store attempts to create a resource and the plan limit for that resource type is reached, THE Enforcement_Middleware SHALL return HTTP 403 with error code `SUBSCRIPTION_LIMIT_REACHED`.
5. WHEN a store attempts to access a feature not included in the current plan, THE Enforcement_Middleware SHALL return HTTP 403 with error code `SUBSCRIPTION_FEATURE_LOCKED`.
6. WHILE the Subscription_Status is `degraded`, THE Enforcement_Middleware SHALL allow read-only access but block write operations that exceed free-tier limits.

### Requirement 11: Security and Integrity

**User Story:** As the system, I want to ensure all subscription operations are secure and auditable, so that payment data and subscription state remain trustworthy.

#### Acceptance Criteria

1. THE Subscription_System SHALL require authentication (Bearer token via Laravel Sanctum) for all subscription endpoints except the webhook endpoint.
2. THE Subscription_System SHALL verify that the authenticated user owns or has permission to manage the specified `store_id` before processing any request.
3. THE Subscription_System SHALL validate Paymob webhooks using HMAC signature verification with a server-side secret.
4. THE Subscription_System SHALL ensure each `session_id` is single-use; once consumed (success or failure), the session cannot be reused.
5. THE Subscription_System SHALL prevent concurrent Payment_Sessions for the same store by rejecting new initiation requests while a pending session exists.
6. THE Subscription_System SHALL log all subscription state changes, payment events, and security violations in the Audit_Log.

### Requirement 12: Notifications

**User Story:** As a store owner, I want to receive timely notifications about my subscription status, so that I can take action before losing access.

#### Acceptance Criteria

1. WHEN a payment succeeds, THE Subscription_System SHALL send a `subscription_payment_approved` notification to the store owner.
2. WHEN a payment fails, THE Subscription_System SHALL send a `subscription_payment_failed` notification to the store owner.
3. WHEN the subscription is within a configurable number of days before expiry, THE Subscription_System SHALL send a `subscription_expiring_soon` notification.
4. WHEN the subscription enters grace period, THE Subscription_System SHALL send a `subscription_grace_started` notification.
5. WHEN the subscription enters degraded status, THE Subscription_System SHALL send a `subscription_degraded` notification.
6. WHEN the subscription enters suspended status, THE Subscription_System SHALL send a `subscription_suspended` notification.

### Requirement 13: Review Mode Configuration

**User Story:** As the system administrator, I want to toggle review mode, so that payment UI elements are hidden during app store review periods without affecting backend logic.

#### Acceptance Criteria

1. WHILE `is_review_mode` is set to true in the system configuration, THE Subscription_System SHALL include `is_review_mode: true` in the plans endpoint response.
2. THE Subscription_System SHALL not alter subscription logic or enforcement when review mode is active; only the client-facing metadata changes.
3. THE Subscription_System SHALL allow toggling `is_review_mode` via environment configuration without requiring code deployment.

### Requirement 14: Scheduled Subscription Lifecycle Jobs

**User Story:** As the system, I want to run scheduled jobs that transition subscriptions through lifecycle states, so that expired subscriptions are handled automatically.

#### Acceptance Criteria

1. THE Subscription_System SHALL run a scheduled job that identifies subscriptions past their renewal date and transitions them from `active` to `grace`.
2. THE Subscription_System SHALL run a scheduled job that identifies subscriptions past their grace period and transitions them from `grace` to `degraded`.
3. THE Subscription_System SHALL run a scheduled job that identifies subscriptions past their degraded period and transitions them from `degraded` to `suspended`.
4. THE Subscription_System SHALL run a scheduled job that sends `subscription_expiring_soon` notifications for subscriptions expiring within a configurable threshold.
5. THE Subscription_System SHALL ensure scheduled jobs are idempotent, producing correct results even if executed multiple times for the same period.
