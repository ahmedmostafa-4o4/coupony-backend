<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_claims', function (Blueprint $table) {
            $table->decimal('revenue_amount', 14, 2)->nullable()->after('redeemed_by');
            $table->char('revenue_currency', 3)->nullable()->after('revenue_amount');
        });
    }

    public function down(): void
    {
        Schema::table('offer_claims', function (Blueprint $table) {
            $table->dropColumn(['revenue_amount', 'revenue_currency']);
        });
    }
};
