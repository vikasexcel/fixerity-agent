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
        if (!Schema::hasTable('user_package_booking_quantity')) {
            Schema::create('user_package_booking_quantity', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('package_id');
                $table->unsignedInteger('order_id');
                $table->integer('num_of_items');
                $table->string('package_name', 191);
                $table->string('sub_category_name', 191)->nullable();
                $table->double('price_for_one');
                $table->string('ja_sub_category_name', 191)->nullable();
                $table->string('pt_sub_category_name', 191)->nullable();
                $table->string('vi_sub_category_name', 191)->nullable();
                $table->string('he_sub_category_name', 191)->nullable();
                $table->string('de_sub_category_name', 191)->nullable();
                $table->string('es_sub_category_name', 191)->nullable();
                $table->string('fr_sub_category_name', 191)->nullable();
                $table->string('ko_sub_category_name', 191)->nullable();
                $table->string('zh_sub_category_name', 191)->nullable();
                $table->string('fil_sub_category_name', 191)->nullable();
                $table->string('ar_sub_category_name', 191)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_package_booking_quantity');
    }
};
