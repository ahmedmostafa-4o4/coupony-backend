# Offer Claim Expiry Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Persist overdue active offer claims as expired through both scheduled synchronization and attempted redemption.

**Architecture:** A focused Artisan command performs an idempotent set-based status update and Laravel's scheduler runs it every minute. The redemption transaction returns an expiry outcome after committing the status change, then raises the existing domain error outside the transaction.

**Tech Stack:** PHP 8, Laravel Artisan and scheduler, Eloquent, PHPUnit.

---

### Task 1: Expiry command

**Files:**
- Create: `app/Application/Console/Commands/ExpireOfferClaims.php`
- Create: `tests/Feature/Console/ExpireOfferClaimsCommandTest.php`

- [ ] **Step 1: Write failing command behavior tests**

Create claims covering overdue active, future active, null-expiry active, redeemed, cancelled, and already-expired states. Execute `offer-claims:expire`, assert only the overdue active claim becomes expired, assert the affected-count output, then run it again and assert zero affected rows.

- [ ] **Step 2: Verify the command test fails**

Run: `php artisan test tests/Feature/Console/ExpireOfferClaimsCommandTest.php`

Expected: FAIL because `offer-claims:expire` is not defined.

- [ ] **Step 3: Implement the command**

Define `ExpireOfferClaims extends Command` with signature `offer-claims:expire`. In `handle()`, update `OfferClaim` rows where status is `OfferClaimStatus::ACTIVE`, `expires_at` is non-null, and `expires_at <= now()` to `OfferClaimStatus::EXPIRED`; print the affected count and return `self::SUCCESS`.

- [ ] **Step 4: Verify the command test passes**

Run: `php artisan test tests/Feature/Console/ExpireOfferClaimsCommandTest.php`

Expected: PASS.

### Task 2: Scheduler registration

**Files:**
- Modify: `routes/console.php`
- Create: `tests/Feature/Console/OfferClaimExpiryScheduleTest.php`

- [ ] **Step 1: Write a failing scheduler test**

Inspect `Schedule::events()` and assert an event exists whose command contains `offer-claims:expire`, whose cron expression is `* * * * *`, and whose overlap-prevention mutex is enabled.

- [ ] **Step 2: Verify the scheduler test fails**

Run: `php artisan test tests/Feature/Console/OfferClaimExpiryScheduleTest.php`

Expected: FAIL because the expiry command has not been scheduled.

- [ ] **Step 3: Register the schedule**

Add `Schedule::command('offer-claims:expire')->everyMinute()->withoutOverlapping();` to `routes/console.php` under an offer-claim lifecycle section.

- [ ] **Step 4: Verify the scheduler test passes**

Run: `php artisan test tests/Feature/Console/OfferClaimExpiryScheduleTest.php`

Expected: PASS.

### Task 3: Redemption persistence

**Files:**
- Modify: `app/Domain/Product/Actions/RedeemOfferClaim.php`
- Modify: `tests/Feature/OfferClaimTest.php`

- [ ] **Step 1: Strengthen the existing expired-redemption test**

After the existing expired claim redemption request returns the expiry error, refresh the claim and assert its status is `OfferClaimStatus::EXPIRED`. Retain assertions proving no redemption side effects occur.

- [ ] **Step 2: Verify the regression test fails**

Run: `php artisan test tests/Feature/OfferClaimTest.php --filter=redeem_rejects_expired_and_already_redeemed_claims`

Expected: FAIL because the transaction rollback leaves the claim active.

- [ ] **Step 3: Commit expiry before raising the domain error**

Change the transaction result to permit an `expired` outcome. When an active claim is overdue, update it to expired and return that outcome from the transaction. After `DB::transaction()` completes, throw `DomainException('This claim has expired.')`; keep the successful redemption result and event flow unchanged.

- [ ] **Step 4: Verify redemption behavior passes**

Run: `php artisan test tests/Feature/OfferClaimTest.php --filter=redeem_rejects_expired_and_already_redeemed_claims`

Expected: PASS.

### Task 4: Customer expired filter integration

**Files:**
- Modify: `tests/Feature/API/V1/MyOfferClaimControllerTest.php`

- [ ] **Step 1: Add the integration test**

Create an overdue active customer claim, run `offer-claims:expire`, request `/api/v1/me/offer-claims?status=expired`, and assert the synchronized claim is returned with status `expired`.

- [ ] **Step 2: Run the integration test**

Run: `php artisan test tests/Feature/API/V1/MyOfferClaimControllerTest.php --filter=expired`

Expected: PASS using the command implemented in Task 1.

### Task 5: Quality gates

**Files:**
- Review all files changed by Tasks 1-4.

- [ ] **Step 1: Run focused tests**

Run: `php artisan test tests/Feature/Console/ExpireOfferClaimsCommandTest.php tests/Feature/Console/OfferClaimExpiryScheduleTest.php tests/Feature/API/V1/MyOfferClaimControllerTest.php tests/Feature/OfferClaimTest.php`

Expected: PASS.

- [ ] **Step 2: Format changed PHP files**

Run: `vendor/bin/pint --dirty`

Expected: success with no remaining formatting changes required.

- [ ] **Step 3: Run the full suite**

Run: `php artisan test`

Expected: PASS with no regressions.

- [ ] **Step 4: Review production and test code**

Apply clean-code-guard to production changes and test-guard to test changes. Resolve all must-fix findings, rerun affected tests, and inspect `git diff --check`.
