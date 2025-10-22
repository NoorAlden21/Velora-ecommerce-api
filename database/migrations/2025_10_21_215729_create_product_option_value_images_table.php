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
        Schema::create('product_option_value_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('option_value_id')
                ->constrained('option_values')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('image_url');
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(
                ['product_id', 'option_value_id', 'image_url'],
                'povi_prod_val_url_uq' // short name
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_value_images');
    }
};
