<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->unsignedInteger('followers_count')->default(0)->after('rating_count');
        });

        // Backfill existing follower counts
        DB::statement('
            UPDATE stores
            SET followers_count = (
                SELECT COUNT(*) FROM store_followers WHERE store_followers.store_id = stores.id
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('followers_count');
        });
    }
};
