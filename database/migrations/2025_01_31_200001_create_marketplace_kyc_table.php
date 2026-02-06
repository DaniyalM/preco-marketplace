<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform (central) table: KYC for marketplaces (tenants).
 * Super Admin reviews and approves/rejects; on approval, tenant DB is provisioned.
 */
return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('marketplace_kyc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->onDelete('cascade');

            $table->string('legal_name');
            $table->string('tax_id')->nullable();
            $table->string('business_type')->nullable();
            $table->enum('id_type', ['passport', 'national_id', 'drivers_license', 'business_license']);
            $table->string('id_document_front')->nullable(); // Required before submit
            $table->string('id_document_back')->nullable();
            $table->string('proof_of_address')->nullable();
            $table->string('business_registration')->nullable();
            $table->string('tax_certificate')->nullable();

            $table->enum('status', ['draft', 'pending', 'under_review', 'approved', 'rejected', 'expired'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();

            $table->date('document_expiry_date')->nullable();
            $table->boolean('is_resubmission')->default(false);
            $table->unsignedInteger('submission_count')->default(1);

            $table->timestamps();

            $table->index(['marketplace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('marketplace_kyc');
    }
};
