<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_message_usages', function (Blueprint $table): void {
            $table->id();
            $table->date('usage_date');
            $table->string('subject_type', 20);
            $table->char('subject_id', 36);
            $table->unsignedInteger('used')->default(0);
            $table->json('reservation_tokens')->nullable();
            $table->timestamps();

            $table->unique(
                ['usage_date', 'subject_type', 'subject_id'],
                'ai_usage_subject_day_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_message_usages');
    }
};
