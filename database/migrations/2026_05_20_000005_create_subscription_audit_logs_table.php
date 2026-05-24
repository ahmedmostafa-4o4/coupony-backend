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
        Schema::create('subscription_audit_logs', function (Blueprint $table) {
            $table->char('id', 36)->primary();
            $table->char('store_id', 36);
            $table->char('subscription_id', 36)->nullable();
            $table->string('event_type');
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('store_id');
            $table->index('subscription_id');
            $table->index('event_type');
            $table->index('created_at');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();

            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_audit_logs');
    }
};
