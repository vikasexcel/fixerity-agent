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
        if (!Schema::hasTable('other_service_provider_timings')) {
            Schema::create('other_service_provider_timings', function (Blueprint $table) {
                $table->id();
                $table->integer('provider_id');
                $table->string('day', 191);
                $table->text('open_time_list');
                $table->time('provider_open_time')->nullable();
                $table->time('provider_close_time')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_service_provider_timings');
    }
};
