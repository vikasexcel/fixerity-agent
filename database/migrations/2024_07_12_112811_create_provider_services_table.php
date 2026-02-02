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
        if (!Schema::hasTable('provider_services')) {
            Schema::create('provider_services', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('provider_id')->unsigned();
                $table->integer('service_cat_id')->unsigned();
                $table->tinyInteger('current_status')->default(0)->comment('1=on, 0=off');
                $table->tinyInteger('is_sponsor')->default(0)->comment('1:for dispaly in sponspo in provider panel');
                $table->tinyInteger('status')->default(0)->comment('0=pending, 1=approved, 2=blocked, 3=rejected');
                $table->string('rejected_reason', 200)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
