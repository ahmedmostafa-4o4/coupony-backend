# Customer Home Page — Backend Requirements for Flutter Integration

**Document purpose:**
This document describes exactly what the backend must implement so that the Flutter customer home page works with real data.
The Flutter UI is fully built and waiting. Once the backend implements everything here, the Flutter developer will connect without any further discussion.

---

## Context: What the Flutter Page Shows

The customer home page is the main landing page for the customer. It is a vertically scrolling page with multiple sections.
The page has TWO feed modes toggled by the user:
- **Tab 0 — "كل العروض" (All Offers):** Shows categorized offer sections, banners, featured offers, travel, etc.
- **Tab 1 — "المتابَعون" (Following):** Instagram-style infinite scroll feed showing offers from stores the user follows.

**CRITICAL BUSINESS LOGIC — Following Feed Fallback:**
- If the user does NOT follow any stores → the feed must NOT be empty. Show offers from stores close to the user's interests (based on categories they browse, offers they save, etc.).
- If the user follows stores but ALL followed stores have expired offers → show new offers from similar/related stores the user doesn't follow yet.
- The feed must ALWAYS have content. Infinite scroll must never hit a dead end. When real followed-store offers run out, seamlessly transition to recommended/discovery offers.

---

## Page Sections (Top to Bottom)

1. **Header** — User name, avatar, location, notification bell, search bar
2. **Categories** — Horizontal scrollable category icons (already has its own endpoint via CategoriesCubit)
3. **Countdown Timer** — Live countdown to the nearest active promo end time
4. **Banner Carousel** — Promotional banners (PageView with dots)
5. **Info Video Section** — Static (no backend needed)
6. **Feed Toggle** — "All Offers" / "Following" tabs
7. **Personalized Offers Row** — Horizontal scroll of offers tailored to user
8. **Stores Row** — Horizontal scroll of store circles (repeated between sections)
9. **Featured Offers Row** — Large horizontal offer cards
10. **Travel Destinations Section** — Destination cards + travel offers + filter chips
11. **Egypt Offers Row** — Offers specific to Egypt
12. **Favorites Row** — User's saved/bookmarked offers
13. **Product Category Sections** — Electronics, Phones, Beauty (each with offers + stores)
14. **Following Feed** — Infinite scroll of post cards from followed stores


---

## Endpoints Required

### 1.1 — Get Customer Home Data (Main Endpoint)

```http
GET /api/v1/customer/home
```

**Query parameters:**

| Parameter | Type | Required | Description |
|---|---|---|---|
| `latitude` | `float` | No | User's current latitude (for location-based recommendations) |
| `longitude` | `float` | No | User's current longitude |

**Headers:**

```http
Authorization: Bearer <ACCESS_TOKEN>
Accept: application/json
Accept-Language: ar
```

**`Accept-Language` values:**

| Value | Meaning |
|---|---|
| `ar` | Arabic — return Arabic text fields |
| `en` | English — return English text fields |

If omitted, default to `ar`.

- Authenticated users get personalized content (followed stores, favorites, recommendations).
- Guest users (no token) get generic popular content. Return `200` with generic data, NOT `401`.

**Localization:**
- ALL text fields in the response must be returned in the language specified by `Accept-Language` header.
- The backend stores both Arabic and English versions of all text content.
- Flutter sends `Accept-Language: ar` or `Accept-Language: en` based on the app's current locale.
- If a translation is missing, fall back to Arabic.

---

### 1.2 — Get Following Feed (Infinite Scroll)

```http
GET /api/v1/customer/home/following-feed?page={page}&per_page={per_page}
```

**Query parameters:**

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `page` | `integer` | No | `1` | Page number for pagination |
| `per_page` | `integer` | No | `10` | Items per page |
| `latitude` | `float` | No | — | For fallback recommendations |
| `longitude` | `float` | No | — | For fallback recommendations |

**Authentication:** Required (Bearer token). Returns `401` for unauthenticated users.

**CRITICAL FALLBACK LOGIC (must be implemented server-side):**

1. First, return offers from stores the user follows that have ACTIVE (non-expired) offers.
2. If followed stores have no active offers OR user follows no stores → return offers from stores matching user's interest categories.
3. If still not enough content → return trending/popular offers in the user's area.
4. NEVER return an empty `items` array unless there are literally zero offers in the entire system.
5. Mark each item with `source_type` so Flutter knows the context (see response shape below).

---

### 1.3 — Toggle Offer Favorite (Save/Unsave)

```http
POST /api/v1/customer/offers/{offer_id}/toggle-favorite
```

**Response:**

```json
{
  "data": {
    "offer_id": "uuid",
    "is_favorite": true
  }
}
```

---

### 1.4 — Toggle Offer Like

```http
POST /api/v1/customer/offers/{offer_id}/toggle-like
```

**Response:**

```json
{
  "data": {
    "offer_id": "uuid",
    "is_liked": true,
    "likes_count": 1843
  }
}
```


---

### 1.5 — Get Promo/Banner Details

```http
GET /api/v1/customer/promos/{promo_id}
```

**Authentication:** Optional. Authenticated users get `is_liked` and `is_saved` status.

---

### 1.6 — Get Active Banners

```http
GET /api/v1/customer/banners
```

**Authentication:** Optional.

---

### 1.7 — Get Info Videos (YouTube Tutorial Links)

```http
GET /api/v1/customer/info-videos
```

**Authentication:** Optional.

**Description:** Returns the YouTube video URLs for the "ابدأ مع كوبوني" (Start with Coupony) section. This section has two buttons: "كمستخدم" (As Customer) and "تاجر" (As Merchant). Each button opens a YouTube video explaining how to use the app.

**Response:**

