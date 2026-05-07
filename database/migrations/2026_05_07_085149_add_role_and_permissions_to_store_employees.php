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
        Schema::table('store_employees', function (Blueprint $table) {
            $table->string('role')->default('store_employee')->after('user_id');
            $table->json('permissions')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_employees', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
        });
    }
};
