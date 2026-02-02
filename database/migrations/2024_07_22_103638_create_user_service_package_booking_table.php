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
        if (!Schema::hasTable('user_service_package_booking')) {
            Schema::create('user_service_package_booking', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('provider_id');
                $table->integer('package_id')->nullable();
                $table->unsignedInteger('service_cat_id');
                $table->bigInteger('order_no')->nullable();
                $table->string('booking_time_zone', 51)->nullable();
                $table->tinyInteger('order_type')->default(0)->comment('0=book-now,1=schedule');
                $table->dateTime('service_date_time')->useCurrent();
                $table->string('service_date', 50)->nullable();
                $table->string('service_time', 50)->nullable();
                $table->time('book_start_time')->nullable();
                $table->time('book_end_time')->nullable();
                $table->integer('book_slot_time')->nullable();
                $table->double('total_item_cost')->default(0.00);
                $table->double('tax')->default(0.00);
                $table->double('tip')->default(0.00);
                $table->double('extra_amount')->default(0.00)->comment('extra charge taken from the provider after completing the order');
                $table->double('refer_discount')->default(0.00);
                $table->double('total_pay')->default(0.00);
                $table->double('admin_commission')->default(0.00);
                $table->double('provider_amount')->default(0.00);
                $table->string('user_name', 100)->nullable();
                $table->tinyInteger('status')->default(1)->comment('1=pending, 2=confirmed, 3=scheduled-confirm, 4=rejected, 5=cancelled, 6=ongoing, 7=arrived, 8=processing, 9=completed, 10=failed');
                $table->string('provider_name', 60)->nullable();
                $table->text('delivery_address')->nullable();
                $table->string('lat_long', 191)->nullable();
                $table->string('flat_no', 191)->nullable();
                $table->string('landmark', 191)->nullable();
                $table->integer('payment_type')->default(0)->comment('1=cash ,2=card, 3=wallet');
                $table->tinyInteger('payment_status')->default(0)->comment('0=pending, 1=completed');
                $table->string('transaction_id', 191)->nullable();
                $table->tinyInteger('promo_code')->default(0);
                $table->text('remark')->nullable();
                $table->string('cancel_by', 10)->nullable();
                $table->text('cancel_reason')->nullable();
                $table->double('cancel_charge')->default(0.00);
                $table->double('refund_amount')->default(0.00);
                $table->tinyInteger('user_refund_status')->default(0);
                $table->text('order_package_list')->nullable();
                $table->tinyInteger('user_rating_status')->default(0);
                $table->tinyInteger('provider_rating_status')->default(0);
                $table->tinyInteger('provider_pay_settle_status')->default(0);
                $table->tinyInteger('order_from')->default(0);
                $table->tinyInteger('select_provider_location')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_service_package_booking');
    }
};
