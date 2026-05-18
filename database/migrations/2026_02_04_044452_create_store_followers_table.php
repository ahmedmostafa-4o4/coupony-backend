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
        Schema::create('store_followers', function (Blueprint $table) {
            $table->id();

            $table->uuid('user_id');
            $table->uuid('store_id');

            $table->boolean('notification_enabled')->default(true);
            $table->timestamp('followed_at')->useCurrent();

            $table->unique(['user_id', 'store_id'], 'unique_user_store');

            $table->index('user_id', 'idx_user');
            $table->index('store_id', 'idx_store');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

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
        Schema::dropIfExists('store_followers');
    }
};
