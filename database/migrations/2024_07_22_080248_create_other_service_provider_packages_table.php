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
        if (!Schema::hasTable('other_service_provider_packages')) {
            Schema::create('other_service_provider_packages', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('provider_service_id');
                $table->unsignedInteger('sub_cat_id');
                $table->unsignedInteger('service_cat_id');
                $table->string('name', 191);
                $table->text('description')->nullable();
                $table->double('price')->default(0.55);
                $table->integer('max_book_quantity')->default(1);
                $table->tinyInteger('status')->default(0)->comment('0=deactivate, 1=activate');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_service_provider_packages');
    }
};
