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
        Schema::create('payment_sessions', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('store_id', 36);
            $table->char('plan_id', 36);
            $table->enum('billing_cycle', ['monthly', 'yearly']);
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EGP');
            $table->enum('status', ['pending', 'paid', 'failed', 'expired']);
            $table->string('paymob_order_id')->nullable();
            $table->string('paymob_transaction_id')->nullable();
            $table->text('payment_url')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index('paymob_order_id');
            $table->index('expires_at');

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
        Schema::dropIfExists('payment_sessions');
    }
};
