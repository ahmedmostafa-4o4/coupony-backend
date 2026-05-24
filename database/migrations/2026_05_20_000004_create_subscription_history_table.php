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
        Schema::create('subscription_history', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('store_id', 36);
            $table->char('plan_id', 36);
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->enum('status', ['active', 'expired', 'refunded', 'failed', 'cancelled']);
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->char('payment_session_id', 36)->nullable();
            $table->timestamps();

            $table->index('store_id');
            $table->index('status');
            $table->index('created_at');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();

            $table->foreign('plan_id')
                ->references('id')
                ->on('subscription_plans')
                ->restrictOnDelete();

            $table->foreign('payment_session_id')
                ->references('id')
                ->on('payment_sessions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_history');
    }
};
