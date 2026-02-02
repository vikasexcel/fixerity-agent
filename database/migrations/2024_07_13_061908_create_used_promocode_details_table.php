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
        if (!Schema::hasTable('used_promocode_details')) {
            Schema::create('used_promocode_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('service_cat_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('promocode_id');
                $table->string('promocode_name', 191);
                $table->double('discount_amount');
                $table->tinyInteger('status')->default(1)->comment('0:pending,1:apply,2 cancel(return order)');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('used_promocode_details');
    }
};
