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
        if (!Schema::hasTable('home_page_spot_light')) {
            Schema::create('home_page_spot_light', function (Blueprint $table) {
                $table->id();
                $table->integer('service_cat_id')->nullable();
                $table->integer('provider_id')->nullable();
                $table->tinyInteger('status')->default(1)->comment('1:active,0:inactive');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_page_spot_light');
    }
};
