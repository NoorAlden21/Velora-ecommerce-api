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
        Schema::create('option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')
                ->constrained('options')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name');                   // Black, White, 42, M...
            $table->string('slug');                   // black, white, 42, m...
            $table->string('code')->nullable();       // for colors: #000000 
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['option_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_values');
    }
};
