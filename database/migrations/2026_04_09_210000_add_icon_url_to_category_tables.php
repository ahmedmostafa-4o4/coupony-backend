<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'icon_url')) {
                $table->string('icon_url')->nullable()->after('description');
            }
        });

        Schema::table('store_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('store_categories', 'icon_url')) {
                $table->string('icon_url')->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'icon_url')) {
                $table->dropColumn('icon_url');
            }
        });

        Schema::table('store_categories', function (Blueprint $table) {
            if (Schema::hasColumn('store_categories', 'icon_url')) {
                $table->dropColumn('icon_url');
            }
        });
    }
};
