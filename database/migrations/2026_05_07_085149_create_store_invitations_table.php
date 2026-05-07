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
        Schema::create('store_invitations', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id');
            $table->char('invited_by_user_id', 36);
            $table->char('invitee_user_id', 36);
            $table->string('role');
            $table->json('permissions')->nullable();
            $table->string('status')->default('pending');
            $table->text('message')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamps();

            $table->foreign('store_id')->references('id')->on('stores')->cascadeOnDelete();
            $table->foreign('invited_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('invitee_user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['store_id', 'invitee_user_id', 'status']);
            $table->index(['invitee_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_invitations');
    }
};
