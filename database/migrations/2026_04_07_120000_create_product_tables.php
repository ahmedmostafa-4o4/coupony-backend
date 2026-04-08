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
                $table->enum('product_type', ['standard', 'service', 'couponable_item'])->default('standard');
                $table->decimal('base_price', 12, 2);
                $table->decimal('compare_at_price', 12, 2)->nullable();
                $table->char('currency', 3)->default('EGP');
                $table->string('sku', 100)->nullable();
                $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft');
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
