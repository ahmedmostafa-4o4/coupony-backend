<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $hasProductType = Schema::hasColumn('products', 'product_type');
        $hadApprovalStatusBefore = Schema::hasColumn('products', 'approval_status');
        $hasPublishedRevisionNo = Schema::hasColumn('products', 'published_revision_no');
        $hasApprovedAt = Schema::hasColumn('products', 'approved_at');
        $hasApprovedBy = Schema::hasColumn('products', 'approved_by');
        $hasRejectedAt = Schema::hasColumn('products', 'rejected_at');
        $hasRejectedBy = Schema::hasColumn('products', 'rejected_by');
        $hasRejectionReason = Schema::hasColumn('products', 'rejection_reason');
        $hasAdminNotes = Schema::hasColumn('products', 'admin_notes');

        Schema::table('products', function (Blueprint $table) use (
            $hadApprovalStatusBefore,
            $hasPublishedRevisionNo,
            $hasApprovedAt,
            $hasApprovedBy,
            $hasRejectedAt,
            $hasRejectedBy,
            $hasRejectionReason,
            $hasAdminNotes
        ) {
            if (! $hadApprovalStatusBefore) {
                $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('status');
            }

            if (! $hasPublishedRevisionNo) {
                $table->unsignedInteger('published_revision_no')
                    ->default(0)
                    ->after('approval_status');
            }

            if (! $hasApprovedAt) {
                $table->timestamp('approved_at')->nullable()->after('published_revision_no');
            }

            if (! $hasApprovedBy) {
                $table->char('approved_by', 36)->nullable()->after('approved_at');
            }

            if (! $hasRejectedAt) {
                $table->timestamp('rejected_at')->nullable()->after('approved_by');
            }

            if (! $hasRejectedBy) {
                $table->char('rejected_by', 36)->nullable()->after('rejected_at');
            }

            if (! $hasRejectionReason) {
                $table->text('rejection_reason')->nullable()->after('rejected_by');
            }

            if (! $hasAdminNotes) {
                $table->text('admin_notes')->nullable()->after('rejection_reason');
            }
        });

        if (! $hadApprovalStatusBefore) {
            DB::table('products')->update([
                'approval_status' => DB::raw("
                    CASE
                        WHEN status = 'draft' THEN 'pending'
                        ELSE 'approved'
                    END
                "),
            ]);

            DB::table('products')
                ->where('approval_status', 'approved')
                ->where('published_revision_no', 0)
                ->update([
                    'published_revision_no' => 1,
                    'approved_at' => DB::raw('COALESCE(approved_at, updated_at, created_at)'),
                ]);
        }

        DB::table('products')
            ->whereIn('status', ['draft', 'archived'])
            ->update(['status' => 'inactive']);

        Schema::table('products', function (Blueprint $table) use ($hasProductType) {
            $table->enum('status', ['active', 'inactive'])->default('inactive')->change();

            if ($hasProductType) {
                $table->dropColumn('product_type');
            }
        });

        $this->ensureNullableUserForeignKey('products', 'approved_by', 'products_approved_by_foreign');
        $this->ensureNullableUserForeignKey('products', 'rejected_by', 'products_rejected_by_foreign');
    }

    public function down(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $hasProductType = Schema::hasColumn('products', 'product_type');

        Schema::table('products', function (Blueprint $table) use ($hasProductType) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);

            if (! $hasProductType) {
                $table->enum('product_type', ['standard', 'service', 'couponable_item'])
                    ->default('standard')
                    ->after('description');
            }
        });

        DB::table('products')
            ->where('approval_status', 'pending')
            ->update(['status' => 'draft']);

        Schema::table('products', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'inactive', 'archived'])->default('draft')->change();
            $table->dropColumn([
                'approval_status',
                'published_revision_no',
                'approved_at',
                'approved_by',
                'rejected_at',
                'rejected_by',
                'rejection_reason',
                'admin_notes',
            ]);
        });
    }

    private function ensureNullableUserForeignKey(string $tableName, string $column, string $fallbackName): void
    {
        $foreignKeys = array_column(Schema::getForeignKeys($tableName), 'name');

        if (
            in_array("{$tableName}_{$column}_foreign", $foreignKeys, true)
            || in_array($fallbackName, $foreignKeys, true)
        ) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $fallbackName) {
            $table->foreign($column, $fallbackName)
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
