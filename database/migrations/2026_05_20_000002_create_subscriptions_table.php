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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('store_id', 36);
            $table->char('plan_id', 36);
            $table->enum('status', ['none', 'trial', 'active', 'grace', 'degraded', 'suspended', 'archived']);
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('grace_period_end')->nullable();
            $table->timestamp('degraded_period_end')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique('store_id');
            $table->index('status');
            $table->index('current_period_end');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();

            $table->foreign('plan_id')
                ->references('id')
                ->on('subscription_plans')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
