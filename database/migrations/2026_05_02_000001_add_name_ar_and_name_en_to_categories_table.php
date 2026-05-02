<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name');
            }

            if (!Schema::hasColumn('categories', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
        });

        if (Schema::hasColumn('categories', 'name')) {
            if (Schema::hasColumn('categories', 'name_ar')) {
                DB::table('categories')
                    ->whereNull('name_ar')
                    ->update(['name_ar' => DB::raw('name')]);
            }

            if (Schema::hasColumn('categories', 'name_en')) {
                DB::table('categories')
                    ->whereNull('name_en')
                    ->update(['name_en' => DB::raw('name')]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'name_ar')) {
                $table->dropColumn('name_ar');
            }

            if (Schema::hasColumn('categories', 'name_en')) {
                $table->dropColumn('name_en');
            }
        });
    }
};
