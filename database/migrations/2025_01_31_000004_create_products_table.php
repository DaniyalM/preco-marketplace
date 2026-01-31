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
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            
            // Pricing (base price, variants can override)
            $table->decimal('base_price', 12, 2);
            $table->decimal('compare_at_price', 12, 2)->nullable(); // Original price for discounts
            $table->decimal('cost_price', 12, 2)->nullable(); // For profit calculation
            
            // Inventory (when no variants)
            $table->boolean('track_inventory')->default(true);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->boolean('allow_backorder')->default(false);
            
            // Product Type
            $table->boolean('has_variants')->default(false);
            $table->enum('product_type', ['physical', 'digital', 'service'])->default('physical');
            
            // Physical product attributes
            $table->decimal('weight', 10, 3)->nullable(); // kg
            $table->decimal('length', 10, 2)->nullable(); // cm
            $table->decimal('width', 10, 2)->nullable();  // cm
            $table->decimal('height', 10, 2)->nullable(); // cm
            
            // Status & Visibility
            $table->enum('status', ['draft', 'pending_review', 'active', 'inactive', 'rejected'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            
            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            
            // Stats
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('sold_count')->default(0);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['vendor_id', 'status']);
            $table->index(['category_id', 'status']);
            $table->index(['status', 'is_featured']);
            $table->fullText(['name', 'short_description']);
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
