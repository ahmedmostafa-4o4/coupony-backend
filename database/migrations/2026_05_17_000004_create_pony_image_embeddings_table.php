<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pony_image_embeddings')) {
            return;
        }

        Schema::create('pony_image_embeddings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_image_id')->unique();
            $table->json('embedding');
            $table->text('caption')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->foreign('product_image_id')
                ->references('id')
                ->on('product_images')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pony_image_embeddings');
    }
};
