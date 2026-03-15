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
        Schema::create('otps', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');

            // Relation (nullable for pre-login OTPs)
            $table->uuid('user_id')->nullable();

            // OTP data
            $table->string('phone_or_email');

            $table->string('otp_hash');
            $table->string('purpose');

            // Optional channel
            $table->string('channel');

            $table->enum('status', [
                'pending',
                'verified',
                'expired',
                'blocked'
            ])->default('pending');

            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);

            // Lifecycle
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('phone_or_email', 'idx_phone_email');
            $table->index('purpose', 'idx_purpose');
            $table->index('status', 'idx_status');
            $table->index('expires_at', 'idx_expires');

            // Foreign key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_otps');
    }
};
