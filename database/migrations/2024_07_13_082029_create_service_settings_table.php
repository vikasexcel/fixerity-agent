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
        if (!Schema::hasTable('service_settings')) {
            Schema::create('service_settings', function (Blueprint $table) {
                $table->id();
                $table->integer('service_cat_id')->unsigned();
                $table->tinyInteger('provider_accept_timeout')->nullable();
                $table->tinyInteger('provider_search_radius')->nullable();
                $table->double('tax')->nullable();
                $table->double('admin_commission')->nullable();
                $table->double('shopper_admin_commission')->nullable();
                $table->double('cancel_charge')->nullable();
                $table->integer('delivery_charge')->nullable();
                $table->tinyInteger('surcharge_timings_status')->default(0)->nullable();
                $table->tinyInteger('status')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_settings');
    }
};
