<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('option_id')
                ->constrained('options')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('option_value_id')
                ->constrained('option_values')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            // one value for each option
            $table->unique(['product_variant_id', 'option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
    }
};
