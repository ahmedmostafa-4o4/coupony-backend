# Database Schema Documentation

## Overview
This document provides a comprehensive overview of the Coupony application database schema.

**Database Type:** MySQL/MariaDB  
**Character Set:** utf8mb4  
**Collation:** utf8mb4_unicode_ci  
**Primary Key Strategy:** UUID for main entities, Auto-increment for supporting tables

---

## Table of Contents
1. [User Management](#user-management)
2. [Authentication & Security](#authentication--security)
3. [Store Management](#store-management)
4. [Categories & Classification](#categories--classification)
5. [Location & Addresses](#location--addresses)
6. [Notifications & Communication](#notifications--communication)
7. [Contact & Engagement](#contact--engagement)
8. [System Tables](#system-tables)

---

## User Management

### `users`
Core user accounts table.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | CHAR(36) | PRIMARY KEY, UUID | User unique identifier |
| email | VARCHAR(255) | UNIQUE, NOT NULL | User email address |
| password_hash | VARCHAR(255) | NOT NULL | Hashed password |
| phone_number | VARCHAR(20) | UNIQUE, NULLABLE | User phone number |
| email_verified_at | TIMESTAMP | NULLABLE | Email verification timestamp |
| phone_verified_at | TIMESTAMP | NULLABLE | Phone verification timestamp |
| status | ENUM | DEFAULT 'active' | active, suspended, deleted |
| last_login_at | TIMESTAMP | NULLABLE | Last login timestamp |
| login_count | INT UNSIGNED | DEFAULT 0 | Total login count |
| shard_key | VARCHAR(50) | NULLABLE, INDEXED | Sharding key for partitioning |
| remember_token | VARCHAR(100) | NULLABLE | Remember me token |
| two_factor_enabled | BOOLEAN | DEFAULT false | 2FA status |
| last_ip | VARCHAR(45) | NULLABLE | Last login IP address |
| provider | VARCHAR(50) | NULLABLE | OAuth provider (google, facebook) |
| provider_id | VARCHAR(255) | NULLABLE | OAuth provider user ID |
| language | VARCHAR(10) | DEFAULT 'ar' | User preferred language |
| timezone | VARCHAR(50) | DEFAULT 'UTC' | User timezone |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Indexes:**
- `idx_email` on (email)
- `idx_phone` on (phone_number)
- `idx_provider` on (provider, provider_id)
- `idx_status_created` on (status, created_at)
- `idx_shard_key` on (shard_key)

---

### `profiles`
Extended user profile information.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Profile ID |
| user_id | CHAR(36) | UNIQUE, NOT NULL | Foreign key to users |
| first_name | VARCHAR(100) | NULLABLE | User first name |
| last_name | VARCHAR(100) | NULLABLE | User last name |
| date_of_birth | DATE | NULLABLE | User birth date |
| gender | ENUM | NULLABLE | male, female |
| avatar_url | TEXT | NULLABLE | Profile picture URL |
| bio | TEXT | NULLABLE | User biography |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

### `user_points`
User loyalty points tracking.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Points record ID |
| user_id | CHAR(36) | UNIQUE, NOT NULL | Foreign key to users |
| current_balance | INT | DEFAULT 0 | Current points balance |
| lifetime_earned | INT | DEFAULT 0 | Total points earned |
| lifetime_spent | INT | DEFAULT 0 | Total points spent |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

### `user_preferences`
User application preferences and settings.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Preference ID |
| user_id | CHAR(36) | UNIQUE, NOT NULL | Foreign key to users |
| interesting_offers | JSON | NULLABLE | User interests (electronics, fashion, etc.) |
| shopping_style | JSON | NULLABLE | Shopping preferences (online, in_store) |
| budget | VARCHAR(50) | NULLABLE | Budget category (low, medium, high) |
| notification_preferences | JSON | NULLABLE | Notification settings |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

## Authentication & Security

### `sessions`
User session management.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | CHAR(36) | PRIMARY KEY, UUID | Session ID |
| user_id | CHAR(36) | NULLABLE, INDEXED | Foreign key to users |
| token | VARCHAR(255) | UNIQUE, NOT NULL | Session token |
| user_agent | TEXT | NULLABLE | Browser user agent |
| ip_address | VARCHAR(45) | NULLABLE | Session IP address |
| payload | VARCHAR(255) | NULLABLE | Session data |
| device_type | VARCHAR(255) | NULLABLE | Device type |
| last_activity | INT UNSIGNED | INDEXED | Last activity timestamp |
| expires_at | TIMESTAMP | NOT NULL | Session expiration |
| verified_at | TIMESTAMP | NULLABLE | Verification timestamp |
| revoked_at | TIMESTAMP | NULLABLE | Revocation timestamp |
| revoked_reason | VARCHAR(255) | NULLABLE | Revocation reason |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- `idx_user_expires` on (user_id, expires_at)
- `idx_token` on (token)

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

### `personal_access_tokens`
API token management (Laravel Sanctum).

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Token ID |
| tokenable_type | VARCHAR(255) | NOT NULL | Polymorphic type |
| tokenable_id | BIGINT UNSIGNED | NOT NULL | Polymorphic ID |
| name | VARCHAR(255) | NOT NULL | Token name |
| token | VARCHAR(64) | UNIQUE, NOT NULL | Hashed token |
| abilities | TEXT | NULLABLE | Token permissions |
| last_used_at | TIMESTAMP | NULLABLE | Last usage timestamp |
| expires_at | TIMESTAMP | NULLABLE | Token expiration |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- Composite index on (tokenable_type, tokenable_id)

---

### `otps`
One-Time Password management.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | OTP ID |
| user_id | CHAR(36) | NULLABLE | Foreign key to users |
| phone_or_email | VARCHAR(255) | INDEXED, NOT NULL | Recipient identifier |
| otp_hash | VARCHAR(255) | NOT NULL | Hashed OTP code |
| purpose | VARCHAR(255) | INDEXED, NOT NULL | verify_email, reset_password, login |
| channel | VARCHAR(255) | NOT NULL | email, sms, whatsapp |
| status | ENUM | DEFAULT 'pending' | pending, verified, expired, blocked |
| attempts | TINYINT UNSIGNED | DEFAULT 0 | Verification attempts |
| max_attempts | TINYINT UNSIGNED | DEFAULT 3 | Maximum attempts allowed |
| expires_at | TIMESTAMP | INDEXED, NOT NULL | OTP expiration |
| used_at | TIMESTAMP | NULLABLE | Usage timestamp |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- `idx_phone_email` on (phone_or_email)
- `idx_purpose` on (purpose)
- `idx_status` on (status)
- `idx_expires` on (expires_at)

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

### `password_reset_tokens`
Password reset token storage.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| email | VARCHAR(255) | PRIMARY KEY | User email |
| token | VARCHAR(255) | NOT NULL | Reset token |
| created_at | TIMESTAMP | NULLABLE | Token creation timestamp |

---

### `roles` & `permissions`
Role-based access control (Spatie Permission).

**`roles`**
| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Role ID |
| name | VARCHAR(255) | NOT NULL | Role name (admin, seller, customer) |
| guard_name | VARCHAR(255) | NOT NULL | Guard name (sanctum) |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**`permissions`**
| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Permission ID |
| name | VARCHAR(255) | NOT NULL | Permission name |
| guard_name | VARCHAR(255) | NOT NULL | Guard name (sanctum) |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**`model_has_roles`** - Pivot table for user-role assignment  
**`model_has_permissions`** - Pivot table for direct permissions  
**`role_has_permissions`** - Pivot table for role-permission assignment

---

### `user_roles`
Store-specific role assignments.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Assignment ID |
| user_id | CHAR(36) | NOT NULL | Foreign key to users |
| role_id | BIGINT UNSIGNED | NOT NULL | Foreign key to roles |
| store_id | CHAR(36) | NULLABLE | Foreign key to stores |
| granted_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Grant timestamp |
| granted_by_user_id | CHAR(36) | NULLABLE | Granter user ID |
| expires_at | TIME | NULLABLE | Expiration time |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- `idx_user_store` on (user_id, store_id)
- `unique_user_role_store` UNIQUE on (user_id, role_id, store_id)

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)
- `role_id` → `roles.id` (CASCADE DELETE)
- `store_id` → `stores.id` (CASCADE DELETE)
- `granted_by_user_id` → `users.id` (SET NULL)

---

## Store Management

### `stores`
Store/merchant accounts.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | CHAR(36) | PRIMARY KEY, UUID | Store unique identifier |
| owner_user_id | CHAR(36) | INDEXED, NOT NULL | Foreign key to users |
| name | VARCHAR(255) | NOT NULL | Store name |
| description | TEXT | NULLABLE | Store description |
| logo_url | TEXT | NULLABLE | Store logo path |
| banner_url | TEXT | NULLABLE | Store banner path |
| email | VARCHAR(255) | NULLABLE | Store contact email |
| phone | VARCHAR(20) | NULLABLE | Store contact phone |
| tax_id | VARCHAR(100) | NULLABLE | Tax identification number |
| commission_rate | DECIMAL(5,4) | DEFAULT 0.1500 | Platform commission rate |
| status | ENUM | DEFAULT 'pending' | pending, active, rejected, suspended, closed |
| subscription_tier | ENUM | DEFAULT 'free' | free, basic, premium, enterprise |
| is_verified | BOOLEAN | DEFAULT false | Verification status |
| verified_at | TIMESTAMP | NULLABLE | Verification timestamp |
| total_sales | DECIMAL(15,2) | DEFAULT 0 | Total sales amount |
| rating_avg | DECIMAL(3,2) | DEFAULT 0 | Average rating |
| rating_count | INT UNSIGNED | DEFAULT 0 | Total ratings count |
| shard_key | VARCHAR(50) | NULLABLE | Sharding key |
| approved_at | TIMESTAMP | NULLABLE | Approval timestamp |
| approved_by | CHAR(36) | NULLABLE | Approver user ID |
| rejected_at | TIMESTAMP | NULLABLE | Rejection timestamp |
| rejected_by | CHAR(36) | NULLABLE | Rejector user ID |
| rejection_reason | TEXT | NULLABLE | Rejection reason |
| admin_notes | TEXT | NULLABLE | Admin notes |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |
| deleted_at | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Indexes:**
- Index on (owner_user_id)
- `idx_subscription` on (subscription_tier)

**Foreign Keys:**
- `owner_user_id` → `users.id` (RESTRICT DELETE)
- `approved_by` → `users.id` (SET NULL)
- `rejected_by` → `users.id` (SET NULL)

---

### `store_verifications`
Store verification documents.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | CHAR(36) | PRIMARY KEY, UUID | Verification ID |
| store_id | CHAR(36) | INDEXED, NOT NULL | Foreign key to stores |
| document_type | VARCHAR(255) | NOT NULL | commercial_register, tax_card, id_card_front, id_card_back |
| document_path | VARCHAR(255) | NOT NULL | Document file path |
| status | ENUM | DEFAULT 'pending' | pending, approved, rejected |
| verified_by | CHAR(36) | NULLABLE | Verifier user ID |
| verified_at | TIMESTAMP | NULLABLE | Verification timestamp |
| rejection_reason | TEXT | NULLABLE | Rejection reason |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- Index on (store_id)
- Index on (status)
- Index on (document_type)
- UNIQUE on (store_id, document_type)

**Foreign Keys:**
- `store_id` → `stores.id` (CASCADE DELETE)

---

### `store_hours`
Store operating hours.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Hours ID |
| store_id | CHAR(36) | INDEXED, NOT NULL | Foreign key to stores |
| day_of_week | TINYINT | NOT NULL | 0=Sunday, 6=Saturday |
| open_time | TIME | NULLABLE | Opening time |
| close_time | TIME | NULLABLE | Closing time |
| is_closed | BOOLEAN | DEFAULT false | Closed for the day |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Foreign Keys:**
- `store_id` → `stores.id` (CASCADE DELETE)

---

### `store_followers`
Store follower relationships.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Follow ID |
| user_id | CHAR(36) | NOT NULL | Foreign key to users |
| store_id | CHAR(36) | NOT NULL | Foreign key to stores |
| created_at | TIMESTAMP | NOT NULL | Follow timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- UNIQUE on (user_id, store_id)

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)
- `store_id` → `stores.id` (CASCADE DELETE)

---

## Categories & Classification

### `store_categories`
Store category definitions.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Category ID |
| name | VARCHAR(255) | NOT NULL | Category name |
| slug | VARCHAR(255) | NULLABLE | URL-friendly slug |
| sort_order | INT | NULLABLE | Display order |
| is_active | BOOLEAN | DEFAULT true | Active status |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

---

### `store_store_category`
Store-category pivot table.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| store_id | CHAR(36) | PRIMARY KEY (composite) | Foreign key to stores |
| store_category_id | BIGINT UNSIGNED | PRIMARY KEY (composite) | Foreign key to store_categories |

**Foreign Keys:**
- `store_id` → `stores.id` (CASCADE DELETE)
- `store_category_id` → `store_categories.id` (CASCADE DELETE)

---

### `categories`
General product/offer categories.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Category ID |
| name | VARCHAR(255) | NOT NULL | Category name |
| slug | VARCHAR(255) | UNIQUE | URL-friendly slug |
| description | TEXT | NULLABLE | Category description |
| parent_id | BIGINT | NULLABLE | Parent category ID (for hierarchy) |
| sort_order | INT | DEFAULT 0 | Display order |
| is_active | BOOLEAN | DEFAULT true | Active status |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Foreign Keys:**
- `parent_id` → `categories.id` (CASCADE DELETE)

---

## Location & Addresses

### `addresses`
Physical address storage.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Address ID |
| first_name | VARCHAR(100) | NULLABLE | Recipient first name |
| last_name | VARCHAR(100) | NULLABLE | Recipient last name |
| company | VARCHAR(255) | NULLABLE | Company name |
| address_line1 | VARCHAR(255) | NOT NULL | Primary address line |
| address_line2 | VARCHAR(255) | NULLABLE | Secondary address line |
| city | VARCHAR(100) | NOT NULL | City name |
| state_province | VARCHAR(100) | NULLABLE | State/province |
| postal_code | VARCHAR(20) | NULLABLE | Postal/ZIP code |
| country_code | CHAR(2) | NULLABLE | ISO country code |
| phone_number | VARCHAR(20) | NULLABLE | Contact phone |
| latitude | DECIMAL(10,8) | NULLABLE | GPS latitude |
| longitude | DECIMAL(11,8) | NULLABLE | GPS longitude |
| delivery_instructions | TEXT | NULLABLE | Delivery notes |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- `idx_lat_lng` on (latitude, longitude)

---

### `addressables`
Polymorphic address relationships.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Relationship ID |
| address_id | BIGINT | NOT NULL | Foreign key to addresses |
| owner_type | VARCHAR(255) | NOT NULL | Polymorphic type (User, Store) |
| owner_id | CHAR(36) | NOT NULL | Polymorphic ID |
| label | VARCHAR(50) | DEFAULT 'home' | Address label (home, work, branch) |
| is_default_shipping | BOOLEAN | DEFAULT false | Default shipping address |
| is_default_billing | BOOLEAN | DEFAULT false | Default billing address |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- `idx_address` on (address_id)
- UNIQUE on (owner_type, owner_id, address_id)

**Foreign Keys:**
- `address_id` → `addresses.id` (CASCADE DELETE)

---

## Notifications & Communication

### `notifications`
User notification management.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Notification ID |
| user_id | CHAR(36) | INDEXED, NOT NULL | Foreign key to users |
| type | VARCHAR(255) | NOT NULL | Notification type |
| title | VARCHAR(255) | NOT NULL | Notification title |
| message | TEXT | NOT NULL | Notification message |
| data | JSON | NULLABLE | Additional data |
| channel | VARCHAR(255) | NOT NULL | in_app, email, sms, push |
| status | ENUM | DEFAULT 'pending' | pending, sent, failed, read |
| reference_type | VARCHAR(50) | NULLABLE | Related entity type |
| reference_id | CHAR(36) | NULLABLE | Related entity ID |
| sent_at | TIMESTAMP | NULLABLE | Send timestamp |
| read_at | TIMESTAMP | NULLABLE | Read timestamp |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Indexes:**
- `idx_user_status` on (user_id, status)
- `idx_created` on (created_at)
- `idx_unread` on (user_id, read_at)

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

## Contact & Engagement

### `contact_us_customer`
Customer contact form submissions.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Submission ID |
| name | VARCHAR(255) | NOT NULL | Customer name |
| email | VARCHAR(255) | NOT NULL | Customer email |
| phone | VARCHAR(20) | NULLABLE | Customer phone |
| message | TEXT | NOT NULL | Message content |
| status | ENUM | DEFAULT 'pending' | pending, in_progress, resolved |
| resolved_at | TIMESTAMP | NULLABLE | Resolution timestamp |
| resolved_by | CHAR(36) | NULLABLE | Resolver user ID |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

---

### `contact_us_seller`
Seller inquiry form submissions.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Submission ID |
| name | VARCHAR(255) | NOT NULL | Seller name |
| email | VARCHAR(255) | NOT NULL | Seller email |
| phone | VARCHAR(20) | NULLABLE | Seller phone |
| company | VARCHAR(255) | NULLABLE | Company name |
| message | TEXT | NOT NULL | Message content |
| status | ENUM | DEFAULT 'pending' | pending, contacted, converted |
| contacted_at | TIMESTAMP | NULLABLE | Contact timestamp |
| contacted_by | CHAR(36) | NULLABLE | Contact user ID |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

---

### `notify_me`
Pre-launch notification signups.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Signup ID |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Subscriber email |
| notified_at | TIMESTAMP | NULLABLE | Notification sent timestamp |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

---

### `interests`
User interest tracking.

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Interest ID |
| user_id | CHAR(36) | NOT NULL | Foreign key to users |
| category | VARCHAR(255) | NOT NULL | Interest category |
| subcategory | VARCHAR(255) | NULLABLE | Interest subcategory |
| weight | INT | DEFAULT 1 | Interest weight/score |
| created_at | TIMESTAMP | NOT NULL | Record creation timestamp |
| updated_at | TIMESTAMP | NOT NULL | Record update timestamp |

**Foreign Keys:**
- `user_id` → `users.id` (CASCADE DELETE)

---

## System Tables

### `cache` & `cache_locks`
Laravel cache storage.

**`cache`**
| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| key | VARCHAR(255) | PRIMARY KEY | Cache key |
| value | MEDIUMTEXT | NOT NULL | Cached value |
| expiration | INT | NOT NULL | Expiration timestamp |

**`cache_locks`**
| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| key | VARCHAR(255) | PRIMARY KEY | Lock key |
| owner | VARCHAR(255) | NOT NULL | Lock owner |
| expiration | INT | NOT NULL | Lock expiration |

---

### `jobs` & `job_batches` & `failed_jobs`
Laravel queue system.

**`jobs`**
| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Job ID |
| queue | VARCHAR(255) | INDEXED, NOT NULL | Queue name |
| payload | LONGTEXT | NOT NULL | Job payload |
| attempts | TINYINT UNSIGNED | NOT NULL | Attempt count |
| reserved_at | INT UNSIGNED | NULLABLE | Reservation timestamp |
| available_at | INT UNSIGNED | NOT NULL | Availability timestamp |
| created_at | INT UNSIGNED | NOT NULL | Creation timestamp |

**`failed_jobs`**
| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Failed job ID |
| uuid | VARCHAR(255) | UNIQUE, NOT NULL | Job UUID |
| connection | TEXT | NOT NULL | Connection name |
| queue | TEXT | NOT NULL | Queue name |
| payload | LONGTEXT | NOT NULL | Job payload |
| exception | LONGTEXT | NOT NULL | Exception details |
| failed_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Failure timestamp |

---

### `telescope_entries`
Laravel Telescope monitoring (if enabled).

Stores application monitoring data including:
- Requests
- Commands
- Jobs
- Exceptions
- Queries
- Models
- Events
- Logs

---

## Relationships Summary

### User Relationships
- User → Profile (1:1)
- User → Points (1:1)
- User → Preferences (1:1)
- User → Sessions (1:Many)
- User → Stores (1:Many as owner)
- User → Addresses (Many:Many via addressables)
- User → Notifications (1:Many)
- User → Roles (Many:Many via model_has_roles)
- User → Store Followers (1:Many)

### Store Relationships
- Store → Owner (Many:1 to User)
- Store → Verifications (1:Many)
- Store → Hours (1:Many)
- Store → Categories (Many:Many via store_store_category)
- Store → Addresses (Many:Many via addressables)
- Store → Followers (1:Many)
- Store → User Roles (1:Many)

### Address Relationships
- Address → Users (Many:Many via addressables)
- Address → Stores (Many:Many via addressables)

---

## Enums Reference

### User Status
- `active` - Active user account
- `suspended` - Temporarily suspended
- `deleted` - Soft deleted account

### Store Status
- `pending` - Awaiting approval
- `active` - Approved and active
- `rejected` - Application rejected
- `suspended` - Temporarily suspended
- `closed` - Permanently closed

### Verification Status
- `pending` - Awaiting review
- `approved` - Verified and approved
- `rejected` - Verification failed

### Notification Status
- `pending` - Not yet sent
- `sent` - Successfully sent
- `failed` - Delivery failed
- `read` - Read by user

### OTP Status
- `pending` - Awaiting verification
- `verified` - Successfully verified
- `expired` - Time expired
- `blocked` - Too many attempts

---

## File Storage Paths

### User Files
- Avatars: `storage/app/public/avatars/{user_id}/`

### Store Files
- Logos: `storage/app/public/stores/{store_id}/logo/`
- Banners: `storage/app/public/stores/{store_id}/banner/`
- Verification Documents: `storage/app/public/stores/{store_id}/verifications/{document_type}/`

---

## Indexing Strategy

### Performance Indexes
- Foreign keys are automatically indexed
- Frequently queried columns (status, created_at) are indexed
- Composite indexes for common query patterns
- Unique indexes for data integrity

### Sharding Preparation
- `shard_key` columns in users and stores tables
- Prepared for horizontal scaling

---

## Security Considerations

1. **Password Storage**: Bcrypt hashing with salt
2. **Token Storage**: Hashed tokens in database
3. **OTP Security**: Hashed codes, attempt limiting, expiration
4. **Soft Deletes**: Preserve data integrity
5. **Foreign Key Constraints**: Maintain referential integrity
6. **Unique Constraints**: Prevent duplicate data

---

## Maintenance

### Regular Tasks
- Clean expired OTPs
- Clean expired sessions
- Archive old notifications
- Purge soft-deleted records (after retention period)
- Optimize tables and rebuild indexes

### Backup Strategy
- Daily full backups
- Hourly incremental backups
- Point-in-time recovery capability
- Offsite backup storage

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2026-03-06 | Initial schema documentation |

---

## Notes

- All timestamps use UTC timezone
- UUIDs are stored as CHAR(36) for compatibility
- JSON columns require MySQL 5.7.8+ or MariaDB 10.2.7+
- Soft deletes preserve data for audit trails
- Polymorphic relationships use Laravel conventions

---

**Last Updated:** March 6, 2026  
**Database Version:** MySQL 8.0 / MariaDB 10.6+  
**Laravel Version:** 11.x
