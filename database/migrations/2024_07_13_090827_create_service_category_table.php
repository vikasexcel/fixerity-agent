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
        if (!Schema::hasTable('service_category')) {
            Schema::create('service_category', function (Blueprint $table) {
                $table->id();
                $table->string('name', 30);
                $table->string('slug', 30);
                $table->string('ja_name', 191)->nullable();
                $table->string('pt_name', 191)->nullable();
                $table->string('vi_name', 191)->nullable();
                $table->string('he_name', 191)->nullable();
                $table->string('de_name', 191)->nullable();
                $table->string('es_name', 191)->nullable();
                $table->string('fr_name', 191)->nullable();
                $table->string('ko_name', 191)->nullable();
                $table->string('zh_name', 191)->nullable();
                $table->string('fil_name', 191)->nullable();
                $table->string('ar_name', 191)->nullable();
                $table->string('icon_name', 35);
                $table->string('banner_image', 255)->nullable();
                $table->integer('display_order')->default(0);
                $table->tinyInteger('category_type')->default(3)->comment('1=transport, 2=store, 3=others-part-1, 4=others-part-2, 5= transport-part-2');
                $table->Integer('is_sub_cat_flow')->default(0)->comment('0-default flow(food-delivery),1-sub-cat flow(grocery-flow)');
                $table->tinyInteger('status')->default(1)->comment('1=on, 0=off');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_category');
    }
};