```json
{
  "data": {
    "section_title": "ابدأ مع كوبوني",
    "section_subtitle": "شوف ازاي تقدر تستفيد من كوبوني في دقايق",
    "background_image_url": "https://cdn.coupony.shop/static/info_video_bg.png",
    "videos": [
      {
        "id": "customer_video",
        "label": "كمستخدم",
        "is_primary": true,
        "youtube_url": "https://www.youtube.com/watch?v=UIqAesBEZy8",
        "sheet_title": "ابدأ كمستخدم",
        "sheet_subtitle": "اكتشف طريقة استخدام كوبوني والعثور على العروض"
      },
      {
        "id": "merchant_video",
        "label": "تاجر",
        "is_primary": false,
        "youtube_url": "https://www.youtube.com/watch?v=hGp59-z5mso",
        "sheet_title": "ابدأ كتاجر",
        "sheet_subtitle": "اعرف كيف تعرض كوبوناتك وتدير متجرك"
      }
    ]
  }
}
```

**Example response when `Accept-Language: en`:**

```json
{
  "data": {
    "section_title": "Start with Coupony",
    "section_subtitle": "See how you can benefit from Coupony in minutes",
    "background_image_url": "https://cdn.coupony.shop/static/info_video_bg.png",
    "videos": [
      {
        "id": "customer_video",
        "label": "As Customer",
        "is_primary": true,
        "youtube_url": "https://www.youtube.com/watch?v=UIqAesBEZy8",
        "sheet_title": "Start as Customer",
        "sheet_subtitle": "Discover how to use Coupony and find the best deals"
      },
      {
        "id": "merchant_video",
        "label": "Merchant",
        "is_primary": false,
        "youtube_url": "https://www.youtube.com/watch?v=hGp59-z5mso",
        "sheet_title": "Start as Merchant",
        "sheet_subtitle": "Learn how to showcase your coupons and manage your store"
      }
    ]
  }
}
```

**Field specification:**

| Field | Type | Description |
|---|---|---|
| `section_title` | `string` | Main title displayed above the buttons |
| `section_subtitle` | `string` | Subtitle/description below the title |
| `background_image_url` | `string \| null` | Background image URL for the section. If `null`, Flutter uses local asset. |
| `videos` | `array` | Array of video objects (currently 2: customer + merchant) |
| `videos[].id` | `string` | Unique identifier for the video |
| `videos[].label` | `string` | Button label text (e.g. "كمستخدم", "تاجر") |
| `videos[].is_primary` | `boolean` | If `true`, button is filled (orange). If `false`, button is outlined (white). |
| `videos[].youtube_url` | `string` | Full YouTube video URL |
| `videos[].sheet_title` | `string` | Title shown in the video bottom sheet |
| `videos[].sheet_subtitle` | `string` | Subtitle shown in the video bottom sheet |

**Rules:**
- Always return at least the 2 default videos (customer + merchant).
- Admin can update URLs without app release.
- Admin can add more videos in the future (Flutter will render them dynamically).
- Order matters: Flutter renders buttons left-to-right in the order returned.
- If the endpoint fails, Flutter falls back to hardcoded URLs (graceful degradation).
- All text fields (`section_title`, `section_subtitle`, `label`, `sheet_title`, `sheet_subtitle`) are returned in the language matching `Accept-Language` header.
- `youtube_url` may differ per language (e.g. Arabic video vs English video).

---

## Localization — Global Rule for ALL Endpoints

**CRITICAL:** Every text field returned by the backend must respect the `Accept-Language` header.

Flutter sends:
- `Accept-Language: ar` → Return Arabic text
- `Accept-Language: en` → Return English text

**How to store in database:**
- Every text field should have two columns: `field_ar` and `field_en`.
- The API layer reads `Accept-Language` and returns the appropriate column as `field`.
- Example: `title_ar = "تخفيضات الجاكيت الشتوي"`, `title_en = "Winter Jacket Sale"` → API returns `"title": "تخفيضات الجاكيت الشتوي"` for `ar` and `"title": "Winter Jacket Sale"` for `en`.

**Fields that MUST be localized:**

| Object | Localized Fields |
|---|---|
| Banner | `discount_label`, `min_transaction`, `date_range`, `cta_label` |
| Offer | `title`, `category_ar`→`category_label`, `store_name` |
| Featured Offer | `title`, `store_name` |
| Store | `name` |
| Category Section | `title_ar`/`title_en` → return as `title` |
| Promo Details | `title`, `store_name`, `promo_items[].title`, `promo_items[].description`, `terms[]`, `branches[].name`, `branches[].address`, `branches[].working_hours`, `merchant_info.store_name`, `merchant_info.description`, `merchant_info.address`, `merchant_info.working_hours`, `merchant_info.categories[]` |
| Info Videos | `section_title`, `section_subtitle`, `videos[].label`, `videos[].sheet_title`, `videos[].sheet_subtitle` |
| Following Feed | `offer.title`, `offer.category_ar`→`category_label`, `store.name` |

**Fields that are NOT localized (same in all languages):**
- `id`, `image_url`, `youtube_url`, all prices, percentages, counts, booleans, coordinates, dates, `background_color`

**Fallback:** If English translation is missing → return Arabic. Never return `null` for a text field.

---

## Full Response Shape for `GET /api/v1/customer/home`

