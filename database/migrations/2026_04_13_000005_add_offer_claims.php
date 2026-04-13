<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offer_claims')) {
            return;
        }

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

    public function down(): void
    {
        Schema::dropIfExists('offer_claims');
    }
};
