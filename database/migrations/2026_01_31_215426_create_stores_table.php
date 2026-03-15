<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            // Primary key (UUID)
            $table->char('id', 36)
                ->primary()
                ->default(DB::raw('(UUID())'));

            // Owner
            $table->uuid('owner_user_id');

            // Store info
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('logo_url')->nullable();
            $table->text('banner_url')->nullable();

            // Contact & legal
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('tax_id', 100)->nullable();

            // Business rules
            $table->decimal('commission_rate', 5, 4)->default(0.1500);
            $table->enum('status', ['pending', 'active', 'rejected', 'suspended', 'closed'])
                ->default('pending');
            $table->enum('subscription_tier', ['free', 'basic', 'premium', 'enterprise'])
                ->default('free');

            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();

            // Metrics
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);

            // Sharding
            $table->string('shard_key', 50)->nullable();

            // Approval fields
            $table->timestamp('approved_at')->nullable();
            $table->char('approved_by', 36)->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->char('rejected_by', 36)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();

            // Timestamps & soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('owner_user_id');
            // $table->index('status', 'idx_status');
            $table->index('subscription_tier', 'idx_subscription');

            // Foreign keys
            $table->foreign('owner_user_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('rejected_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('store_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('store_id');

            $table->string('document_type');

            $table->string('document_path');

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->char('verified_by', 36)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('store_id');
            $table->index('status');
            $table->index('document_type');

            // Prevent duplicate documents per store
            $table->unique(['store_id', 'document_type']);

            // Foreign key
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
        Schema::dropIfExists('store_verifications');
    }
};
