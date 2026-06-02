<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id');
            $table->char('requested_by', 36);
            $table->text('image_url');
            $table->string('discount_label', 100);
            $table->string('date_range', 100)->nullable();
            $table->string('cta_label', 100);
            $table->text('terms_of_use');
            $table->timestamp('end_time');
            $table->unsignedInteger('priority')->default(100);
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->char('approved_by', 36)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'end_time', 'priority']);
            $table->index(['status', 'store_id']);

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();

            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('banner_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('banner_id');
            $table->uuid('offer_id');
            $table->timestamps();

            $table->unique(['banner_id', 'offer_id']);
            $table->index('offer_id');

            $table->foreign('banner_id')
                ->references('id')
                ->on('banners')
                ->cascadeOnDelete();

            $table->foreign('offer_id')
                ->references('id')
                ->on('product_offers')
                ->cascadeOnDelete();
        });

        Schema::create('banner_branches', function (Blueprint $table) {
            $table->id();
            $table->uuid('banner_id');
            $table->unsignedBigInteger('address_id');
            $table->timestamps();

            $table->unique(['banner_id', 'address_id']);
            $table->index('address_id');

            $table->foreign('banner_id')
                ->references('id')
                ->on('banners')
                ->cascadeOnDelete();

            $table->foreign('address_id')
                ->references('id')
                ->on('addresses')
                ->cascadeOnDelete();
        });

        Schema::create('banner_likes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('banner_id');
            $table->char('user_id', 36);
            $table->timestamps();

            $table->unique(['banner_id', 'user_id']);
            $table->index('user_id');

            $table->foreign('banner_id')
                ->references('id')
                ->on('banners')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('banner_favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('banner_id');
            $table->char('user_id', 36);
            $table->timestamps();

            $table->unique(['banner_id', 'user_id']);
            $table->index('user_id');

            $table->foreign('banner_id')
                ->references('id')
                ->on('banners')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('banner_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('banner_id');
            $table->char('user_id', 36)->nullable();
            $table->string('platform', 30)->nullable();
            $table->timestamps();

            $table->index(['banner_id', 'created_at']);
            $table->index('user_id');

            $table->foreign('banner_id')
                ->references('id')
                ->on('banners')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('banner_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('banner_id');
            $table->char('user_id', 36);
            $table->uuid('store_id');
            $table->enum('status', ['active', 'redeemed', 'expired', 'cancelled'])->default('active');
            $table->string('claim_token', 100)->unique();
            $table->string('qr_code_token', 100)->unique();
            $table->json('claim_snapshot');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();
            $table->char('redeemed_by', 36)->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['banner_id', 'status']);
            $table->index('user_id');

            $table->foreign('banner_id')
                ->references('id')
                ->on('banners')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();

            $table->foreign('redeemed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_claims');
        Schema::dropIfExists('banner_shares');
        Schema::dropIfExists('banner_favorites');
        Schema::dropIfExists('banner_likes');
        Schema::dropIfExists('banner_branches');
        Schema::dropIfExists('banner_offers');
        Schema::dropIfExists('banners');
    }
};
