<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the table does not exist then create
        if (!Schema::hasTable('provider_bank_details')) {
            Schema::create('provider_bank_details', function (Blueprint $table) {
                $table->id();
                $table->integer('provider_id')->unsigned();
                $table->string('account_number', 191);
                $table->string('holder_name', 191);
                $table->string('bank_name', 191);
                $table->string('bank_location', 191);
                $table->string('payment_email', 191);
                $table->string('bic_swift_code', 191);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_bank_details');
    }
};
