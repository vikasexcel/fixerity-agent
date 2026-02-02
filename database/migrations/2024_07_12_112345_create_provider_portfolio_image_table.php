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
        if (!Schema::hasTable('provider_portfolio_image')) {
            Schema::create('provider_portfolio_image', function (Blueprint $table) {
                $table->id();
                $table->integer('service_cat_id');
                $table->integer('provider_id')->default(0);
                $table->string('image', 255);
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
        Schema::dropIfExists('provider_portfolio_image');
    }
};
