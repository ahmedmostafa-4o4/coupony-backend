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
        if (! Schema::hasTable('store_employees') || Schema::hasColumn('store_employees', 'address_id')) {
            return;
        }

        Schema::table('store_employees', function (Blueprint $table) {
            $table->unsignedBigInteger('address_id')->nullable()->after('user_id');
            $table->foreign('address_id')->references('id')->on('addresses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('store_employees') || ! Schema::hasColumn('store_employees', 'address_id')) {
            return;
        }

        Schema::table('store_employees', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropColumn('address_id');
        });
    }
};
