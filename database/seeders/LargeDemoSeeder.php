<?php

namespace Database\Seeders;

use App\Domain\Notification\Models\Notification;
use App\Domain\Product\Enums\InventoryMode;
use App\Domain\Product\Enums\OfferClaimStatus;
use App\Domain\Product\Enums\ProductApprovalStatus;
use App\Domain\Product\Enums\ProductOfferStatus;
use App\Domain\Product\Enums\ProductOfferTargetRole;
use App\Domain\Product\Enums\ProductOfferType;
use App\Domain\Product\Enums\ProductRevisionAction;
use App\Domain\Product\Enums\ProductRevisionStatus;
use App\Domain\Product\Enums\ProductStatus;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\OfferClaim;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\ProductComment;
use App\Domain\Product\Models\ProductCommentLike;
use App\Domain\Product\Models\ProductImage;
use App\Domain\Product\Models\ProductLike;
use App\Domain\Product\Models\ProductOffer;
use App\Domain\Product\Models\ProductRevision;
use App\Domain\Product\Models\ProductVariant;
use App\Domain\Product\Models\ProductView;
use App\Domain\Store\Enums\InvitationStatus;
use App\Domain\Store\Enums\StorePermission;
use App\Domain\Store\Enums\StoreStatus;
use App\Domain\Store\Models\Social;
use App\Domain\Store\Models\Store;
use App\Domain\Store\Models\StoreCategory;
use App\Domain\Store\Models\StoreComment;
use App\Domain\Store\Models\StoreCommentLike;
use App\Domain\Store\Models\StoreEmployee;
use App\Domain\Store\Models\StoreFollowers;
use App\Domain\Store\Models\StoreHours;
use App\Domain\Store\Models\StoreInvitation;
use App\Domain\Store\Models\StoreSocial;
use App\Domain\Store\Models\StoreVerification;
use App\Domain\User\Models\Address;
use App\Domain\User\Models\Profile;
use App\Domain\User\Models\User;
use App\Domain\User\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class LargeDemoSeeder extends Seeder
{
    private const PRODUCT_TARGET = 300;

    private User $admin;

    /** @var Collection<int, User> */
    private Collection $sellers;

    /** @var Collection<int, User> */
    private Collection $staff;

    /** @var Collection<int, User> */
    private Collection $customers;

    /** @var Collection<int, Store> */
    private Collection $activeStores;

    public function run(): void
    {
        $this->admin = $this->createUser('admin@coupony.com', 'Admin', 'User', 'admin', 1);

        $this->sellers = collect(range(1, 12))->map(
            fn(int $index) => $this->createUser("seller{$index}@example.com", 'Seller', "User {$index}", $index <= 10 ? 'seller' : 'seller_pending', $index + 10)
        );

        $this->staff = collect(range(1, 5))->map(
            fn(int $index) => $this->createUser("staff{$index}@example.com", 'Store', "Staff {$index}", 'store_employee', $index + 30)
        );

        $this->customers = collect(range(1, 80))->map(
            fn(int $index) => $this->createUser("customer{$index}@example.com", 'Customer', "User {$index}", 'customer', $index + 100)
        );

        $this->seedCustomerInterestTables();
        $this->activeStores = $this->seedStores();
        $products = $this->seedProducts();
        $this->seedEngagement($products);
        $this->seedUtilityTables();
        $this->seedNotifications($products);

        $this->command->info(sprintf(
            'Large demo dataset seeded: %d users, %d active stores, %d products',
            User::count(),
            $this->activeStores->count(),
            Product::count()
        ));
    }

    private function createUser(string $email, string $firstName, string $lastName, string $role, int $phoneSeed): User
    {
        $user = User::create([
            'email' => $email,
            'phone_number' => '+2010' . str_pad((string) $phoneSeed, 8, '0', STR_PAD_LEFT),
            'password_hash' => Hash::make('password'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'status' => 'active',
            'last_login_at' => now()->subDays($phoneSeed % 20),
            'login_count' => 1 + ($phoneSeed % 40),
            'language' => $phoneSeed % 3 === 0 ? 'ar' : 'en',
            'timezone' => 'Africa/Cairo',
        ]);

        $user->assignRole($role);

        Profile::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'date_of_birth' => Carbon::create(1985 + ($phoneSeed % 15), (($phoneSeed % 12) + 1), (($phoneSeed % 25) + 1)),
            'gender' => $phoneSeed % 2 === 0 ? 'female' : 'male',
            'avatar_url' => "https://i.pravatar.cc/300?u={$email}",
            'bio' => "{$firstName} {$lastName} is part of the Coupony demo dataset.",
        ]);

        UserPreference::create([
            'user_id' => $user->id,
            'preferred_language' => $phoneSeed % 3 === 0 ? 'ar' : 'en',
            'preferred_currency' => 'EGP',
            'email_marketing' => $phoneSeed % 4 !== 0,
            'email_order_updates' => true,
            'sms_notifications' => $phoneSeed % 2 === 0,
            'push_notifications' => true,
            'preferred_payment_method' => $phoneSeed % 2 === 0 ? 'card' : 'cash',
            'enable_personalized_recommendations' => true,
            'browsing_history_tracking' => true,
            'show_profile_publicly' => false,
            'allow_data_sharing_for_analytics' => true,
        ]);

        if (Schema::hasTable('user_points')) {
            DB::table('user_points')->insert([
                'user_id' => $user->id,
                'current_balance' => 50 + ($phoneSeed * 3),
                'lifetime_earned' => 200 + ($phoneSeed * 10),
                'lifetime_spent' => 100 + ($phoneSeed * 4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user;
    }

    private function seedCustomerInterestTables(): void
    {
        foreach ($this->customers as $index => $customer) {
            if (Schema::hasTable('interests')) {
                DB::table('interests')->insert([
                    'user_id' => $customer->id,
                    'interesting_offers' => json_encode($this->pickMany(['restaurants', 'fashion', 'supermarket', 'electronics', 'pharmacy', 'beauty', 'travel'], $index, 3)),
                    'budget' => ['low', 'medium', 'best_value'][$index % 3],
                    'shopping_style' => json_encode($this->pickMany(['online', 'based_on_offer', 'in_store', 'best_discount'], $index, 2)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ($this->sellers as $index => $seller) {
            if (Schema::hasTable('shop_interests')) {
                DB::table('shop_interests')->insert([
                    'user_id' => $seller->id,
                    'customer_reach_method' => $index % 2 === 0 ? 'physical_store' : 'online_only',
                    'target_audience' => ['youth', 'families', 'all'][$index % 3],
                    'best_offer_time' => ['all_week', 'weekends_occasions', 'off_peak'][$index % 3],
                    'price_category' => ['budget', 'mid_range', 'premium', 'all_levels'][$index % 4],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedStores(): Collection
    {
        $stores = collect();
        $storeCategories = StoreCategory::query()->pluck('id', 'slug');
        $socials = Social::query()->get();
        $activeProfiles = $this->storeProfiles();

        foreach ($activeProfiles as $index => $profile) {
            $owner = $this->sellers[$index];
            $store = $this->createStore($owner, $profile, StoreStatus::ACTIVE, $index);
            $stores->push($store);

            $store->categories()->attach($this->storeCategoryIdsFor($storeCategories, $profile['category']));

            $address = $this->createAddress($profile['city'], $profile['name'], $index);
            $store->addresses()->attach($address->id, [
                'label' => 'main_branch',
                'is_default_shipping' => true,
                'is_default_billing' => true,
            ]);

            $this->seedStoreHours($store);
            $this->seedStoreVerifications($store, 'approved');
            $this->seedStoreSocials($store, $socials, $profile['slug']);
            $this->seedStoreStaff($store, $address, $index);
        }

        for ($i = 0; $i < 2; $i++) {
            $profile = [
                'name' => 'Pending Bazaar ' . ($i + 1),
                'slug' => 'pending-bazaar-' . ($i + 1),
                'city' => ['Cairo', 'Giza'][$i],
                'category' => 'fashion',
            ];

            $store = $this->createStore($this->sellers[10 + $i], $profile, StoreStatus::PENDING, 20 + $i);
            $store->categories()->attach($this->storeCategoryIdsFor($storeCategories, $profile['category']));
            $store->addresses()->attach($this->createAddress($profile['city'], $profile['name'], 20 + $i)->id, ['label' => 'main_branch']);
            $this->seedStoreHours($store);
            $this->seedStoreVerifications($store, 'pending');
        }

        $rejectedProfile = [
            'name' => 'Rejected Outlet',
            'slug' => 'rejected-outlet',
            'city' => 'Alexandria',
            'category' => 'misc',
        ];
        $rejectedStore = $this->createStore($this->customers->first(), $rejectedProfile, StoreStatus::REJECTED, 50);
        $rejectedStore->categories()->attach($this->storeCategoryIdsFor($storeCategories, $rejectedProfile['category']));
        $rejectedStore->addresses()->attach($this->createAddress('Alexandria', 'Rejected Outlet', 50)->id, ['label' => 'main_branch']);
        $this->seedStoreHours($rejectedStore);
        $this->seedStoreVerifications($rejectedStore, 'rejected');

        return $stores;
    }

    private function storeCategoryIdsFor(Collection $storeCategories, string $category): array
    {
        $slug = match ($category) {
            'electronics' => 'electronics',
            'fashion' => 'fashion-clothing',
            'food' => 'food-beverages',
            'home' => 'home-garden',
            'beauty' => 'beauty-health',
            'sports' => 'sports-outdoors',
            default => 'grocery',
        };

        return array_values(array_filter([$storeCategories->get($slug)]));
    }

    private function createStore(User $owner, array $profile, StoreStatus $status, int $index): Store
    {
        return Store::create([
            'owner_user_id' => $owner->id,
            'name' => $profile['name'],
            'description' => "A curated {$profile['category']} store in {$profile['city']} with realistic seeded offers and products.",
            'logo_url' => "https://placehold.co/320x320/png?text=" . urlencode((string) Str::of($profile['name'])->substr(0, 12)),
            'banner_url' => $this->bannerFor($profile['category'], $index),
            'email' => Str::slug($profile['name']) . '@demo.coupony.test',
            'phone' => '+202' . str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT),
            'tax_id' => 'TAX-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT),
            'commission_rate' => 0.1200 + (($index % 4) * 0.01),
            'status' => $status,
            'subscription_tier' => ['basic', 'premium', 'enterprise'][$index % 3],
            'is_verified' => $status === StoreStatus::ACTIVE,
            'verified_at' => $status === StoreStatus::ACTIVE ? now()->subDays(40 - $index) : null,
            'total_sales' => $status === StoreStatus::ACTIVE ? 15000 + ($index * 3250) : 0,
            'rating_avg' => 0,
            'rating_count' => 0,
            'approved_at' => $status === StoreStatus::ACTIVE ? now()->subDays(30 - min($index, 20)) : null,
            'approved_by' => $status === StoreStatus::ACTIVE ? $this->admin->id : null,
            'rejected_at' => $status === StoreStatus::REJECTED ? now()->subDays(5) : null,
            'rejected_by' => $status === StoreStatus::REJECTED ? $this->admin->id : null,
            'rejection_reason' => $status === StoreStatus::REJECTED ? 'Demo rejection for incomplete commercial documents.' : null,
            'admin_notes' => 'Seeded by LargeDemoSeeder.',
        ]);
    }

    private function createAddress(string $city, string $company, int $index): Address
    {
        return Address::create([
            'first_name' => 'Branch',
            'last_name' => 'Manager',
            'company' => $company,
            'address_line1' => (10 + $index) . ' Demo Street',
            'address_line2' => 'Floor ' . (($index % 5) + 1),
            'city' => $city,
            'state_province' => $city,
            'postal_code' => '11' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'country_code' => 'EG',
            'phone_number' => '+202' . str_pad((string) (20000000 + $index), 8, '0', STR_PAD_LEFT),
            'latitude' => 30.0444 + ($index / 1000),
            'longitude' => 31.2357 + ($index / 1000),
            'delivery_instructions' => 'Seeded demo branch address.',
        ]);
    }

    private function seedStoreHours(Store $store): void
    {
        foreach (range(0, 6) as $day) {
            StoreHours::create([
                'store_id' => $store->id,
                'day_of_week' => $day,
                'open_time' => $day === 5 ? '13:00' : '09:00',
                'close_time' => $day === 5 ? '22:00' : '21:00',
                'is_closed' => false,
            ]);
        }
    }

    private function seedStoreVerifications(Store $store, string $status): void
    {
        foreach (['commercial_register', 'tax_card', 'id_card_front', 'id_card_back'] as $document) {
            StoreVerification::create([
                'store_id' => $store->id,
                'document_type' => $document,
                'document_path' => "documents/demo/{$store->id}/{$document}.pdf",
                'status' => $status,
                'verified_by' => $status === 'approved' ? $this->admin->id : null,
                'verified_at' => $status === 'approved' ? now()->subDays(12) : null,
                'rejection_reason' => $status === 'rejected' ? 'Document is not readable in this demo scenario.' : null,
            ]);
        }
    }

    private function seedStoreSocials(Store $store, Collection $socials, string $slug): void
    {
        foreach ($socials->take(3) as $social) {
            StoreSocial::create([
                'store_id' => $store->id,
                'social_id' => $social->id,
                'link' => 'https://example.com/' . Str::slug($social->name) . '/' . $slug,
            ]);
        }
    }

    private function seedStoreStaff(Store $store, Address $address, int $index): void
    {
        $permissions = [
            StorePermission::PRODUCTS_VIEW->value,
            StorePermission::CLAIMS_VIEW->value,
            StorePermission::CLAIMS_MANAGE->value,
        ];
        $staffUser = $this->staff[$index % $this->staff->count()];
        $employeePayload = [
            'store_id' => $store->id,
            'user_id' => $staffUser->id,
        ];

        if (Schema::hasColumn('store_employees', 'address_id')) {
            $employeePayload['address_id'] = $address->id;
        }

        if (Schema::hasColumn('store_employees', 'role')) {
            $employeePayload['role'] = 'cashier';
        }

        if (Schema::hasColumn('store_employees', 'permissions')) {
            $employeePayload['permissions'] = $permissions;
        }

        StoreEmployee::create($employeePayload);

        if (Schema::hasTable('user_roles')) {
            $role = Role::query()->where('name', 'store_employee')->first();
            DB::table('user_roles')->insert([
                'user_id' => $staffUser->id,
                'role_id' => $role?->id,
                'store_id' => $store->id,
                'granted_at' => now(),
                'granted_by_user_id' => $store->owner_user_id,
                'expires_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $invitee = $this->customers[($index + 20) % $this->customers->count()];
        $invitationPayload = [
            'store_id' => $store->id,
            'invited_by_user_id' => $store->owner_user_id,
            'invitee_user_id' => $invitee->id,
            'role' => 'store_employee',
            'permissions' => [StorePermission::PRODUCTS_VIEW->value, StorePermission::CLAIMS_VIEW->value],
            'status' => [InvitationStatus::PENDING, InvitationStatus::ACCEPTED, InvitationStatus::DECLINED][$index % 3],
            'message' => 'Demo staff invitation seeded for store-management testing.',
            'expires_at' => now()->addDays(7),
            'accepted_at' => $index % 3 === 1 ? now()->subDays(1) : null,
            'declined_at' => $index % 3 === 2 ? now()->subDays(1) : null,
        ];

        if (Schema::hasColumn('store_invitations', 'address_id')) {
            $invitationPayload['address_id'] = $address->id;
        }

        StoreInvitation::create($invitationPayload);
    }

    private function seedProducts(): Collection
    {
        $products = collect();
        $templates = $this->catalogTemplates();
        $categories = Category::query()->pluck('id', 'slug');

        for ($index = 0; $index < self::PRODUCT_TARGET; $index++) {
            $template = $templates[$index % count($templates)];
            $store = $this->activeStores[$index % $this->activeStores->count()];
            $title = $this->productTitle($template, $index);
            $basePrice = $this->priceFor($template, $index);
            $approval = $this->approvalFor($index);

            $product = Product::create([
                'store_id' => $store->id,
                'title' => $title,
                'slug' => Str::slug($title) . '-' . ($index + 1),
                'short_description' => $template['short'] . ' Demo item #' . ($index + 1) . '.',
                'description' => $template['description'] . ' Includes realistic seeded pricing, variants, images, reviews, and offer metadata.',
                'base_price' => $basePrice,
                'compare_at_price' => round($basePrice * (1.12 + (($index % 5) / 100)), 2),
                'currency' => 'EGP',
                'sku' => 'PRD-' . strtoupper($template['code']) . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                'status' => $approval === ProductApprovalStatus::APPROVED && $index % 11 !== 0 ? ProductStatus::ACTIVE : ProductStatus::INACTIVE,
                'approval_status' => $approval,
                'published_revision_no' => $approval === ProductApprovalStatus::APPROVED ? 1 : 0,
                'approved_at' => $approval === ProductApprovalStatus::APPROVED ? now()->subDays(($index % 45) + 1) : null,
                'approved_by' => $approval === ProductApprovalStatus::APPROVED ? $this->admin->id : null,
                'rejected_at' => $approval === ProductApprovalStatus::REJECTED ? now()->subDays(($index % 15) + 1) : null,
                'rejected_by' => $approval === ProductApprovalStatus::REJECTED ? $this->admin->id : null,
                'rejection_reason' => $approval === ProductApprovalStatus::REJECTED ? 'Demo rejection: product images need clearer packaging shots.' : null,
                'admin_notes' => 'Large demo seeded product.',
                'is_featured' => $index % 9 === 0,
                'sale_count' => $index % 60,
                'redemption_count' => $index % 25,
                'rating_avg' => 0,
                'rating_count' => 0,
            ]);

            $categoryIds = collect($template['category_slugs'])
                ->map(fn(string $slug) => $categories->get($slug))
                ->filter()
                ->values()
                ->all();
            $product->categories()->attach($categoryIds);

            $this->seedProductImages($product, $template, $index);
            $variants = $this->seedProductVariants($product, $template, $basePrice, $index);
            $offer = $this->seedProductOffer($product, $variants, $index);
            $this->seedProductRevision($product, $template, $variants, $offer, $index);

            $products->push($product);
        }

        return $products;
    }

    private function seedProductImages(Product $product, array $template, int $index): void
    {
        $images = $this->pickMany($template['images'], $index, 1 + ($index % 3));

        foreach ($images as $sort => $imageUrl) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $imageUrl,
                'sort_order' => $sort,
                'is_primary' => $sort === 0,
                'created_at' => now(),
            ]);
        }
    }

    private function seedProductVariants(Product $product, array $template, float $basePrice, int $index): Collection
    {
        $variants = collect();
        $options = $template['variants'];
        $variantCount = min(count($options), 2 + ($index % 3));

        for ($i = 0; $i < $variantCount; $i++) {
            $option = $options[($index + $i) % count($options)];
            $price = round($basePrice + ($i * $template['price_step']), 2);
            $inventoryMode = ($index + $i) % 4 === 0 ? InventoryMode::TRACKED : InventoryMode::UNLIMITED;

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'title' => $option['title'],
                'option_summary' => $option['summary'],
                'sku' => 'VAR-' . strtoupper($template['code']) . '-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT) . '-' . ($i + 1),
                'barcode' => '62' . str_pad((string) (($index + 1) * 10 + $i), 10, '0', STR_PAD_LEFT),
                'original_price' => $price,
                'price' => $price,
                'compare_at_price' => round($price * 1.12, 2),
                'currency' => 'EGP',
                'sort_order' => $i,
                'is_default' => $i === 0,
                'is_active' => $i !== 3 || $index % 5 !== 0,
                'inventory_mode' => $inventoryMode,
                'stock_qty' => $inventoryMode === InventoryMode::TRACKED ? 15 + (($index + $i) % 80) : null,
                'low_stock_threshold' => $inventoryMode === InventoryMode::TRACKED ? 5 : null,
                'allow_backorder' => $inventoryMode === InventoryMode::TRACKED && $index % 10 === 0,
                'sale_count' => ($index + $i) % 40,
                'redemption_count' => ($index + $i) % 18,
            ]);

            foreach ($option['attributes'] as $order => $attribute) {
                $variant->attributes()->create([
                    'attribute_name' => $attribute[0],
                    'attribute_value' => $attribute[1],
                    'sort_order' => $order,
                    'created_at' => now(),
                ]);
            }

            $variants->push($variant);
        }

        return $variants;
    }

    private function seedProductOffer(Product $product, Collection $variants, int $index): ProductOffer
    {
        $type = [ProductOfferType::PERCENTAGE, ProductOfferType::FIXED, ProductOfferType::BUY_X_GET_Y][$index % 3];

        $offer = ProductOffer::create([
            'product_id' => $product->id,
            'type' => $type,
            'status' => $product->approval_status === ProductApprovalStatus::APPROVED ? ProductOfferStatus::ACTIVE : ProductOfferStatus::INACTIVE,
            'label' => match ($type) {
                ProductOfferType::PERCENTAGE => (10 + ($index % 20)) . '% off',
                ProductOfferType::FIXED => 'EGP ' . (50 + (($index % 6) * 25)) . ' off',
                ProductOfferType::BUY_X_GET_Y => 'Buy 2 get 1',
            },
            'starts_at' => now()->subDays($index % 10),
            'ends_at' => now()->addDays(20 + ($index % 30)),
            'claim_expiration_minutes' => 1440,
            'fixed_amount' => $type === ProductOfferType::FIXED ? 50 + (($index % 6) * 25) : null,
            'percentage_value' => $type === ProductOfferType::PERCENTAGE ? 10 + ($index % 20) : null,
            'max_discount' => $type === ProductOfferType::PERCENTAGE ? 300 + (($index % 5) * 50) : null,
            'buy_qty' => $type === ProductOfferType::BUY_X_GET_Y ? 2 : null,
            'get_qty' => $type === ProductOfferType::BUY_X_GET_Y ? 1 : null,
            'allow_mix_buy_variants' => $type === ProductOfferType::BUY_X_GET_Y && $variants->count() > 2,
            'allow_mix_reward_variants' => false,
        ]);

        if ($type === ProductOfferType::BUY_X_GET_Y) {
            $variants->take(2)->each(fn(ProductVariant $variant) => $offer->targets()->create([
                'variant_id' => $variant->id,
                'role' => ProductOfferTargetRole::BUY,
            ]));

            $offer->targets()->create([
                'variant_id' => $variants->last()->id,
                'role' => ProductOfferTargetRole::REWARD,
            ]);
        }

        return $offer;
    }

    private function seedProductRevision(Product $product, array $template, Collection $variants, ProductOffer $offer, int $index): void
    {
        $status = match ($product->approval_status) {
            ProductApprovalStatus::APPROVED => ProductRevisionStatus::APPROVED,
            ProductApprovalStatus::REJECTED => ProductRevisionStatus::REJECTED,
            default => ProductRevisionStatus::PENDING,
        };

        ProductRevision::create([
            'product_id' => $product->id,
            'revision_no' => 1,
            'action' => ProductRevisionAction::CREATE,
            'status' => $status,
            'base_revision_no' => null,
            'submitted_by' => $product->store->owner_user_id,
            'submitted_at' => now()->subDays(($index % 50) + 1),
            'reviewed_by' => $status === ProductRevisionStatus::PENDING ? null : $this->admin->id,
            'reviewed_at' => $status === ProductRevisionStatus::PENDING ? null : now()->subDays($index % 20),
            'rejection_reason' => $status === ProductRevisionStatus::REJECTED ? 'Please replace the primary image and clarify the offer terms.' : null,
            'admin_notes' => 'Seeded revision for approval workflow demos.',
            'payload' => [
                'title' => $product->title,
                'category_slugs' => $template['category_slugs'],
                'variants' => $variants->pluck('sku')->all(),
                'offer' => [
                    'type' => $offer->type->value,
                    'label' => $offer->label,
                ],
            ],
            'review_fields' => ['title', 'images', 'variants', 'offer'],
            'requested_changes' => $status === ProductRevisionStatus::REJECTED ? ['images' => 'Use clearer product photos.', 'offer' => 'Clarify redemption conditions.'] : null,
        ]);
    }

    private function seedEngagement(Collection $products): void
    {
        foreach ($this->activeStores as $storeIndex => $store) {
            $followers = $this->customers->reject(fn(User $customer) => $customer->id === $store->owner_user_id)->slice($storeIndex * 5, 24);

            foreach ($followers as $index => $customer) {
                StoreFollowers::create([
                    'store_id' => $store->id,
                    'user_id' => $customer->id,
                    'notification_enabled' => $index % 4 !== 0,
                    'followed_at' => now()->subDays($index + $storeIndex),
                ]);
            }

            if (Schema::hasColumn('stores', 'followers_count')) {
                $store->update(['followers_count' => $followers->count()]);
            }

            $this->seedStoreComments($store, $storeIndex);
        }

        foreach ($products as $index => $product) {
            $reviewers = $this->customers->reject(fn(User $customer) => $customer->id === $product->store->owner_user_id)->slice($index % 60, 3)->values();

            foreach ($reviewers as $offset => $customer) {
                $comment = ProductComment::create([
                    'product_id' => $product->id,
                    'user_id' => $customer->id,
                    'rating' => 3 + (($index + $offset) % 3),
                    'body' => $this->reviewBody($product->title, $offset),
                    'status' => $offset === 2 && $index % 17 === 0 ? ProductComment::STATUS_HIDDEN : ProductComment::STATUS_VISIBLE,
                ]);

                ProductComment::create([
                    'product_id' => $product->id,
                    'user_id' => $product->store->owner_user_id,
                    'parent_id' => $comment->id,
                    'body' => 'Thanks for trying this demo product. We appreciate the feedback.',
                    'status' => ProductComment::STATUS_VISIBLE,
                ]);

                $this->customers->slice(($index + $offset) % 70, 2)->each(fn(User $liker) => ProductCommentLike::firstOrCreate([
                    'comment_id' => $comment->id,
                    'user_id' => $liker->id,
                ]));
            }

            $this->customers->slice($index % 70, 8)->each(fn(User $liker) => ProductLike::firstOrCreate([
                'product_id' => $product->id,
                'user_id' => $liker->id,
            ]));

            if (Schema::hasTable('product_views')) {
                foreach (range(1, 12 + ($index % 12)) as $viewIndex) {
                    ProductView::create([
                        'product_id' => $product->id,
                        'user_id' => $viewIndex % 4 === 0 ? null : $this->customers[($index + $viewIndex) % $this->customers->count()]->id,
                        'ip_address' => '10.10.' . ($index % 255) . '.' . ($viewIndex + 10),
                        'user_agent' => 'CouponyDemoSeeder/1.0',
                        'created_at' => now()->subHours($viewIndex),
                    ]);
                }
            }

            if ($product->offer?->status === ProductOfferStatus::ACTIVE && $index % 2 === 0) {
                $this->seedOfferClaims($product, $index);
            }
        }
    }

    private function seedStoreComments(Store $store, int $storeIndex): void
    {
        $reviewers = $this->customers->slice($storeIndex * 6, 6)->values();

        foreach ($reviewers as $offset => $customer) {
            $comment = StoreComment::create([
                'store_id' => $store->id,
                'user_id' => $customer->id,
                'rating' => 3 + (($storeIndex + $offset) % 3),
                'body' => "Helpful staff and a strong demo selection at {$store->name}.",
                'status' => StoreComment::STATUS_VISIBLE,
            ]);

            StoreComment::create([
                'store_id' => $store->id,
                'user_id' => $store->owner_user_id,
                'parent_id' => $comment->id,
                'body' => 'Thanks for visiting our demo store.',
                'status' => StoreComment::STATUS_VISIBLE,
            ]);

            $this->customers->slice(($storeIndex + $offset) % 70, 2)->each(fn(User $liker) => StoreCommentLike::firstOrCreate([
                'comment_id' => $comment->id,
                'user_id' => $liker->id,
            ]));
        }
    }

    private function seedOfferClaims(Product $product, int $index): void
    {
        foreach ([OfferClaimStatus::ACTIVE, OfferClaimStatus::REDEEMED, OfferClaimStatus::EXPIRED, OfferClaimStatus::CANCELLED] as $offset => $status) {
            $user = $this->customers[($index + $offset) % $this->customers->count()];

            OfferClaim::create([
                'user_id' => $user->id,
                'store_id' => $product->store_id,
                'product_id' => $product->id,
                'offer_id' => $product->offer->id,
                'status' => $status,
                'claim_token' => 'CLM-' . Str::upper(Str::random(24)) . '-' . $index . '-' . $offset,
                'qr_code_token' => 'QR-' . Str::upper(Str::random(24)) . '-' . $index . '-' . $offset,
                'offer_snapshot' => [
                    'product_title' => $product->title,
                    'store_name' => $product->store->name,
                    'offer_label' => $product->offer->label,
                    'offer_type' => $product->offer->type->value,
                ],
                'expires_at' => $status === OfferClaimStatus::EXPIRED ? now()->subDays(1) : now()->addDays(5),
                'redeemed_at' => $status === OfferClaimStatus::REDEEMED ? now()->subDays(2) : null,
                'redeemed_by' => $status === OfferClaimStatus::REDEEMED ? $product->store->employeeLinks()->value('user_id') : null,
            ]);
        }
    }

    private function seedUtilityTables(): void
    {
        if (Schema::hasTable('otps')) {
            foreach ($this->customers->take(15)->values() as $index => $customer) {
                DB::table('otps')->insert([
                    'user_id' => $customer->id,
                    'phone_or_email' => $customer->email,
                    'otp_hash' => Hash::make((string) (100000 + $index)),
                    'purpose' => ['login', 'verify_email', 'reset_password'][$index % 3],
                    'channel' => $index % 2 === 0 ? 'email' : 'sms',
                    'status' => ['pending', 'verified', 'expired'][$index % 3],
                    'attempts' => $index % 3,
                    'max_attempts' => 3,
                    'expires_at' => $index % 3 === 2 ? now()->subMinutes(10) : now()->addMinutes(20),
                    'used_at' => $index % 3 === 1 ? now()->subMinutes(5) : null,
                    'created_at' => now()->subHours($index),
                    'updated_at' => now()->subHours($index),
                ]);
            }
        }

        if (Schema::hasTable('notify_me')) {
            foreach (range(1, 20) as $index) {
                DB::table('notify_me')->insert([
                    'email' => "early-bird{$index}@example.com",
                    'created_at' => now()->subDays($index),
                    'updated_at' => now()->subDays($index),
                ]);
            }
        }

        if (Schema::hasTable('contact_us_customer')) {
            foreach (range(1, 12) as $index) {
                DB::table('contact_us_customer')->insert([
                    'name' => "Demo Customer {$index}",
                    'email' => "contact-customer{$index}@example.com",
                    'subject' => 'Question about a seeded offer',
                    'message' => 'This is a realistic demo support message seeded for dashboard testing.',
                    'created_at' => now()->subDays($index),
                    'updated_at' => now()->subDays($index),
                ]);
            }
        }

        if (Schema::hasTable('contact_us_seller')) {
            foreach (range(1, 8) as $index) {
                DB::table('contact_us_seller')->insert([
                    'store_name' => "Prospective Seller {$index}",
                    'phone_number' => '+2011' . str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                    'created_at' => now()->subDays($index),
                    'updated_at' => now()->subDays($index),
                ]);
            }
        }
    }

    private function seedNotifications(Collection $products): void
    {
        foreach (User::query()->take(40)->get() as $index => $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'welcome',
                'title' => 'Welcome to Coupony',
                'message' => 'Your demo account is ready with fresh marketplace data.',
                'data' => ['seeded' => true],
                'channel' => 'in_app',
                'status' => $index % 3 === 0 ? 'read' : 'sent',
                'reference_type' => null,
                'reference_id' => null,
                'sent_at' => now()->subDays($index % 12),
                'read_at' => $index % 3 === 0 ? now()->subDays($index % 8) : null,
            ]);

            $product = $products[$index % $products->count()];
            Notification::create([
                'user_id' => $user->id,
                'type' => ['promotion', 'price_drop', 'coupon_expiring'][$index % 3],
                'title' => 'Demo offer available',
                'message' => "{$product->title} has a seeded offer ready for testing.",
                'data' => ['product_id' => $product->id],
                'channel' => ['email', 'push', 'in_app'][$index % 3],
                'status' => ['sent', 'pending', 'read'][$index % 3],
                'reference_type' => Product::class,
                'reference_id' => $product->id,
                'sent_at' => now()->subHours($index),
                'read_at' => $index % 3 === 2 ? now()->subHours($index - 1) : null,
            ]);
        }
    }

    private function catalogTemplates(): array
    {
        return [
            [
                'code' => 'AUD',
                'family' => 'Audio',
                'names' => ['Wireless Earbuds Pro', 'Noise Canceling Headphones', 'Portable Bluetooth Speaker', 'Studio Monitor Buds'],
                'short' => 'Premium audio gear with daily-use pricing.',
                'description' => 'A practical electronics product for commuting, workouts, calls, and entertainment.',
                'category_slugs' => ['electronics', 'audio'],
                'price_min' => 899,
                'price_max' => 3499,
                'price_step' => 75,
                'variants' => $this->colorVariants(['Black', 'White', 'Navy', 'Rose Gold']),
                'images' => [
                    'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1484704849700-f032a568e944?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1545454675-3531b543be5d?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1606220588913-b3aacb4d2f46?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'code' => 'SNE',
                'family' => 'Footwear',
                'names' => ['Performance Runner Sneakers', 'Urban Court Trainers', 'Everyday Walking Shoes', 'Lightweight Gym Sneakers'],
                'short' => 'Comfortable footwear with realistic sizes and colors.',
                'description' => 'A reliable fashion product with breathable materials and durable soles.',
                'category_slugs' => ['fashion', 'footwear'],
                'price_min' => 1199,
                'price_max' => 4299,
                'price_step' => 100,
                'variants' => $this->shoeVariants(),
                'images' => [
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1608231387042-66d1773070a5?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1460353581641-37baddab0fa2?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'code' => 'CAF',
                'family' => 'Food',
                'names' => ['Weekend Brunch Voucher', 'Specialty Coffee Bundle', 'Family Pizza Deal', 'Dessert Tasting Box'],
                'short' => 'Redeemable food and beverage offers.',
                'description' => 'A local dining offer for testing coupon claims and redemptions.',
                'category_slugs' => ['food-beverages', 'restaurant-deals', 'coffee-desserts'],
                'price_min' => 149,
                'price_max' => 999,
                'price_step' => 35,
                'variants' => $this->packageVariants(['Single', 'Couple', 'Family', 'Party']),
                'images' => [
                    'https://images.unsplash.com/photo-1551782450-a2132b4ba21d?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1488477181946-6428a0291777?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'code' => 'HOM',
                'family' => 'Home',
                'names' => ['Ceramic Dinnerware Set', 'Smart LED Desk Lamp', 'Compact Air Purifier', 'Kitchen Storage Bundle'],
                'short' => 'Home essentials with useful variant options.',
                'description' => 'A home product for everyday organization, comfort, and practical upgrades.',
                'category_slugs' => ['home-garden', 'kitchen-essentials', 'home-decor'],
                'price_min' => 399,
                'price_max' => 2799,
                'price_step' => 80,
                'variants' => $this->packageVariants(['Compact', 'Standard', 'Premium', 'Family']),
                'images' => [
                    'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1494438639946-1ebd1d20bf85?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'code' => 'BEA',
                'family' => 'Beauty',
                'names' => ['Hydration Skincare Set', 'Vitamin C Serum Duo', 'Daily Sunscreen Pack', 'Aloe Recovery Kit'],
                'short' => 'Beauty and wellness bundles with skin-type variants.',
                'description' => 'A self-care product for skincare routines, gifting, and wellness promotions.',
                'category_slugs' => ['beauty-health', 'skincare', 'wellness'],
                'price_min' => 249,
                'price_max' => 1899,
                'price_step' => 65,
                'variants' => $this->skinVariants(),
                'images' => [
                    'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
            [
                'code' => 'FIT',
                'family' => 'Fitness',
                'names' => ['Adjustable Dumbbell Pair', 'Yoga Starter Kit', 'Resistance Band Set', 'Insulated Cycling Bottle'],
                'short' => 'Sports and outdoor products for active lifestyles.',
                'description' => 'A fitness product with practical options for home training and outdoor activity.',
                'category_slugs' => ['sports-outdoors', 'fitness-gear', 'cycling'],
                'price_min' => 199,
                'price_max' => 2499,
                'price_step' => 90,
                'variants' => $this->packageVariants(['Light', 'Medium', 'Heavy', 'Pro']),
                'images' => [
                    'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1599058917212-d750089bc07e?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1200&q=80',
                    'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=1200&q=80',
                ],
            ],
        ];
    }

    private function productTitle(array $template, int $index): string
    {
        return $template['names'][$index % count($template['names'])] . ' ' . ['Lite', 'Plus', 'Max', 'Select', 'Core'][$index % 5];
    }

    private function priceFor(array $template, int $index): float
    {
        $range = $template['price_max'] - $template['price_min'];
        return round($template['price_min'] + (($index * 137) % max(1, $range)), 2);
    }

    private function approvalFor(int $index): ProductApprovalStatus
    {
        if ($index % 19 === 0) {
            return ProductApprovalStatus::REJECTED;
        }

        if ($index % 13 === 0) {
            return ProductApprovalStatus::PENDING;
        }

        return ProductApprovalStatus::APPROVED;
    }

    private function pickMany(array $items, int $offset, int $count): array
    {
        $picked = [];
        $total = count($items);

        for ($i = 0; $i < min($count, $total); $i++) {
            $picked[] = $items[($offset + $i) % $total];
        }

        return $picked;
    }

    private function reviewBody(string $productTitle, int $offset): string
    {
        return [
            "{$productTitle} matched the seeded photos and worked well for testing checkout flows.",
            "Good value and clear offer terms. The variants make the product detail page feel realistic.",
            "Useful demo product with enough data to test reviews, likes, and claims.",
        ][$offset % 3];
    }

    private function bannerFor(string $category, int $index): string
    {
        $banners = [
            'electronics' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=1600&q=80',
            'fashion' => 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=1600&q=80',
            'food' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1600&q=80',
            'home' => 'https://images.unsplash.com/photo-1513161455079-7dc1de15ef3e?auto=format&fit=crop&w=1600&q=80',
            'beauty' => 'https://images.unsplash.com/photo-1522338242992-e1a54906a8da?auto=format&fit=crop&w=1600&q=80',
            'sports' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=1600&q=80',
            'misc' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=1600&q=80',
        ];

        return $banners[$category] ?? $banners[array_keys($banners)[$index % count($banners)]];
    }

    private function storeProfiles(): array
    {
        return [
            ['name' => 'Cairo Tech Market', 'slug' => 'cairo-tech-market', 'city' => 'Cairo', 'category' => 'electronics'],
            ['name' => 'Alex Style House', 'slug' => 'alex-style-house', 'city' => 'Alexandria', 'category' => 'fashion'],
            ['name' => 'Giza Brunch Club', 'slug' => 'giza-brunch-club', 'city' => 'Giza', 'category' => 'food'],
            ['name' => 'Delta Home Goods', 'slug' => 'delta-home-goods', 'city' => 'Mansoura', 'category' => 'home'],
            ['name' => 'Nile Beauty Lab', 'slug' => 'nile-beauty-lab', 'city' => 'Cairo', 'category' => 'beauty'],
            ['name' => 'Maadi Fitness Gear', 'slug' => 'maadi-fitness-gear', 'city' => 'Cairo', 'category' => 'sports'],
            ['name' => 'Nasr City Electronics', 'slug' => 'nasr-city-electronics', 'city' => 'Cairo', 'category' => 'electronics'],
            ['name' => 'Heliopolis Wardrobe', 'slug' => 'heliopolis-wardrobe', 'city' => 'Cairo', 'category' => 'fashion'],
            ['name' => 'Zamalek Coffee Deals', 'slug' => 'zamalek-coffee-deals', 'city' => 'Cairo', 'category' => 'food'],
            ['name' => 'October Living Store', 'slug' => 'october-living-store', 'city' => '6th of October', 'category' => 'home'],
        ];
    }

    private function colorVariants(array $colors): array
    {
        return array_map(fn(string $color) => [
            'title' => $color,
            'summary' => "Color: {$color}",
            'attributes' => [['color', Str::lower($color)]],
        ], $colors);
    }

    private function shoeVariants(): array
    {
        return [
            ['title' => 'Black / 41', 'summary' => 'Color: Black, Size: 41', 'attributes' => [['color', 'black'], ['size', '41']]],
            ['title' => 'Blue / 42', 'summary' => 'Color: Blue, Size: 42', 'attributes' => [['color', 'blue'], ['size', '42']]],
            ['title' => 'White / 43', 'summary' => 'Color: White, Size: 43', 'attributes' => [['color', 'white'], ['size', '43']]],
            ['title' => 'Grey / 44', 'summary' => 'Color: Grey, Size: 44', 'attributes' => [['color', 'grey'], ['size', '44']]],
        ];
    }

    private function packageVariants(array $packages): array
    {
        return array_map(fn(string $package) => [
            'title' => $package,
            'summary' => "Package: {$package}",
            'attributes' => [['package', Str::lower($package)]],
        ], $packages);
    }

    private function skinVariants(): array
    {
        return [
            ['title' => 'Normal Skin', 'summary' => 'Skin type: Normal', 'attributes' => [['skin_type', 'normal']]],
            ['title' => 'Sensitive Skin', 'summary' => 'Skin type: Sensitive', 'attributes' => [['skin_type', 'sensitive']]],
            ['title' => 'Dry Skin', 'summary' => 'Skin type: Dry', 'attributes' => [['skin_type', 'dry']]],
            ['title' => 'Oily Skin', 'summary' => 'Skin type: Oily', 'attributes' => [['skin_type', 'oily']]],
        ];
    }
}
