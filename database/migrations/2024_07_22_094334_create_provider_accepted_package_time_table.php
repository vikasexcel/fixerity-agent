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
        if (!Schema::hasTable('provider_accepted_package_time')) {
            Schema::create('provider_accepted_package_time', function (Blueprint $table) {
                $table->id();
                $table->integer('provider_id');
                $table->integer('order_id');
                $table->string('date', 191);
                $table->string('time', 191);
                $table->time('book_start_time')->nullable();
                $table->time('book_end_time')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_accepted_package_time');
    }
};
