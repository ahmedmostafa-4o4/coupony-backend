# Product Analytics Multi-Series Trends Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add additive product analytics trend presets for daily, monthly, and peak-time chart switching.

**Architecture:** Keep the existing `engagement.trend` field untouched and add a new `engagement.trends` object from `GetProductAnalyticsAction`. Reuse existing analytics models and period resolver; add focused helpers for fixed 7-day, fixed 6-month, and time-of-day interaction buckets.

**Tech Stack:** Laravel, PHPUnit feature tests, existing analytics domain actions.

---

### Task 1: Add Product Analytics Tests

**Files:**
- Modify: `tests/Feature/Analytics/ProductAnalyticsTest.php`

- [ ] **Step 1: Add a failing test for `engagement.trends`**

Add a test that creates likes/comments/saves/shares across the current date range and asserts:

```php
$response
    ->assertOk()
    ->assertJsonCount(7, 'engagement.trends.days')
    ->assertJsonCount(6, 'engagement.trends.months')
    ->assertJsonCount(4, 'engagement.trends.peak_times')
    ->assertJsonPath('engagement.trends.peak_times.0.label', 'night')
    ->assertJsonPath('engagement.trends.peak_times.1.label', 'morning')
    ->assertJsonPath('engagement.trends.peak_times.2.label', 'afternoon')
    ->assertJsonPath('engagement.trends.peak_times.3.label', 'evening');
```

- [ ] **Step 2: Run the focused test**

Run:

```bash
php artisan test tests/Feature/Analytics/ProductAnalyticsTest.php --filter=multi_series
```

Expected: FAIL because `engagement.trends` does not exist.

### Task 2: Implement Multi-Series Trends

**Files:**
- Modify: `app/Domain/Analytics/Actions/GetProductAnalyticsAction.php`

- [ ] **Step 1: Add `trends` to the engagement payload**

Add:

```php
'trends' => $this->computeTrendPresets($product, $start, $end),
```

inside `computeEngagement()`.

- [ ] **Step 2: Add helpers**

Add helpers that:

- Build 7 daily buckets ending today.
- Build 6 monthly buckets ending current month.
- Build 4 peak-time buckets for the resolved request range.
- Count interactions from `ProductLike`, `ProductComment`, `ProductFavorite`, and `ProductShare`.

- [ ] **Step 3: Run the focused test**

Run:

```bash
php artisan test tests/Feature/Analytics/ProductAnalyticsTest.php --filter=multi_series
```

Expected: PASS.

### Task 3: Update Docs

**Files:**
- Modify: `docs/Seller_Analytics/FLUTTER_SELLER_ANALYTICS_INTEGRATION.md`
- Modify: `docs/superpowers/specs/2026-07-01-product-analytics-multi-series-trends-design.md` only if implementation differs from the approved design.

- [ ] **Step 1: Add response documentation**

Document `engagement.trends.days`, `engagement.trends.months`, and `engagement.trends.peak_times`.

- [ ] **Step 2: Run docs placeholder scan**

Run:

```bash
rg -n "TBD|TODO|\\.\\.\\." docs/Seller_Analytics/FLUTTER_SELLER_ANALYTICS_INTEGRATION.md docs/superpowers/specs/2026-07-01-product-analytics-multi-series-trends-design.md
```

Expected: no matches.

### Task 4: Final Verification

**Files:**
- Verify only.

- [ ] **Step 1: Run product analytics tests**

Run:

```bash
php artisan test tests/Feature/Analytics/ProductAnalyticsTest.php
```

Expected: PASS.

- [ ] **Step 2: Run formatter**

Run:

```bash
vendor/bin/pint app/Domain/Analytics/Actions/GetProductAnalyticsAction.php tests/Feature/Analytics/ProductAnalyticsTest.php
```

Expected: PASS.

- [ ] **Step 3: Run diff check**

Run:

```bash
git diff --check -- app/Domain/Analytics/Actions/GetProductAnalyticsAction.php tests/Feature/Analytics/ProductAnalyticsTest.php docs/Seller_Analytics/FLUTTER_SELLER_ANALYTICS_INTEGRATION.md docs/superpowers/specs/2026-07-01-product-analytics-multi-series-trends-design.md
```

Expected: no output.

