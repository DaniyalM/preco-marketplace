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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('keycloak_user_id')->unique(); // Keycloak subject ID
            $table->string('email')->unique();
            $table->string('business_name');
            $table->string('slug')->unique();
            $table->string('business_type')->nullable(); // individual, company, partnership
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->string('website')->nullable();
            
            // Address
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('US');
            
            // Status
            $table->enum('status', ['pending', 'under_review', 'approved', 'suspended', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Settings
            $table->decimal('commission_rate', 5, 2)->default(10.00); // Platform commission %
            $table->boolean('is_featured')->default(false);
            $table->json('settings')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
