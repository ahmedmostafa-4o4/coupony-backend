<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_point_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('user_id', 36);
            $table->char('admin_user_id', 36)->nullable();
            $table->char('store_id', 36)->nullable();
            $table->char('offer_claim_id', 36)->nullable();
            $table->string('type');
            $table->integer('points');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->string('reason');
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('admin_user_id');
            $table->index('store_id');
            $table->index('offer_claim_id');
            $table->index('type');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('admin_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();

            $table->foreign('offer_claim_id')
                ->references('id')
                ->on('offer_claims')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_point_transactions');
    }
};
