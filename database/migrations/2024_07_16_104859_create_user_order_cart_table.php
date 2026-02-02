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
        if (!Schema::hasTable('user_order_cart')) {
            Schema::create('user_order_cart', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('user_id');
                $table->bigInteger('store_id');
                $table->Integer('service_cat_id')->nullable();
                $table->tinyInteger('order_add_by')->default(0)->comment('0:admin,1:user');
                $table->bigInteger('product_id');
                $table->bigInteger('quantity')->nullable();
                $table->bigInteger('size_id')->nullable();
                $table->string('option_id', 191)->nullable();
                $table->string('topping_id', 191)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_order_cart');
    }
};
