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
            $table->tinyInteger('cash_payment')->default(0)->comment('1:show ,0:hide')->after('wallet_payment');
            $table->tinyInteger('card_payment')->default(0)->comment('1:show ,0:hide')->after('cash_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            //
        });
    }
};
