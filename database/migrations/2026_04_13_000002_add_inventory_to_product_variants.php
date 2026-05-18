<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        $hasInventoryMode = Schema::hasColumn('product_variants', 'inventory_mode');
        $hasStockQty = Schema::hasColumn('product_variants', 'stock_qty');
        $hasLowStockThreshold = Schema::hasColumn('product_variants', 'low_stock_threshold');
        $hasAllowBackorder = Schema::hasColumn('product_variants', 'allow_backorder');

        Schema::table('product_variants', function (Blueprint $table) use (
            $hasInventoryMode,
            $hasStockQty,
            $hasLowStockThreshold,
            $hasAllowBackorder
        ) {
            if (! $hasInventoryMode) {
                $table->enum('inventory_mode', ['tracked', 'unlimited'])
                    ->default('unlimited')
                    ->after('is_active');
            }

            if (! $hasStockQty) {
                $table->unsignedInteger('stock_qty')->nullable()->after('inventory_mode');
            }

            if (! $hasLowStockThreshold) {
                $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock_qty');
            }

            if (! $hasAllowBackorder) {
                $table->boolean('allow_backorder')->default(false)->after('low_stock_threshold');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn([
                'inventory_mode',
                'stock_qty',
                'low_stock_threshold',
                'allow_backorder',
            ]);
        });
    }
};
