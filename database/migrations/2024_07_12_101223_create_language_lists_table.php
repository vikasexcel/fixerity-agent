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
        if (!Schema::hasTable('language_lists')) {
            Schema::create('language_lists', function (Blueprint $table) {
                $table->id();
                $table->string('language_name', 255)->nullable();
                $table->string('language_code', 10)->nullable();
                $table->string('language_flag', 51)->nullable();
                $table->tinyInteger('status')->default(1)->comment('1:mean active, 0:inactive');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('language_lists');
    }
};
