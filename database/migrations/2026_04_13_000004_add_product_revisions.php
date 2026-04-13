<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_revisions')) {
            Schema::create('product_revisions', function (Blueprint $table) {
                $table->id();
                $table->uuid('product_id');
                $table->unsignedInteger('revision_no');
                $table->enum('action', ['create', 'update', 'resubmit']);
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->unsignedInteger('base_revision_no')->nullable();
                $table->char('submitted_by', 36);
                $table->timestamp('submitted_at');
                $table->char('reviewed_by', 36)->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('admin_notes')->nullable();
                $table->json('payload');
                $table->timestamps();

                $table->unique(['product_id', 'revision_no']);
                $table->index(['product_id', 'status']);

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();

                $table->foreign('submitted_by')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                $table->foreign('reviewed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        $defaultSubmitter = DB::table('products')
            ->join('stores', 'stores.id', '=', 'products.store_id')
            ->whereNotNull('stores.owner_user_id')
            ->select('products.id as product_id', 'stores.owner_user_id as owner_user_id')
            ->get();

        foreach ($defaultSubmitter as $row) {
            $exists = DB::table('product_revisions')
                ->where('product_id', $row->product_id)
                ->exists();

            if ($exists) {
                continue;
            }

            $payload = app(\App\Domain\Product\Repositories\ProductRepository::class)
                ->snapshotPayload(\App\Domain\Product\Models\Product::query()->findOrFail($row->product_id));

            DB::table('product_revisions')->insert([
                'product_id' => $row->product_id,
                'revision_no' => max(1, (int) DB::table('products')->where('id', $row->product_id)->value('published_revision_no')),
                'action' => 'create',
                'status' => DB::table('products')->where('id', $row->product_id)->value('published_revision_no') > 0 ? 'approved' : 'pending',
                'base_revision_no' => null,
                'submitted_by' => $row->owner_user_id,
                'submitted_at' => now(),
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
                'admin_notes' => null,
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_revisions');
    }
};