```json
{
  "data": {
    "user": {
      "name": "أحمد محمد",
      "avatar_url": "https://cdn.coupony.shop/users/uuid/avatar.jpg",
      "location": "مدينة نصر، القاهرة"
    },

    "promo_end_time": "2026-06-29T23:59:59+02:00",

    "info_videos": {
      "section_title": "ابدأ مع كوبوني",
      "section_subtitle": "شوف ازاي تقدر تستفيد من كوبوني في دقايق",
      "background_image_url": "https://cdn.coupony.shop/static/info_video_bg.png",
      "videos": [
        {
          "id": "customer_video",
          "label": "كمستخدم",
          "is_primary": true,
          "youtube_url": "https://www.youtube.com/watch?v=UIqAesBEZy8",
          "sheet_title": "ابدأ كمستخدم",
          "sheet_subtitle": "اكتشف طريقة استخدام كوبوني والعثور على العروض"
        },
        {
          "id": "merchant_video",
          "label": "تاجر",
          "is_primary": false,
          "youtube_url": "https://www.youtube.com/watch?v=hGp59-z5mso",
          "sheet_title": "ابدأ كتاجر",
          "sheet_subtitle": "اعرف كيف تعرض كوبوناتك وتدير متجرك"
        }
      ]
    },

    "banners": [
      {
        "id": "uuid",
        "image_url": "https://cdn.coupony.shop/banners/uuid/image.jpg",
        "discount_label": "25%",
        "min_transaction": "500 ج.م",
        "date_range": "25 - 29 يونيو 2026",
        "cta_label": "تسوق الآن",
        "end_time": "2026-06-29T23:59:59+02:00"
      }
    ],

    "personalized_offers": [
      {
        "id": "uuid",
        "image_url": "https://cdn.coupony.shop/offers/uuid/image.jpg",
        "title": "تخفيضات الجاكيت الشتوي",
        "original_price": 350.0,
        "discounted_price": 210.0,
        "save_percent": 40,
        "is_favorite": false,
        "category_slug": "fashion",
        "category_label": "أزياء",
        "store_name": "Zara",
        "store_id": "uuid"
      }
    ],

    "featured_offers": [
      {
        "id": "uuid",
        "title": "تخفيضات كبيرة على المعاطف",
        "store_name": "محل Jolie-الفيوم",
        "store_id": "uuid",
        "original_price": 450.0,
        "discounted_price": 350.0,
        "discount_percent": 40,
        "image_url": "https://cdn.coupony.shop/offers/uuid/image.jpg",
        "is_favorite": false
      }
    ],

    "travel_offers": [
      {
        "id": "uuid",
        "image_url": "https://cdn.coupony.shop/offers/uuid/image.jpg",
        "title": "إقامة فندقية 5 نجوم في دبي",
        "original_price": 1999.0,
        "discounted_price": 1399.0,
        "save_percent": 30,
        "is_favorite": false,
        "category_slug": "travel",
        "category_label": "سفر",
        "store_name": "Booking.com",
        "store_id": "uuid",
        "rating_avg": 4.8,
        "rating_count": 234,
        "location_label": "دبي، الإمارات"
      }
    ],

    "egypt_offers": [
      {
        "id": "uuid",
        "image_url": "https://cdn.coupony.shop/offers/uuid/image.jpg",
        "title": "سماعات JBL بخصم رائع",
        "original_price": 1999.0,
        "discounted_price": 1399.0,
        "save_percent": 30,
        "is_favorite": false,
        "category_slug": "electronics",
        "category_label": "إلكترونيات",
        "store_name": "Virgin Megastore",
        "store_id": "uuid"
      }
    ],

    "favorites": [
      {
        "id": "uuid",
        "image_url": "https://cdn.coupony.shop/offers/uuid/image.jpg",
        "title": "نايكي إير ماكس المحدودة",
        "original_price": 1200.0,
        "discounted_price": 840.0,
        "save_percent": 30,
        "is_favorite": true,
        "category": "fashion",
        "category_ar": "أزياء",
        "store_name": "Nike",
        "store_id": "uuid"
      }
    ],

    "stores": [
      {
        "id": "uuid",
        "name": "B-you",
        "image_url": "https://cdn.coupony.shop/stores/uuid/logo.jpg"
      }
    ],

    "category_sections": [
      {
        "section_id": "electronics",
        "title_ar": "الأجهزة الكهربائية",
        "title_en": "Electronics",
        "background_color": "#EBF5FB",
        "offers": [],
        "stores": []
      },
      {
        "section_id": "phones",
        "title_ar": "الهواتف والأجهزة الذكية",
        "title_en": "Phones & Smart Devices",
        "background_color": "#E8F8F5",
        "offers": [],
        "stores": []
      },
      {
        "section_id": "beauty",
        "title_ar": "مستحضرات التجميل والاكسسوارات",
        "title_en": "Beauty & Accessories",
        "background_color": "#FEF0F6",
        "offers": [],
        "stores": []
      }
    ]
  }
}
```


---

## Full Response Shape for `GET /api/v1/customer/home/following-feed`

```json
{
  "data": {
    "items": [
      {
        "source_type": "followed",
        "store": {
          "id": "uuid",
          "name": "ماكدونالدز مصر",
          "image_url": "https://cdn.coupony.shop/stores/uuid/logo.jpg",
          "is_followed": true
        },
        "offer": {
          "id": "uuid",
          "image_url": "https://cdn.coupony.shop/offers/uuid/image.jpg",
          "title": "وجبة العائلة الكبيرة",
          "original_price": 350.0,
          "discounted_price": 229.0,
          "save_percent": 35,
          "category": "restaurants",
          "category_ar": "مطاعم",
          "store_name": "ماكدونالدز مصر",
          "is_liked": false,
          "likes_count": 127,
          "comments_count": 34,
          "is_saved": false,
          "created_at": "2026-05-20T14:30:00+02:00"
        }
      },
      {
        "source_type": "recommended",
        "recommendation_reason": "based_on_interests",
        "store": {
          "id": "uuid",
          "name": "مطعم جديد",
          "image_url": "https://cdn.coupony.shop/stores/uuid/logo.jpg",
          "is_followed": false
        },
        "offer": {
          "id": "uuid",
          "image_url": "https://cdn.coupony.shop/offers/uuid/image.jpg",
          "title": "عرض خاص للمستخدمين الجدد",
          "original_price": 200.0,
          "discounted_price": 120.0,
          "save_percent": 40,
          "category": "restaurants",
          "category_ar": "مطاعم",
          "store_name": "مطعم جديد",
          "is_liked": false,
          "likes_count": 45,
          "comments_count": 8,
          "is_saved": false,
          "created_at": "2026-05-21T10:00:00+02:00"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total_items": 156,
      "total_pages": 16,
      "has_next_page": true
    }
  }
}
```

**`source_type` values:**

