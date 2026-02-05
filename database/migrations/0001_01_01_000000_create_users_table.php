<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary key (UUID)
            $table->char('id', 36)->primary()->default(DB::raw('(UUID())'));

            // Auth
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->string('phone_number', 20)->nullable()->unique();

            // Verification
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();

            // Status & activity
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->string('shard_key', 50)->nullable();
            // Multi-device & security
            $table->rememberToken();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('last_ip', 45)->nullable();

            // Social login
            $table->string('provider', 50)->nullable();
            $table->string('provider_id', 255)->nullable();

            // Localization
            $table->string('language', 10)->default('ar');
            $table->string('timezone', 50)->default('UTC');

            // Timestamps & soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('email', 'idx_email');
            $table->index('phone_number', 'idx_phone');
            $table->index(['provider', 'provider_id'], 'idx_provider');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index('shard_key', 'idx_shard_key');

        });

        // Schema::create('password_reset_tokens', function (Blueprint $table) {
        //     $table->string('email')->primary();
        //     $table->string('token');
        //     $table->timestamp('created_at')->nullable();
        // });

        Schema::create('user_points', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');

            // Relation
            $table->char('user_id', 36)->unique();

            // Balances
            $table->integer('current_balance')->default(0);
            $table->integer('lifetime_earned')->default(0);
            $table->integer('lifetime_spent')->default(0);

            // Timestamps
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('sessions', function (Blueprint $table) {
            // Primary key (UUID)
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            // Session data
            $table->string('token')->unique();
            $table->text('user_agent')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('payload')->nullable();
            // Optional device type (recommended here, not in users)
            $table->string('device_type')->nullable();

            // Activity & lifecycle
            $table->unsignedInteger('last_activity')->index();
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('revoked_at')->nullable(); // manual / forced logout
            $table->string('revoked_reason')->nullable(); // optional
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'expires_at'], 'idx_user_expires');
            $table->index('token', 'idx_token');

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_points');
        // Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
