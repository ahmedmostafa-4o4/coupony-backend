<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants') || Schema::hasColumn('product_variants', 'original_price')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->nullable()->after('barcode');
        });

        DB::table('product_variants')
            ->whereNull('original_price')
            ->update([
                'original_price' => DB::raw('COALESCE(compare_at_price, price, 0)'),
            ]);

        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('original_price', 12, 2)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_variants') || ! Schema::hasColumn('product_variants', 'original_price')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('original_price');
        });
    }
};
