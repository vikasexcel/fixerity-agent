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
        if (!Schema::hasTable('running_service')) {
            Schema::create('running_service', function (Blueprint $table) {
                $table->bigInteger('provider_id')->unsigned();
                $table->bigInteger('user_id')->unsigned();
                $table->bigInteger('service_cat_id')->unsigned();
                $table->bigInteger('booking_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('running_service');
    }
};
