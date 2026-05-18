<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('store_comments')) {
            Schema::create('store_comments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('store_id');
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

                $table->unique(['store_id', 'review_user_id']);
                $table->index(['store_id', 'parent_id', 'status']);
                $table->index('user_id');

                $table->foreign('store_id')
                    ->references('id')
                    ->on('stores')
                    ->cascadeOnDelete();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                $table->foreign('parent_id')
                    ->references('id')
                    ->on('store_comments')
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

        if (! Schema::hasTable('store_comment_likes')) {
            Schema::create('store_comment_likes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('comment_id');
                $table->char('user_id', 36);
                $table->timestamps();

                $table->unique(['comment_id', 'user_id']);
                $table->index('user_id');

                $table->foreign('comment_id')
                    ->references('id')
                    ->on('store_comments')
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
        Schema::dropIfExists('store_comment_likes');
        Schema::dropIfExists('store_comments');
    }
};
