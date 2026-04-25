<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_revisions')) {
            return;
        }

        $hasReviewFields = Schema::hasColumn('product_revisions', 'review_fields');
        $hasRequestedChanges = Schema::hasColumn('product_revisions', 'requested_changes');

        if (! $hasReviewFields || ! $hasRequestedChanges) {
            Schema::table('product_revisions', function (Blueprint $table) use ($hasReviewFields, $hasRequestedChanges) {
                if (! $hasReviewFields) {
                    $table->json('review_fields')->nullable()->after('payload');
                }

                if (! $hasRequestedChanges) {
                    $table->json('requested_changes')->nullable()->after('review_fields');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_revisions')) {
            return;
        }

        $hasReviewFields = Schema::hasColumn('product_revisions', 'review_fields');
        $hasRequestedChanges = Schema::hasColumn('product_revisions', 'requested_changes');

        if (! $hasReviewFields && ! $hasRequestedChanges) {
            return;
        }

        Schema::table('product_revisions', function (Blueprint $table) use ($hasReviewFields, $hasRequestedChanges) {
            if ($hasRequestedChanges) {
                $table->dropColumn('requested_changes');
            }

            if ($hasReviewFields) {
                $table->dropColumn('review_fields');
            }
        });
    }
};
