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
        Schema::create('notification_broadcasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->uuid('admin_id')->nullable();
            
            $table->string('title');
            $table->text('message');
            $table->json('channels'); // e.g. ['in_app', 'push', 'email']
            
            $table->json('target_roles')->nullable(); // e.g. ['all'], ['customer', 'store_owner']
            $table->json('target_user_ids')->nullable(); // specific array of user UUIDs

            $table->string('status')->default('pending'); // pending, processing, completed, failed
            
            $table->integer('total_sent')->default(0);
            $table->integer('total_failed')->default(0);
            
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('admin_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_broadcasts');
    }
};
