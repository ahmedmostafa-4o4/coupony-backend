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
        Schema::create('user_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('role_id');
            $table->uuid('store_id')->nullable();
            $table->timestamp('granted_at')->useCurrent();
            $table->uuid('granted_by_user_id')->nullable();
            $table->time('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'store_id'], 'idx_user_store');
            $table->unique(['user_id', 'role_id', 'store_id'], 'unique_user_role_store');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
            $table->foreign('granted_by_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
