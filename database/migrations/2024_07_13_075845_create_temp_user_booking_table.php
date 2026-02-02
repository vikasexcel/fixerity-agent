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
        if (!Schema::hasTable('temp_user_booking')) {
            Schema::create('temp_user_booking', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('service_cat_id')->nullable();
                $table->bigInteger('booking_id')->nullable();
                $table->string('transaction_id', 191)->nullable();
                $table->double('amount')->default(0.00);
                $table->tinyInteger('payment_method_type')->default(0)->comment('0 = paypal, 1= pay mongo');
                $table->tinyInteger('payment_status')->default(0)->comment('0=not paid, 1=paid');
                $table->integer('resp_code')->nullable();
                $table->string('coupon_ids', 191)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_user_booking');
    }
};
