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
        Schema::table('general_settings', function (Blueprint $table) {
            $table->double('min_cashout')->default(0.00)->comment('minimum amount required for cash_out')->after('auto_settle_wallet');
            $table->double('max_cashout')->default(0.00)->comment('maximum amount required for cash_out')->after('min_cashout');
            $table->double('provider_min_amount')->default(0.00)->comment("minimum amount required in provider's wallet")->after('wallet_payment');
            $table->tinyInteger('is_otp_verification')->default(0)->comment('0=not-verify(1234),1=verify')->after('is_apple_login');
            $table->tinyInteger('otp_method')->default(0)->comment('0=default,1=twilio')->after('is_otp_verification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn('min_cashout');
            $table->dropColumn('max_cashout');
            $table->dropColumn('provider_min_amount');
        });
    }
};
