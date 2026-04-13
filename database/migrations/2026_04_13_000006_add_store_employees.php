<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_employees')) {
            return;
        }

        Schema::create('store_employees', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id');
            $table->char('user_id', 36);
            $table->timestamps();

            $table->unique(['store_id', 'user_id']);
            $table->index('user_id');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_employees');
    }
};
