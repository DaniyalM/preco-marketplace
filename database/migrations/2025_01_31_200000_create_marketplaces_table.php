<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform (central) table: marketplaces = tenants in MaaS.
 * Each approved marketplace gets a separate tenant database for data isolation.
 */
return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('marketplaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->comment('Primary domain or subdomain key');
            $table->string('email')->nullable();
            $table->string('support_email')->nullable();

            // Status: pending_kyc → kyc_under_review → approved | rejected
            $table->enum('status', [
                'pending_kyc',
                'kyc_under_review',
                'approved',
                'rejected',
                'suspended',
            ])->default('pending_kyc');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Tenant database config (set on approval; encrypted password in app)
            $table->string('db_connection_name')->nullable()->comment('Laravel connection name e.g. tenant_abc');
            $table->string('db_host')->nullable();
            $table->string('db_port', 10)->nullable();
            $table->string('db_database')->nullable();
            $table->string('db_username')->nullable();
            $table->text('db_password_encrypted')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('marketplaces');
    }
};
