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
        if (!Schema::hasTable('service_slider_banner')) {
            Schema::create('service_slider_banner', function (Blueprint $table) {
                $table->id();
                $table->integer('service_cat_id');
                $table->tinyInteger('type')->default(1)->comment('1:mean store,2:ondemand');
                $table->integer('store_id')->default(0);
                $table->integer('ondemand_cat_id')->default(0);
                $table->string('banner_image', 255);
                $table->tinyInteger('status')->default(0)->comment('0=Off,1=On');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_slider_banner');
    }
};