| Value | Meaning |
|---|---|
| `followed` | Offer is from a store the user actively follows |
| `recommended` | Offer is a recommendation (user doesn't follow this store) |
| `trending` | Offer is trending/popular in the area |

**`recommendation_reason` values (only when `source_type` is `recommended`):**

| Value | Meaning |
|---|---|
| `based_on_interests` | Matches user's browsing/saving patterns |
| `similar_to_followed` | Store is similar to ones user follows |
| `popular_nearby` | Popular in user's geographic area |
| `new_store` | New store that might interest the user |


---

## Full Response Shape for `GET /api/v1/customer/promos/{promo_id}`

```json
{
  "data": {
    "id": "uuid",
    "title": "عرض كومبو مميز بخصم 25% على منتجات مختارة",
    "store_name": "كوبوني ماركت",
    "store_id": "uuid",
    "discount_label": "25%",
    "likes_count": 1842,
    "is_liked": false,
    "is_saved": false,
    "end_date": "31-05-2026",
    "minimum_transaction": "500 ج.م",
    "available_branches_count": 12,

    "image_urls": [
      "https://cdn.coupony.shop/promos/uuid/img1.jpg",
      "https://cdn.coupony.shop/promos/uuid/img2.jpg",
      "https://cdn.coupony.shop/promos/uuid/img3.jpg"
    ],

    "promo_items": [
      {
        "id": "uuid",
        "title": "كوبون كومبو عائلي",
        "description": "يشمل الوجبة الرئيسية مع مشروب وسناك جانبي. مناسب للمشاركة ويطبق داخل الفروع المشاركة.",
        "original_price": 420,
        "discounted_price": 315,
        "save_percent": 25,
        "end_date": "31-05-2026"
      },
      {
        "id": "uuid",
        "title": "كوبون وجبة فردية مميزة",
        "description": "اختيار منتج رئيسي واحد مع مشروب وخصم إضافي عند استخدام البرومو من التطبيق.",
        "original_price": 220,
        "discounted_price": 165,
        "save_percent": 25,
        "end_date": "31-05-2026"
      }
    ],

    "terms": [
      "لا يمكن دمج البرومو مع عروض أخرى في نفس الطلب.",
      "العرض متاح داخل الفروع المشاركة فقط.",
      "قد تختلف بعض المنتجات حسب توفرها في الفرع.",
      "يجب إظهار البرومو قبل إتمام عملية الدفع."
    ],

    "branches": [
      {
        "id": "uuid",
        "name": "فرع مدينة نصر - شارع مصطفى النحاس",
        "address": "شارع مصطفى النحاس، مدينة نصر، القاهرة",
        "latitude": 30.0511,
        "longitude": 31.3656,
        "working_hours": "10:00 ص - 11:00 م"
      },
      {
        "id": "uuid",
        "name": "فرع التجمع الخامس - شارع التسعين",
        "address": "شارع التسعين، التجمع الخامس، القاهرة",
        "latitude": 30.0074,
        "longitude": 31.4913,
        "working_hours": "10:00 ص - 12:00 ص"
      }
    ],

    "merchant_info": {
      "store_id": "uuid",
      "store_name": "كوبوني ماركت",
      "description": "متجر يقدم عروضاً مختارة بعناية لمستخدمي Coupony، مع تحديثات دورية على المنتجات والفروع المشاركة.",
      "is_verified": true,
      "phone_number": "01008864664",
      "email": "info@couponymarket.com",
      "logo_url": "https://cdn.coupony.shop/stores/uuid/logo.jpg",
      "banner_url": "https://cdn.coupony.shop/stores/uuid/banner.jpg",
      "rating_avg": 4.7,
      "rating_count": 128,
      "followers_count": 12500,
      "coupons_count": 18,
      "address": "مدينة نصر، القاهرة",
      "working_hours": "10:00 ص - 11:00 م",
      "categories": ["مطاعم", "عروض يومية"],
      "social_links": {
        "facebook": "https://facebook.com/couponymarket",
        "instagram": "https://instagram.com/couponymarket",
        "website": "https://couponymarket.com"
      }
    }
  }
}
```


---

## Full Response Shape for `GET /api/v1/customer/banners`

```json
{
  "data": [
    {
      "id": "uuid",
      "image_url": "https://cdn.coupony.shop/banners/uuid/image.jpg",
      "discount_label": "25%",
      "min_transaction": "500 ج.م",
      "date_range": "25 - 29 يونيو 2026",
      "cta_label": "تسوق الآن",
      "end_time": "2026-06-29T23:59:59+02:00",
      "promo_id": "uuid",
      "deep_link": "/promos/uuid"
    }
  ]
}
```

---

## Field-by-Field Specification

### `user` object (in home response)

| Field | Type | Description |
|---|---|---|
| `name` | `string` | User's full name. Empty string for guests. |
| `avatar_url` | `string \| null` | User's profile picture URL |
| `location` | `string` | User's default delivery address as display string. Empty for guests. |

**Notes:**
- For guest users (no auth token), return `name: ""`, `avatar_url: null`, `location: ""`.
- Flutter handles the guest UI display separately.

---

### `promo_end_time` field

| Field | Type | Description |
|---|---|---|
| `promo_end_time` | `ISO 8601 datetime` | The end time of the nearest active global promo. Used for the countdown timer. |

**Rules:**
- Return the soonest `end_time` among all currently active banners/promos.
- If no active promo exists, return `null`. Flutter will hide the countdown.

---

### `banners` array

Each banner drives one card in the carousel.

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Banner unique ID |
| `image_url` | `string` | Banner image URL (recommended: 600×400 or 3:2 ratio) |
| `discount_label` | `string` | Display text for discount (e.g. "25%", "BOGO") |
| `min_transaction` | `string` | Minimum purchase text (e.g. "500 ج.م") |
| `date_range` | `string` | Human-readable date range (e.g. "25 - 29 يونيو 2026") |
| `cta_label` | `string` | Button text (e.g. "تسوق الآن") |
| `end_time` | `ISO 8601 datetime` | When this banner/promo expires. Used for per-banner countdown. |

**Rules:**
- Return only ACTIVE banners (not expired).
- Order by priority/weight (admin-configurable).
- Maximum 10 banners.


---

### Offer object (shared shape for all offer arrays)

Used in: `personalized_offers`, `egypt_offers`, `travel_offers`, `favorites`, and inside `category_sections[].offers`.

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Offer unique ID |
| `image_url` | `string` | Offer image URL (recommended: 400×400 or 1:1 ratio) |
| `title` | `string` | Offer title in Arabic |
| `original_price` | `float` | Original price before discount |
| `discounted_price` | `float` | Price after discount |
| `save_percent` | `integer` | Discount percentage (0-100) |
| `is_favorite` | `boolean` | Whether the current user has saved this offer. Always `false` for guests. |
| `category` | `string` | Category slug (e.g. "fashion", "electronics", "travel", "restaurants", "beauty") |
| `category_ar` | `string` | Arabic category name for display |
| `store_name` | `string` | Store display name |
| `store_id` | `string (uuid)` | Store ID for navigation |

**Additional fields for travel offers only:**

| Field | Type | Description |
|---|---|---|
| `rating_avg` | `float` | Average rating (0.0 - 5.0) |
| `rating_count` | `integer` | Number of ratings |
| `location_label` | `string` | Location display text (e.g. "دبي، الإمارات") |

---

### Featured offer object

Used in: `featured_offers` array.

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Offer unique ID |
| `title` | `string` | Offer title |
| `store_name` | `string` | Store display name |
| `store_id` | `string (uuid)` | Store ID |
| `original_price` | `float` | Original price |
| `discounted_price` | `float` | Discounted price |
| `discount_percent` | `integer` | Discount percentage |
| `image_url` | `string \| null` | Offer image URL (recommended: 400×600 or 2:3 ratio for larger cards) |
| `is_favorite` | `boolean` | Whether saved by current user |

---

### Store object

Used in: `stores` array and inside `category_sections[].stores`.

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Store unique ID |
| `name` | `string` | Store display name |
| `image_url` | `string \| null` | Store logo URL (recommended: 200×200, circular display) |

---

### `category_sections` array

Each item represents a themed product category section on the home page.

| Field | Type | Description |
|---|---|---|
| `section_id` | `string` | Unique section identifier (e.g. "electronics", "phones", "beauty") |
| `title_ar` | `string` | Arabic section title |
| `title_en` | `string` | English section title |
| `background_color` | `string` | Hex color for section background (e.g. "#EBF5FB") |
| `offers` | `array` | Array of offer objects (same shape as above) |
| `stores` | `array` | Array of store objects (same shape as above) |

**Rules:**
- Return 3-6 category sections.
- Each section should have 4-8 offers and 3-5 stores.
- Sections are ordered by admin priority.
- If a section has zero active offers, omit it entirely.


---

## Following Feed — Detailed Specification

### Feed Item object

| Field | Type | Description |
|---|---|---|
| `source_type` | `string` | One of: `followed`, `recommended`, `trending` |
| `recommendation_reason` | `string \| null` | Only present when `source_type` is not `followed` |
| `store` | `object` | Store info (see below) |
| `offer` | `object` | Offer info with social metrics (see below) |

### Feed Store object

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Store ID |
| `name` | `string` | Store display name |
| `image_url` | `string \| null` | Store logo URL |
| `is_followed` | `boolean` | Whether the current user follows this store |

### Feed Offer object

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Offer ID |
| `image_url` | `string` | Offer image (recommended: 600×450 or 4:3 ratio for feed cards) |
| `title` | `string` | Offer title |
| `original_price` | `float` | Original price |
| `discounted_price` | `float` | Discounted price |
| `save_percent` | `integer` | Discount percentage |
| `category` | `string` | Category slug |
| `category_ar` | `string` | Arabic category name |
| `store_name` | `string` | Store name (duplicated for convenience) |
| `is_liked` | `boolean` | Whether current user liked this offer |
| `likes_count` | `integer` | Total likes count |
| `comments_count` | `integer` | Total comments count |
| `is_saved` | `boolean` | Whether current user saved/bookmarked this offer |
| `created_at` | `ISO 8601 datetime` | When the offer was published |

### Pagination object

| Field | Type | Description |
|---|---|---|
| `current_page` | `integer` | Current page number |
| `per_page` | `integer` | Items per page |
| `total_items` | `integer` | Total available items |
| `total_pages` | `integer` | Total pages |
| `has_next_page` | `boolean` | Whether more pages exist |

---

## Following Feed — Fallback Algorithm (CRITICAL)

The backend MUST implement this fallback logic to ensure the feed is NEVER empty:

```
ALGORITHM: Get Following Feed Items

INPUT: user_id, page, per_page, latitude, longitude

1. Get list of store_ids the user follows
2. Get active offers from those stores, ordered by created_at DESC
3. IF enough items to fill the page:
     → Return items with source_type = "followed"
4. ELSE (not enough followed-store offers):
     a. Get user's interest categories (from: browsing history, saved offers, followed store categories)
     b. Get active offers from NON-followed stores in those categories
     c. Mark these with source_type = "recommended", recommendation_reason = "based_on_interests"
     d. Mix them in after the followed items
5. IF STILL not enough:
     a. Get trending offers (most liked/saved in last 7 days) in user's area
     b. Mark with source_type = "trending"
6. NEVER return empty items array (unless zero offers exist system-wide)
7. Shuffle recommended/trending items slightly to avoid repetitive patterns
```

**Ordering within the feed:**
- `followed` items come first, ordered by `created_at` DESC (newest first)
- `recommended` items are interleaved after followed items
- `trending` items fill remaining slots
- On subsequent pages, continue the same priority order


---

## Promo Details — Detailed Specification

### Promo Item object

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Promo item/coupon ID |
| `title` | `string` | Item title (e.g. "كوبون كومبو عائلي") |
| `description` | `string` | Item description (2-3 lines) |
| `original_price` | `integer` | Original price |
| `discounted_price` | `integer` | Discounted price |
| `save_percent` | `integer` | Discount percentage |
| `end_date` | `string` | Expiry date (format: "DD-MM-YYYY") |

### Branch object

| Field | Type | Description |
|---|---|---|
| `id` | `string (uuid)` | Branch ID |
| `name` | `string` | Branch display name (e.g. "فرع مدينة نصر - شارع مصطفى النحاس") |
| `address` | `string` | Full address text |
| `latitude` | `float` | Branch latitude |
| `longitude` | `float` | Branch longitude |
| `working_hours` | `string` | Display text for working hours (e.g. "10:00 ص - 11:00 م") |

### Merchant Info object

| Field | Type | Description |
|---|---|---|
| `store_id` | `string (uuid)` | Store ID for navigation to full store page |
| `store_name` | `string` | Store display name (localized) |
| `description` | `string` | Store bio/description (localized) |
| `is_verified` | `boolean` | Whether the merchant is verified by Coupony |
| `phone_number` | `string` | Contact phone number (e.g. "01008864664") |
| `email` | `string \| null` | Store contact email |
| `logo_url` | `string \| null` | Store logo URL (circular display) |
| `banner_url` | `string \| null` | Store banner/cover image URL |
| `rating_avg` | `float` | Average store rating (0.0 - 5.0) |
| `rating_count` | `integer` | Total number of ratings |
| `followers_count` | `integer` | Total followers count |
| `coupons_count` | `integer` | Number of active coupons/offers |
| `address` | `string` | Store main address (localized) |
| `working_hours` | `string` | Display text for working hours (localized, e.g. "10:00 ص - 11:00 م") |
| `categories` | `array of string` | Store categories (localized labels) |
| `social_links` | `object \| null` | Social media links (see below) |

### Social Links object (inside merchant_info)

| Field | Type | Description |
|---|---|---|
| `facebook` | `string \| null` | Facebook page URL |
| `instagram` | `string \| null` | Instagram profile URL |
| `website` | `string \| null` | Store website URL |
| `twitter` | `string \| null` | Twitter/X profile URL |
| `tiktok` | `string \| null` | TikTok profile URL |

**Notes:**
- Only include social links that the store has configured. Omit `null` fields or return `null`.
- `phone_number` is displayed directly in the "التاجر" tab — it must always be present.
- `is_verified` shows a verification badge in the UI.
- `working_hours` is a formatted string — the backend formats it based on the store's hours configuration and the `Accept-Language` header.

---

## Personalization Logic — How to Build Each Section

### `personalized_offers`
- Based on user's browsing history, saved offers, and followed store categories.
- If new user with no history → show popular offers across all categories.
- Return 4-8 offers.
- Rotate content on each request (don't show the same 4 offers every time).

### `featured_offers`
- Admin-curated or algorithmically selected "best deals" (highest discount %, most popular, or promoted by sellers).
- Return 3-6 offers.
- These are displayed in larger cards (200w × 280h) so image quality matters.

### `travel_offers`
- All offers in the "travel" category.
- Include `rating_avg`, `rating_count`, and `location_label` for travel cards.
- Return 3-6 offers.
- Support filtering by: "خارج مصر" (international), "داخل مصر" (domestic), "جميع الرحلات" (all).
- The filter is currently client-side but should be supported server-side for future optimization.

### `egypt_offers`
- Offers from Egyptian stores or offers available in Egypt.
- Return 3-6 offers.

### `favorites`
- Offers the user has saved/bookmarked.
- Return the most recent 4-8 saved offers.
- For guests: return empty array `[]`.

### `stores`
- Mix of: stores the user follows + popular stores + stores with active offers.
- Return 5-8 stores.
- For guests: show popular stores only.


---

## Travel Section — Filter Support

The travel section has 3 filter chips. Currently filtered client-side, but the backend should tag offers:

| Filter | Arabic | Backend tag |
|---|---|---|
| International | خارج مصر | `travel_type: "international"` |
| Domestic | داخل مصر | `travel_type: "domestic"` |
| All | جميع الرحلات | No filter (return all) |

Add `travel_type` field to travel offers:

```json
{
  "id": "uuid",
  "travel_type": "international",
  ...other offer fields
}
```

---

## Guest User Behavior

When no authentication token is provided:

| Section | Behavior |
|---|---|
| `user` | Return `name: ""`, `avatar_url: null`, `location: ""` |
| `banners` | Return all active banners (same as authenticated) |
| `personalized_offers` | Return popular/trending offers (no personalization) |
| `featured_offers` | Return same as authenticated |
| `travel_offers` | Return same as authenticated |
| `egypt_offers` | Return same as authenticated |
| `favorites` | Return empty array `[]` |
| `stores` | Return popular stores |
| `category_sections` | Return same as authenticated |
| `promo_end_time` | Return same as authenticated |
| Following feed | Not accessible (returns `401`) |

---

## Error Responses

| HTTP Status | When |
|---|---|
| `200 OK` | Success (including guest access to home) |
| `401 Unauthorized` | Token expired/invalid (for following feed only) |
| `404 Not Found` | Promo ID not found |
| `422 Unprocessable Entity` | Invalid parameters |
| `500 Internal Server Error` | Server error |

Error response shape:

```json
{
  "message": "The requested promo was not found.",
  "errors": {}
}
```

---

## Zero-Data Rules

### New user (just registered, no activity):
- `personalized_offers`: Return popular/trending offers (NOT empty)
- `favorites`: Empty array `[]`
- `stores`: Return popular stores
- Following feed: Return `recommended` and `trending` items (NOT empty)

### No active offers in system:
- All offer arrays: Empty `[]`
- `banners`: Empty `[]`
- `promo_end_time`: `null`
- Following feed items: Empty `[]` (this is the ONLY case where empty is acceptable)

### User follows stores but no active offers from them:
- Following feed: Return `recommended` and `trending` items (NOT empty)
- `source_type` will be `recommended` or `trending` for all items


---

## Image Requirements

| Context | Recommended Size | Aspect Ratio | Notes |
|---|---|---|---|
| Banner | 600×400 | 3:2 | Dark overlay applied by Flutter |
| Offer card (small) | 400×400 | 1:1 | Displayed at 148w × 112h |
| Offer card (featured) | 400×600 | 2:3 | Displayed at 200w × 160h |
| Feed post image | 600×450 | 4:3 | Full-width in feed card |
| Store logo | 200×200 | 1:1 | Displayed as circle (70w) |
| Promo detail hero | 1200×1600 | 3:4 | Full-screen hero image |
| Category icon | 104×104 | 1:1 | Displayed at 52w |

All images should be served via CDN with proper caching headers.

---

## Caching Recommendations

| Endpoint | Cache Duration | Invalidation |
|---|---|---|
| `GET /customer/home` | 5 minutes | When user follows/unfollows a store, or saves/unsaves an offer |
| `GET /customer/home/following-feed` | 2 minutes | When followed store publishes new offer |
| `GET /customer/banners` | 15 minutes | When admin updates banners |
| `GET /customer/promos/{id}` | 10 minutes | When promo is updated |

---

## Database Schema Hints

### Tables needed:

1. **user_follows** — `user_id`, `store_id`, `created_at`
2. **user_favorites** — `user_id`, `offer_id`, `created_at`
3. **user_likes** — `user_id`, `offer_id`, `created_at`
4. **offer_comments** — `id`, `user_id`, `offer_id`, `content`, `created_at`
5. **banners** — `id`, `image_url`, `discount_label`, `min_transaction`, `date_range`, `cta_label`, `end_time`, `priority`, `is_active`
6. **promos** — `id`, `store_id`, `title`, `discount_label`, `likes_count`, `end_date`, `minimum_transaction`
7. **promo_items** — `id`, `promo_id`, `title`, `description`, `original_price`, `discounted_price`, `save_percent`, `end_date`
8. **promo_images** — `id`, `promo_id`, `image_url`, `sort_order`
9. **promo_branches** — `id`, `promo_id`, `branch_id`
10. **promo_terms** — `id`, `promo_id`, `term_text`, `sort_order`
11. **user_interests** — `user_id`, `category_id`, `score` (computed from browsing/saving behavior)
12. **home_category_sections** — `id`, `section_id`, `title_ar`, `title_en`, `background_color`, `priority`, `is_active`

### Indexes needed:
- `user_follows(user_id)` — for fetching followed stores
- `user_favorites(user_id)` — for fetching saved offers
- `offers(store_id, is_active, created_at)` — for feed queries
- `offers(category, is_active)` — for category sections
- `banners(is_active, end_time, priority)` — for active banners


---

## Sorting & Ordering Rules

### Home page sections:
- `banners`: By `priority` ASC (admin-set)
- `personalized_offers`: By relevance score DESC (personalization algorithm)
- `featured_offers`: By `priority` ASC or `discount_percent` DESC
- `travel_offers`: By `created_at` DESC
- `egypt_offers`: By `created_at` DESC
- `favorites`: By `user_favorites.created_at` DESC (most recently saved first)
- `stores`: By relevance (followed first, then popular)
- `category_sections`: By `priority` ASC

### Following feed:
- Primary: `source_type` priority (`followed` > `recommended` > `trending`)
- Secondary: `created_at` DESC within each source type
- Recommended items should be shuffled slightly to avoid repetition

---

## Rate Limiting

| Endpoint | Rate Limit |
|---|---|
| `GET /customer/home` | 30 requests/minute per user |
| `GET /customer/home/following-feed` | 60 requests/minute per user |
| `POST /customer/offers/{id}/toggle-favorite` | 30 requests/minute per user |
| `POST /customer/offers/{id}/toggle-like` | 30 requests/minute per user |
| `GET /customer/promos/{id}` | 30 requests/minute per user |

---

## Offer Expiry Handling

- NEVER return expired offers in any array.
- An offer is expired when `NOW() > offer.end_date`.
- When an offer expires:
  - Remove from all home page arrays on next request.
  - Remove from following feed.
  - Keep in user's favorites list but mark with `is_expired: true` (future enhancement).
- The `promo_end_time` countdown should always point to the nearest FUTURE end time.

---

## Infinite Scroll — Technical Details

The following feed uses cursor-based or offset-based pagination:

- Flutter sends `page=1` on first load, then increments.
- Backend returns `has_next_page: true` as long as more content exists.
- When `has_next_page: false`, Flutter stops requesting.
- **IMPORTANT:** Due to the fallback algorithm, `has_next_page` should almost always be `true` unless the system has zero offers.
- `total_items` can be approximate (for performance). Flutter only uses `has_next_page`.

---

## Social Features (Likes, Comments, Saves)

### Likes:
- One like per user per offer (toggle).
- `likes_count` is the total across all users.
- `is_liked` is specific to the authenticated user.

### Comments:
- `comments_count` is the total comments on the offer.
- Comments are NOT loaded on the home page — only the count.
- Full comments are loaded on the offer detail page (separate endpoint, not in this doc).

### Saves (Favorites):
- One save per user per offer (toggle).
- `is_saved` / `is_favorite` are the same concept (bookmarking).
- Saved offers appear in the `favorites` section of the home page.


---

## Widget-to-Endpoint Mapping

This table maps each Flutter widget to the exact data it needs:

| Widget | Data Source | Fields Used |
|---|---|---|
| `HomeHeaderWidget` | `data.user` | `name`, `avatar_url`, `location` |
| `HomeCountdownWidget` | `data.promo_end_time` | ISO datetime |
| `HomeBannerCarouselWidget` | `data.banners[]` | All banner fields |
| `HomeCategoriesWidget` | Separate CategoriesCubit endpoint | `id`, `name`, `image_url` |
| `HomeOffersRowWidget` (Personalized) | `data.personalized_offers[]` | All offer fields |
| `HomeOffersRowWidget` (Egypt) | `data.egypt_offers[]` | All offer fields |
| `HomeOffersRowWidget` (Favorites) | `data.favorites[]` | All offer fields |
| `HomeFeaturedOffersWidget` | `data.featured_offers[]` | All featured offer fields |
| `HomeStoresRowWidget` | `data.stores[]` | `id`, `name`, `image_url` |
| `HomeProductCategorySectionWidget` | `data.category_sections[]` | `title_ar`, `background_color`, `offers[]`, `stores[]` |
| `HomeFollowingPostCard` | Following feed `items[]` | `store.*`, `offer.*` (all fields) |
| `HomeOfferCardWidget` | Any offer array | `id`, `image_url`, `title`, `original_price`, `discounted_price`, `save_percent`, `is_favorite`, `store_name` |
| `PromoDetailsPage` | `GET /promos/{id}` | All promo detail fields |
| Travel section cards | `data.travel_offers[]` | All offer fields + `rating_avg`, `rating_count`, `location_label` |
| Travel filter chips | `data.travel_offers[].travel_type` | `travel_type` field |

---

## Promo Details Page — Tab Mapping

The promo details page has 4 tabs:

| Tab Index | Tab Name (Arabic) | Data Source |
|---|---|---|
| 0 | المنتجات | `data.promo_items[]` |
| 1 | الفروع | `data.branches[]` |
| 2 | الشروط | `data.terms[]` |
| 3 | التاجر | `data.merchant_info` |

---

## Following Feed — Display Logic in Flutter

Flutter displays the feed as follows:
- Each feed item is a card with: store header (logo + name + "متابَع" badge) → offer image (4:3) → offer details (title, prices, like/comment/save bar, CTA button).
- If `store.is_followed == true`: show "متابَع" (Following) badge.
- If `store.is_followed == false`: show "متابعة" (Follow) button instead (future feature).
- Double-tap on image → like animation + increment `likes_count`.
- Heart icon → toggle like.
- Bookmark icon → toggle save.
- Comment icon → navigate to offer detail page.
- "عرض التفاصيل" button → navigate to offer detail page.

---

## Content Freshness Rules

| Section | How often content should change |
|---|---|
| `personalized_offers` | Every request (rotate content) |
| `featured_offers` | Every 1-6 hours (admin-curated) |
| `travel_offers` | When new travel offers are published |
| `egypt_offers` | When new offers are published |
| `favorites` | Real-time (reflects user's saves) |
| `stores` | Every request (mix followed + popular) |
| `banners` | Admin-controlled (publish/unpublish) |
| Following feed | Real-time (new offers appear immediately) |


---

## Checklist for Backend Developer

Before marking this as done, confirm:

### Home Endpoint
- [ ] `GET /api/v1/customer/home` is implemented and returns the full response shape.
- [ ] Guest users (no token) receive `200` with generic popular content, NOT `401`.
- [ ] `user` object returns real user data for authenticated users, empty strings for guests.
- [ ] `promo_end_time` returns the nearest active promo end time, or `null` if none.
- [ ] `banners` returns only active (non-expired) banners ordered by priority.
- [ ] `personalized_offers` returns 4-8 offers based on user interests (or popular for new users).
- [ ] `featured_offers` returns 3-6 admin-curated or algorithmically selected offers.
- [ ] `travel_offers` returns 3-6 travel offers with `rating_avg`, `rating_count`, `location_label`, and `travel_type`.
- [ ] `egypt_offers` returns 3-6 Egypt-specific offers.
- [ ] `favorites` returns user's saved offers (empty for guests).
- [ ] `stores` returns 5-8 stores (mix of followed + popular).
- [ ] `category_sections` returns 3-6 themed sections with offers and stores.
- [ ] No expired offers appear in any array.
- [ ] `is_favorite` is correctly computed per authenticated user.

### Following Feed Endpoint
- [ ] `GET /api/v1/customer/home/following-feed` is implemented with pagination.
- [ ] Returns `401` for unauthenticated users.
- [ ] Fallback algorithm is implemented: followed → recommended → trending.
- [ ] Feed is NEVER empty (unless zero offers exist system-wide).
- [ ] `source_type` is correctly set for each item.
- [ ] `recommendation_reason` is set for non-followed items.
- [ ] `is_liked`, `likes_count`, `comments_count`, `is_saved` are correct per user.
- [ ] `store.is_followed` is correct per user.
- [ ] Pagination works correctly with `has_next_page`.
- [ ] Items are ordered: followed (newest first) → recommended → trending.

### Social Endpoints
- [ ] `POST /api/v1/customer/offers/{offer_id}/toggle-favorite` works (toggle on/off).
- [ ] `POST /api/v1/customer/offers/{offer_id}/toggle-like` works (toggle on/off, returns updated count).
- [ ] Both return `401` for unauthenticated users.
- [ ] Both are idempotent (calling twice returns to original state).

### Promo Details Endpoint
- [ ] `GET /api/v1/customer/promos/{promo_id}` returns full promo details.
- [ ] `image_urls` returns 1-5 images for the hero carousel.
- [ ] `promo_items` returns all coupon items with prices and dates.
- [ ] `branches` returns all participating branches with coordinates and hours.
- [ ] `terms` returns all terms as string array.
- [ ] `merchant_info` returns store details including verification status and phone.
- [ ] `is_liked` and `is_saved` are correct for authenticated users (false for guests).
- [ ] `likes_count` is the real total.
- [ ] Returns `404` for non-existent promo IDs.

### Banners Endpoint
- [ ] `GET /api/v1/customer/banners` returns active banners.
- [ ] Each banner has `end_time` for per-banner countdown.
- [ ] Each banner has `promo_id` for navigation to promo details.
- [ ] Expired banners are never returned.

### Performance
- [ ] Home endpoint responds in < 500ms.
- [ ] Following feed responds in < 300ms.
- [ ] Caching is implemented per the recommendations above.
- [ ] Images are served via CDN.

### Edge Cases
- [ ] New user with zero activity gets populated home (not empty).
- [ ] User who follows stores with all expired offers gets recommended content in feed.
- [ ] User who unfollows all stores gets recommended content in feed.
- [ ] Offer that expires while user is browsing disappears on next refresh.
- [ ] `save_percent` is always a positive integer between 1-99.
- [ ] Prices are never negative.
- [ ] `likes_count` and `comments_count` are never negative.

---

## Summary of All Endpoints

| Method | Path | Auth | Description |
|---|---|---|---|
| `GET` | `/api/v1/customer/home` | Optional | Main home page data |
| `GET` | `/api/v1/customer/home/following-feed` | Required | Infinite scroll feed |
| `GET` | `/api/v1/customer/banners` | Optional | Active banners list |
| `GET` | `/api/v1/customer/promos/{promo_id}` | Optional | Promo detail page |
| `POST` | `/api/v1/customer/offers/{offer_id}/toggle-favorite` | Required | Save/unsave offer |
| `POST` | `/api/v1/customer/offers/{offer_id}/toggle-like` | Required | Like/unlike offer |

---

**End of document.**
