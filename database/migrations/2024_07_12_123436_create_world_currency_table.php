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
        if (!Schema::hasTable('world_currency')) {
            Schema::create('world_currency', function (Blueprint $table) {
                $table->id();
                $table->string('currency_name', 50);
                $table->double('ratio');
                $table->string('currency_code', 50)->nullable();
                $table->string('symbol', 10);
                $table->tinyInteger('status')->default(1);
                $table->tinyInteger('default_currency')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('world_currency');
    }
};
