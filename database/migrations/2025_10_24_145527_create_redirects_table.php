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
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');          // Product 
            $table->unsignedBigInteger('model_id');
            $table->string('from_slug');           // old
            $table->string('to_slug');             // new
            $table->timestamps();

            $table->index(['model_type', 'from_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
