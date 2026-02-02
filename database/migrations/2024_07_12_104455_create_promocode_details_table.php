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
        if (!Schema::hasTable('promocode_details')) {
            Schema::create('promocode_details', function (Blueprint $table) {
                $table->id();
                $table->integer('service_cat_id')->unsigned();
                $table->string('promo_code', 191);
                $table->double('discount_amount');
                $table->tinyInteger('discount_type')->comment('1=amount 2=percentage');
                $table->double('min_order_amount')->nullable();
                $table->double('max_discount_amount')->nullable();
                $table->integer('coupon_limit')->default(0)->comment('0 = no any limit');
                $table->integer('usage_limit')->default(1)->comment('per user');
                $table->integer('total_usage')->default(0)->comment('no of user used code');
                $table->dateTime('expiry_date_time');
                $table->text('description')->nullable();
                $table->tinyInteger('status')->default(1)->comment('1=on, 0=off');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocode_details');
    }
};
