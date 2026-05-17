<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pony_product_embeddings')) {
            return;
        }

        Schema::create('pony_product_embeddings', function (Blueprint $table) {
            $table->uuid('product_id')->primary();
            $table->json('text_embedding');
            $table->json('image_embedding')->nullable();
            $table->unsignedInteger('source_revision_no')->default(0);
            $table->string('model_version')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pony_product_embeddings');
    }
};
