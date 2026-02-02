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
        if (!Schema::hasTable('card_details')) {
            Schema::create('card_details', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->nullable();
                $table->integer('card_provider_type')->default(0)->comment('0:user,1:store,2:driver,3:provider');
                $table->string('holder_name', 40)->nullable();
                $table->string('card_number', 40);
                $table->integer('month');
                $table->integer('year');
                $table->integer('cvv');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_details');
//        Schema::table('card_details',function (Blueprint $table){
//            Schema::dropColumns('cvv');
//        });
    }
};
