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
        if (!Schema::hasTable('temp_user_wallet')) {
            Schema::create('temp_user_wallet', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->default(0);
                $table->string('trans_id', 255)->nullable();
                $table->double('amount')->default(0.00);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_user_wallet');
    }
};
