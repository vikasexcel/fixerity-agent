<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the table does not exist then create
        if (!Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->string('name', 191);
                $table->string('pt_name', 191)->nullable();
                $table->string('vi_name', 191)->nullable();
                $table->string('he_name', 191)->nullable();
                $table->string('de_name', 191)->nullable();
                $table->string('es_name', 191)->nullable();
                $table->string('fr_name', 191)->nullable();
                $table->string('ko_name', 191)->nullable();
                $table->string('ja_name', 191)->nullable();
                $table->string('zh_name', 191)->nullable();
                $table->string('fil_name', 191)->nullable();
                $table->string('ar_name', 191)->nullable();
                $table->tinyInteger('status')->default(0)->comment('0=Off,1=On');
                $table->longText('description')->nullable();
                $table->longText('pt_description')->nullable();
                $table->longText('vi_description')->nullable();
                $table->longText('he_description')->nullable();
                $table->longText('de_description')->nullable();
                $table->longText('es_description')->nullable();
                $table->longText('fr_description')->nullable();
                $table->longText('ko_description')->nullable();
                $table->longText('ja_description')->nullable();
                $table->longText('zh_description')->nullable();
                $table->longText('fil_description')->nullable();
                $table->longText('ar_description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
