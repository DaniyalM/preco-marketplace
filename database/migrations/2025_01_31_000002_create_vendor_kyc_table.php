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
        Schema::create('vendor_kyc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->onDelete('cascade');
            
            // Personal/Business Information
            $table->string('legal_name');
            $table->string('tax_id')->nullable(); // SSN/EIN/VAT
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            
            // Document Type & Uploads
            $table->enum('id_type', ['passport', 'national_id', 'drivers_license', 'business_license']);
            $table->string('id_document_front');
            $table->string('id_document_back')->nullable();
            $table->string('proof_of_address')->nullable(); // Utility bill, bank statement
            $table->string('business_registration')->nullable();
            $table->string('tax_certificate')->nullable();
            
            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();
            $table->string('bank_swift_code')->nullable();
            
            // Verification Status
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'expired'])->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewed_by')->nullable(); // Admin Keycloak ID
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Expiration tracking
            $table->date('document_expiry_date')->nullable();
            $table->boolean('is_resubmission')->default(false);
            $table->unsignedInteger('submission_count')->default(1);
            
            $table->timestamps();
            
            $table->index(['vendor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_kyc');
    }
};
