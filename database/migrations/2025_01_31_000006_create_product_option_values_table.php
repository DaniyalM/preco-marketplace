<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Option values are the actual choices (e.g., "Small", "Medium", "Large" for Size)
     */
    public function up(): void
    {
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->onDelete('cascade');
            $table->string('value'); // e.g., "Small", "Red"
            $table->string('label')->nullable(); // Display label if different from value
            $table->string('color_code')->nullable(); // For color swatches (hex)
            $table->string('image')->nullable(); // For visual options
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique(['product_option_id', 'value']);
            $table->index(['product_option_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_option_values');
    }
};
