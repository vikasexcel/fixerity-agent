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
        if (!Schema::hasTable('required_documents')) {
            Schema::create('required_documents', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->unsignedInteger('service_cat_id');
                $table->tinyInteger('status')->comment('0=off,1=on');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('required_documents');
    }
};
