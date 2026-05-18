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
        Schema::create('notifications', function (Blueprint $table) {
            // Primary key
            $table->bigIncrements('id');

            // Relation
            $table->uuid('user_id');

            // Notification content
            $table->string('type');

            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();

            // Delivery
            $table->string('channel');
            $table->enum('status', ['pending', 'sent', 'failed', 'read'])
                ->default('pending');

            // Polymorphic-ish reference
            $table->string('reference_type', 50)->nullable();
            $table->char('reference_id', 36)->nullable();

            // Lifecycle
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index('created_at', 'idx_created');
            $table->index(['user_id', 'read_at'], 'idx_unread');

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
        Schema::dropIfExists('notifications');
    }
};
