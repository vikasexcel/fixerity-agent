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
        if (!Schema::hasTable('cash_out')) {
            Schema::create('cash_out', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id');
                $table->string('user_name', 20);
                $table->double('amount');
                $table->tinyInteger('status')->default(0)->comment('0-pending,1-approved,2-rejected');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_out');
    }
};
