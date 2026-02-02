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
        if (!Schema::hasTable('user_rating')) {
            Schema::create('user_rating', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('provider_id')->nullable();
                $table->unsignedInteger('ride_book_id')->nullable();
                $table->unsignedInteger('product_book_id')->nullable();
                $table->unsignedInteger('package_book_id')->nullable();
                $table->unsignedInteger('rental_book_id')->nullable();
                $table->double('rating');
                $table->string('comment')->nullable();
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
        Schema::dropIfExists('user_rating');
    }
};
