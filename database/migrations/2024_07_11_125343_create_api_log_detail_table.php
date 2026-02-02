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
        if (!Schema::hasTable('api_log_detail')) {
            Schema::create('api_log_detail', function (Blueprint $table) {
                $table->id();
                $table->string('logger_type', 191)->nullable()->comment('0:user,1:store,2:driver,3:provider');
                $table->bigInteger('logger_id')->nullable();
                $table->string('log_api_name', 191)->nullable();
                $table->longText('log_json')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_log_detail');
    }
};
