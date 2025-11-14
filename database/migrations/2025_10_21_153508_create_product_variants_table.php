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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('variant_key')->nullable();           // color:black|size:42
            $table->string('sku')->nullable();

            // Overrides
            $table->unsignedBigInteger('price_cents')->nullable();
            $table->string('currency', 3)->nullable();

            $table->integer('stock')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'variant_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
