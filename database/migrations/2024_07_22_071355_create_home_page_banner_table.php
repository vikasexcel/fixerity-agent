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
        if (!Schema::hasTable('home_page_banner')) {
            Schema::create('home_page_banner', function (Blueprint $table) {
                $table->id();
                $table->integer('service_id')->default(0);
                $table->tinyInteger('type')->default(1)->comment('0:homepage slider,1:homepage banner');
                $table->string('service_name', 255)->nullable();
                $table->string('banner_image', 255)->nullable();
                $table->enum('status', ['1', '0'])->default(1)->comment('1=yes,0=no');
                $table->string('name', 151)->nullable();
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
                $table->text('description')->nullable();
                $table->string('ja_description', 191)->nullable();
                $table->string('pt_description', 191)->nullable();
                $table->string('vi_description', 191)->nullable();
                $table->string('he_description', 191)->nullable();
                $table->string('de_description', 191)->nullable();
                $table->string('es_description', 191)->nullable();
                $table->string('fr_description', 191)->nullable();
                $table->string('ko_description', 191)->nullable();
                $table->string('zh_description', 191)->nullable();
                $table->string('fil_description', 191)->nullable();
                $table->string('ar_description', 191)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_page_banner');
    }
};
