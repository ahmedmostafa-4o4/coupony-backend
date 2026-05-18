<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('store_categories', 'image_category')) {
                $table->string('image_category')->nullable()->after('icon_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_categories', function (Blueprint $table) {
            if (Schema::hasColumn('store_categories', 'image_category')) {
                $table->dropColumn('image_category');
            }
        });
    }
};
