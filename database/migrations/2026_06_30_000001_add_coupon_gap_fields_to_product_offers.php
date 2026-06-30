<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->json('terms_en')->nullable()->after('label');
            $table->json('terms_ar')->nullable()->after('terms_en');
            $table->boolean('branch_only')->default(false)->after('terms_ar');
            $table->unsignedInteger('max_claims_per_user')->nullable()->after('claim_expiration_minutes');
            $table->unsignedInteger('max_total_claims')->nullable()->after('max_claims_per_user');
        });
    }

    public function down(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->dropColumn([
                'terms_en',
                'terms_ar',
                'branch_only',
                'max_claims_per_user',
                'max_total_claims',
            ]);
        });
    }
};
