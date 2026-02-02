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
        if (!Schema::hasTable('user_address')) {
            Schema::create('user_address', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('address_type', 10)->default('other')->comment('home,work,other');
                $table->text('address');
                $table->string('lat_long', 191);
                $table->string('flat_no', 30)->nullable();
                $table->string('landmark', 30)->nullable();
                $table->tinyInteger('status')->default(1)->comment('1=activate, 0=remove');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_address');
    }
};
