<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_hours', function (Blueprint $table) {
            $table->id();

            $table->uuid('store_id');

            // 0 = Sunday, 6 = Saturday
            $table->unsignedTinyInteger('day_of_week');
            $table->time('open_time');
            $table->time('close_time');

            $table->boolean('is_closed')->default(false);

            $table->unique(['store_id', 'day_of_week'], 'unique_store_day');

            $table->timestamps();



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
        Schema::dropIfExists('store_hours');
    }
};
