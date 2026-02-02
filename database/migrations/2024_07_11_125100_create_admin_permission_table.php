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
        if (!Schema::hasTable('admin_permission')) {
            Schema::create('admin_permission', function (Blueprint $table) {
                $table->id();
                $table->integer('admin_id')->unsigned();
                $table->integer('module_id')->unsigned();
                $table->string('permission', 191);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_permission');
    }
};
