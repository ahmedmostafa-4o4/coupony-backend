<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addressables', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('address_id')
                ->constrained('addresses')
                ->cascadeOnDelete();

            $table->uuidMorphs('owner'); // owner_type (string) + owner_id (BIGINT)


            $table->string('label', 50)
                ->default('home');

            $table->boolean('is_default_shipping')->default(false);
            $table->boolean('is_default_billing')->default(false);

            $table->timestamps();

            $table->unique(
                ['owner_type', 'owner_id', 'address_id'],
                'unique_owner_address'
            );

            $table->index(['owner_type', 'owner_id'], 'idx_owner');
            $table->index('address_id', 'idx_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addressables');
    }
};
