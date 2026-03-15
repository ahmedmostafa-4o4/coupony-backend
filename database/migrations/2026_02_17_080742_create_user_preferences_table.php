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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();

            $table->uuid('user_id')->unique();

            $table->boolean('email_marketing')->default(true);
            $table->boolean('email_order_updates')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->boolean('push_notifications')->default(true);

            $table->char('preferred_currency', 3)->default('USD');
            $table->char('preferred_language', 2)->default('en');
            $table->string('preferred_payment_method', 50)->nullable();

            $table->boolean('enable_personalized_recommendations')->default(true);
            $table->boolean('browsing_history_tracking')->default(true);

            $table->boolean('show_profile_publicly')->default(false);
            $table->boolean('allow_data_sharing_for_analytics')->default(true);

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
