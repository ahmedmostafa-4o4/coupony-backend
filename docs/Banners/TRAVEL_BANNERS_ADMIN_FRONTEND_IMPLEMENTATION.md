# Travel Banners: Admin Frontend Implementation Guide

This document outlines the API integration points and workflows for the Admin dashboard to manage Travel Banners.

## API Base Details
- **Base Route:** `/api/v1/admin/travel-banners`
- **Auth:** Requires Bearer Token (Admin Role).
- **Format:** `multipart/form-data` required for Creation/Update (due to image uploads), `application/json` for fetching data.

---

## 1. Fetch Selectable Products (Filters)
Before creating a banner, the admin needs to select an active product/offer.

- **URL:** `/api/v1/admin/travel-banners/selectable-products`
- **Method:** `GET`
- **Query Parameters:**
  - `category_id` (string/uuid, optional) - Filter by category.
  - `min_review_score` (numeric, optional, 0-5) - Filter by minimum rating.
  - `sort_by` (string, optional) - Values: `most_likes`, `most_saves`, `price_asc`, `price_desc`, `newest`.
  - `min_price` (numeric, optional)
  - `max_price` (numeric, optional)
  - `search` (string, optional) - Keyword search.
  - `per_page` (integer, optional) - Defaults to 15.

**Workflow:**
1. Display a "Select Product" modal or dropdown.
2. Provide filter inputs (Category dropdown, Price Range, Sort By, Search bar).
3. Use this endpoint to fetch and populate the selectable list.
4. When clicked, store the `product.id` to be used in the banner creation form.

---

## 2. List Travel Banners
- **URL:** `/api/v1/admin/travel-banners`
- **Method:** `GET`

Returns paginated travel banners, including the linked `product` details. Used to display the main data table.

---

## 3. Create Travel Banner
- **URL:** `/api/v1/admin/travel-banners`
- **Method:** `POST`
- **Content-Type:** `multipart/form-data`

**Form Fields:**
- `product_id` (required, uuid) - Selected from step 1.
- `image` (required, file, max 5MB) - The banner image.
- `cta_text` (required, string) - E.g., "Book Now".
- `save_percent` (required, string) - E.g., "20% OFF" or "Save 50$".
- `priority` (optional, integer) - Sorting order (default 0).
- `start_date` (optional, date/datetime) - When the banner should start showing.
- `end_date` (optional, date/datetime) - When the banner should stop showing.
- `is_active` (optional, boolean) - `1` or `0`. Defaults to `1`.

---

## 4. Update Travel Banner
- **URL:** `/api/v1/admin/travel-banners/{travelBannerId}`
- **Method:** `POST` with `_method=PATCH` (Laravel workaround for multipart form data)
- **Content-Type:** `multipart/form-data`

**Form Fields:**
- `_method` (required, string) - Value must be `PATCH`.
- `product_id` (optional, uuid)
- `image` (optional, file, max 5MB) - Send only if updating the image.
- `cta_text` (optional, string)
- `save_percent` (optional, string)
- `priority` (optional, integer)
- `start_date` (optional, date/datetime) - When the banner should start showing.
- `end_date` (optional, date/datetime) - When the banner should stop showing.
- `is_active` (optional, boolean)

---

## 5. Delete Travel Banner
- **URL:** `/api/v1/admin/travel-banners/{travelBannerId}`
- **Method:** `DELETE`

Triggers automatic deletion of the associated image file from storage and removes the database record.
