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
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique(); // TSHRT-BLK-M-001
            $table->boolean('is_active')->default(true);
            $table->timestamp('published_at')->nullable();

            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 3)->default('EUR');

            $table->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnUpdate()->nullOnDelete();

            $table->foreignId('primary_category_id')
                ->nullable()
                ->constrained('categories')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('description')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
