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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_network')->nullable()->after('payment_reference'); // e.g. ethereum, polygon
            $table->unsignedBigInteger('payment_chain_id')->nullable()->after('payment_network'); // e.g. 1, 137
            $table->string('payment_currency', 20)->nullable()->after('payment_chain_id'); // e.g. ETH, USDC, MATIC
            $table->string('payer_wallet_address')->nullable()->after('payment_currency');
            // payment_reference stores transaction hash when payment_method = blockchain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_network',
                'payment_chain_id',
                'payment_currency',
                'payer_wallet_address',
            ]);
        });
    }
};
