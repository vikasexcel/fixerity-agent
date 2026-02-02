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
        if (!Schema::hasTable('other_service_provider_details')) {
            Schema::create('other_service_provider_details', function (Blueprint $table) {
                $table->id();
                $table->integer('provider_id');
                $table->double('rating')->default(0.00);
                $table->integer('total_completed_order')->default(0);
                $table->string('address', 191);
                $table->string('flat_no', 100)->nullable();
                $table->string('landmark', 100)->nullable();
                $table->string('lat', 191);
                $table->string('long', 191);
                $table->integer('mail_status')->default(0);
                $table->double('min_order')->default(0.00);
                $table->string('start_time', 50)->default('09:00 AM');
                $table->string('end_time', 50)->default('07:00 PM');
                $table->text('time_list')->nullable();
                $table->tinyInteger('time_slot_status')->default(1);
                $table->tinyInteger('all_day')->default(0);
                $table->tinyInteger('is_allowed_provider_location')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_service_provider_details');
    }
};
