<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'rating_avg')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('rating_avg', 3, 2)->default(0)->after('redemption_count');
                $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            });
        }

        if (! Schema::hasTable('product_comments')) {
            Schema::create('product_comments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('product_id');
                $table->char('user_id', 36);
                $table->uuid('parent_id')->nullable();
                $table->char('review_user_id', 36)->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('body')->nullable();
                $table->enum('status', ['visible', 'hidden'])->default('visible');
                $table->timestamp('hidden_at')->nullable();
                $table->char('hidden_by', 36)->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['product_id', 'review_user_id']);
                $table->index(['product_id', 'parent_id', 'status']);
                $table->index('user_id');

                $table->foreign('product_id')
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                $table->foreign('parent_id')
                    ->references('id')
                    ->on('product_comments')
                    ->cascadeOnDelete();

                $table->foreign('review_user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                $table->foreign('hidden_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('product_comment_likes')) {
            Schema::create('product_comment_likes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('comment_id');
                $table->char('user_id', 36);
                $table->timestamps();

                $table->unique(['comment_id', 'user_id']);
                $table->index('user_id');

                $table->foreign('comment_id')
                    ->references('id')
                    ->on('product_comments')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_comment_likes');
        Schema::dropIfExists('product_comments');

        if (Schema::hasColumn('products', 'rating_avg')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['rating_avg', 'rating_count']);
            });
        }
    }
};
