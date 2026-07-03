# Profile Gender Null Default Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Store `null` when a newly created profile has no valid gender instead of silently defaulting to `male`.

**Architecture:** Keep normalization in the existing `Profile` model `creating` callback. Change only its invalid-value fallback and cover the lifecycle behavior with database-backed model tests.

**Tech Stack:** PHP 8, Laravel Eloquent, PHPUnit, SQLite in-memory test database

---

### Task 1: Cover nullable gender normalization

**Files:**
- Create: `tests/Feature/User/ProfileTest.php`
- Modify: `app/Domain/User/Models/Profile.php:34-35`

- [x] **Step 1: Write the failing tests**

Create `tests/Feature/User/ProfileTest.php`:

```php
<?php

namespace Tests\Feature\User;

use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_created_without_gender_stores_null(): void
    {
        $profile = $this->createProfile();

        $this->assertNull($profile->gender);
        $this->assertDatabaseHas('profiles', [
            'id' => $profile->id,
            'gender' => null,
        ]);
    }

    public function test_profile_created_with_unsupported_gender_stores_null(): void
    {
        $profile = $this->createProfile(['gender' => 'other']);

        $this->assertNull($profile->gender);
    }

    public function test_profile_created_with_valid_gender_normalizes_it_to_lowercase(): void
    {
        $profile = $this->createProfile(['gender' => 'FEMALE']);

        $this->assertSame('female', $profile->gender);
    }

    private function createProfile(array $attributes = []): Profile
    {
        $user = User::factory()->create();
        $user->profile()->delete();

        return $user->profile()->create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
        ], $attributes));
    }
}
```

- [x] **Step 2: Run the focused tests and verify the expected failure**

Run:

```bash
php artisan test tests/Feature/User/ProfileTest.php
```

Expected: the omitted and unsupported gender tests fail because the model stores `male`; the valid gender test passes.

- [x] **Step 3: Implement the minimal model change**

Replace the gender normalization in `app/Domain/User/Models/Profile.php` with:

```php
// Normalize valid gender values and preserve null for missing or unsupported values.
$gender = strtolower((string) $profile->gender);
$profile->gender = in_array($gender, ['male', 'female'], true) ? $gender : null;
```

- [x] **Step 4: Run the focused tests and verify they pass**

Run:

```bash
php artisan test tests/Feature/User/ProfileTest.php
```

Expected: 3 tests pass with no warnings or errors.

- [x] **Step 5: Run related regression tests**

Run:

```bash
php artisan test tests/Unit/RegisterUserTest.php tests/Feature/AuthenticationTest.php tests/Feature/Admin/UserManagementTest.php
```

Expected: all related tests pass with no warnings or errors.

- [x] **Step 6: Review production and test code quality**

Check that the change remains limited to profile creation normalization, uses strict membership checking, and does not add schema or factory changes.

- [x] **Step 7: Commit the implementation**

```bash
git add app/Domain/User/Models/Profile.php tests/Feature/User/ProfileTest.php docs/superpowers/plans/2026-07-03-profile-gender-null-default.md
git commit -m "fix: preserve null profile gender"
```
