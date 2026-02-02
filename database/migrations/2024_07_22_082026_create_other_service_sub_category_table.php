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
        if (!Schema::hasTable('other_service_sub_category')) {
            Schema::create('other_service_sub_category', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('service_cat_id');
                $table->string('name', 50);
                $table->string('icon_name', 50);
                $table->tinyInteger('status')->default(1)->comment('1=on, 0=off');
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
                $table->string('ar_name', 191);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_service_sub_category');
    }
};
