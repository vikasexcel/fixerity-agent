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
        if (!Schema::hasTable('user_refer_history')) {
            Schema::create('user_refer_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('refer_id');
                $table->double('user_discount');
                $table->tinyInteger('user_discount_type');
                $table->double('refer_discount');
                $table->tinyInteger('refer_discount_type');
                $table->tinyInteger('user_status');
                $table->tinyInteger('refer_status');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_refer_history');
    }
};
