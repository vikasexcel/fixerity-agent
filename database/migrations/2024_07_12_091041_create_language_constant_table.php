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
        if (!Schema::hasTable('language_constant')) {
            Schema::create('language_constant', function (Blueprint $table) {
                $table->id();
                $table->string('constant_name', 130)->nullable();
                $table->string('value', 130)->nullable();
                $table->string('ja_value', 191)->nullable();
                $table->string('pt_value', 191)->nullable();
                $table->string('vi_value', 191)->nullable();
                $table->string('he_value', 191)->nullable();
                $table->string('de_value', 191)->nullable();
                $table->string('es_value', 191)->nullable();
                $table->string('fr_value', 191)->nullable();
                $table->string('ko_value', 191)->nullable();
                $table->string('zh_value', 191)->nullable();
                $table->string('fil_value', 191)->nullable();
                $table->string('ar_value', 191)->nullable();
                $table->timestamp('created_at')->useCurrent()->useCurrentOnUpdate();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_constant');
    }
};
