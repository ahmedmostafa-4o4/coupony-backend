<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        if (Schema::hasTable('products')) {
            $productIds = DB::table('products')
                ->leftJoin('product_offers', 'products.id', '=', 'product_offers.product_id')
                ->whereNull('product_offers.product_id')
                ->pluck('products.id');

            foreach ($productIds as $productId) {
                DB::table('product_offers')->insert([
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'product_id' => $productId,
                    'type' => 'fixed',
                    'status' => 'inactive',
                    'label' => 'Backfilled default offer',
                    'starts_at' => null,
                    'ends_at' => null,
                    'claim_expiration_minutes' => null,
                    'fixed_amount' => 0,
                    'percentage_value' => null,
                    'max_discount' => null,
                    'buy_qty' => null,
                    'get_qty' => null,
                    'allow_mix_buy_variants' => false,
                    'allow_mix_reward_variants' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_offer_variant_targets');
        Schema::dropIfExists('product_offers');
    }
};
