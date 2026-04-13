<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->ensureCategoriesTableMatchesCatalogShape();

        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('store_id');
                $table->string('title');
                $table->string('slug');
                $table->string('short_description', 500)->nullable();
                $table->text('description')->nullable();
                $table->decimal('base_price', 12, 2);
                $table->decimal('compare_at_price', 12, 2)->nullable();
                $table->char('currency', 3)->default('EGP');
                $table->string('sku', 100)->nullable();
                $table->enum('status', ['active', 'inactive'])->default('inactive');
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->unsignedInteger('published_revision_no')->default(0);
                $table->timestamp('approved_at')->nullable();
                $table->char('approved_by', 36)->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->char('rejected_by', 36)->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('admin_notes')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('sale_count')->default(0);
                $table->unsignedInteger('redemption_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['store_id', 'slug']);
                $table->unique(['store_id', 'sku']);
                $table->index(['store_id', 'status']);

                $table->foreign('store_id')
                    ->references('id')
                    ->on('stores')
                    ->cascadeOnDelete();

                $table->foreign('approved_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign('rejected_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('product_variants')) {
            Schema::create('product_variants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('product_id');
                $table->string('title');
                $table->string('option_summary')->nullable();
                $table->string('sku', 100)->nullable();
                $table->string('barcode', 100)->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->decimal('compare_at_price', 12, 2)->nullable();
                $table->char('currency', 3)->default('EGP');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->enum('inventory_mode', ['tracked', 'unlimited'])->default('unlimited');
                $table->unsignedInteger('stock_qty')->nullable();
                $table->unsignedInteger('low_stock_threshold')->nullable();
                $table->boolean('allow_backorder')->default(false);
                $table->unsignedInteger('sale_count')->default(0);
                $table->unsignedInteger('redemption_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['product_id', 'sku']);
                $table->index(['product_id', 'is_active', 'sort_order']);

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('product_variant_attributes')) {
            Schema::create('product_variant_attributes', function (Blueprint $table) {
                $table->id();
                $table->uuid('variant_id');
                $table->string('attribute_name', 100);
                $table->string('attribute_value', 255);
                $table->integer('sort_order')->default(0);
                $table->timestamp('created_at')->nullable();

                $table->unique(['variant_id', 'attribute_name']);
                $table->index('variant_id');

                $table->foreign('variant_id')
                    ->references('id')
                    ->on('product_variants')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->uuid('product_id');
                $table->text('image_url');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_primary')->default(false);
                $table->timestamp('created_at')->nullable();

                $table->index('product_id');

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('product_offers')) {
            Schema::create('product_offers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('product_id')->unique();
                $table->enum('type', ['fixed', 'percentage', 'buy_x_get_y']);
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->string('label')->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->unsignedInteger('claim_expiration_minutes')->nullable();
                $table->decimal('fixed_amount', 12, 2)->nullable();
                $table->decimal('percentage_value', 5, 2)->nullable();
                $table->decimal('max_discount', 12, 2)->nullable();
                $table->unsignedInteger('buy_qty')->nullable();
                $table->unsignedInteger('get_qty')->nullable();
                $table->boolean('allow_mix_buy_variants')->default(false);
                $table->boolean('allow_mix_reward_variants')->default(false);
                $table->timestamps();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('product_offer_variant_targets')) {
            Schema::create('product_offer_variant_targets', function (Blueprint $table) {
                $table->id();
                $table->uuid('offer_id');
                $table->uuid('variant_id');
                $table->enum('role', ['buy', 'reward']);
                $table->timestamps();

                $table->unique(['offer_id', 'variant_id', 'role']);

                $table->foreign('offer_id')
                    ->references('id')
                    ->on('product_offers')
                    ->cascadeOnDelete();

                $table->foreign('variant_id')
                    ->references('id')
                    ->on('product_variants')
                    ->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('product_revisions')) {
            Schema::create('product_revisions', function (Blueprint $table) {
                $table->id();
                $table->uuid('product_id');
                $table->unsignedInteger('revision_no');
                $table->enum('action', ['create', 'update', 'resubmit']);
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->unsignedInteger('base_revision_no')->nullable();
                $table->char('submitted_by', 36);
                $table->timestamp('submitted_at');
                $table->char('reviewed_by', 36)->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('admin_notes')->nullable();
                $table->json('payload');
                $table->timestamps();

                $table->unique(['product_id', 'revision_no']);
                $table->index(['product_id', 'status']);

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();

                $table->foreign('submitted_by')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                $table->foreign('reviewed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('offer_claims')) {
            Schema::create('offer_claims', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('user_id', 36);
                $table->uuid('store_id');
                $table->uuid('product_id');
                $table->uuid('offer_id');
                $table->enum('status', ['active', 'redeemed', 'expired', 'cancelled'])->default('active');
                $table->string('claim_token', 100)->unique();
                $table->string('qr_code_token', 100)->unique();
                $table->json('offer_snapshot');
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('redeemed_at')->nullable();
                $table->char('redeemed_by', 36)->nullable();
                $table->timestamps();

                $table->index(['store_id', 'status']);
                $table->index(['product_id', 'status']);

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                $table->foreign('store_id')
                    ->references('id')
                    ->on('stores')
                    ->cascadeOnDelete();

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();

                $table->foreign('offer_id')
                    ->references('id')
                    ->on('product_offers')
                    ->cascadeOnDelete();

                $table->foreign('redeemed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('product_categories')) {
            Schema::create('product_categories', function (Blueprint $table) {
                $table->id();
                $table->uuid('product_id');
                $table->unsignedBigInteger('category_id');
                $table->timestamp('created_at')->nullable();

                $table->unique(['product_id', 'category_id']);

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();

                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('offer_claims');
        Schema::dropIfExists('product_revisions');
        Schema::dropIfExists('product_offer_variant_targets');
        Schema::dropIfExists('product_offers');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variant_attributes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
    }

    private function ensureCategoriesTableMatchesCatalogShape(): void
    {
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique('slug', 'uq_categories_slug');
                $table->index('parent_id', 'idx_categories_parent');
                $table->index(['is_active', 'sort_order'], 'idx_categories_active_sort');
                $table->foreign('parent_id', 'fk_categories_parent')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });

            return;
        }

        $hasName = Schema::hasColumn('categories', 'name');
        $hasSlug = Schema::hasColumn('categories', 'slug');
        $hasDescription = Schema::hasColumn('categories', 'description');
        $hasParentId = Schema::hasColumn('categories', 'parent_id');
        $hasSortOrder = Schema::hasColumn('categories', 'sort_order');
        $hasIsActive = Schema::hasColumn('categories', 'is_active');

        Schema::table('categories', function (Blueprint $table) use (
            $hasName,
            $hasSlug,
            $hasDescription,
            $hasParentId,
            $hasSortOrder,
            $hasIsActive
        ) {
            if (!$hasName) {
                $table->string('name')->default('');
            }

            if (!$hasSlug) {
                $table->string('slug')->nullable();
            }

            if (!$hasDescription) {
                $table->text('description')->nullable();
            }

            if (!$hasParentId) {
                $table->unsignedBigInteger('parent_id')->nullable();
            }

            if (!$hasSortOrder) {
                $table->integer('sort_order')->default(0);
            }

            if (!$hasIsActive) {
                $table->boolean('is_active')->default(true);
            }
        });

        if (!Schema::hasIndex('categories', 'uq_categories_slug')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique('slug', 'uq_categories_slug');
            });
        }

        if (!Schema::hasIndex('categories', 'idx_categories_parent')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('parent_id', 'idx_categories_parent');
            });
        }

        if (!Schema::hasIndex('categories', 'idx_categories_active_sort')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index(['is_active', 'sort_order'], 'idx_categories_active_sort');
            });
        }

        $foreignKeyNames = array_column(Schema::getForeignKeys('categories'), 'name');

        if (!in_array('fk_categories_parent', $foreignKeyNames, true) && !in_array('categories_parent_id_foreign', $foreignKeyNames, true)) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreign('parent_id', 'fk_categories_parent')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }
    }
};
