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
        if (!Schema::hasTable('restricted_area')) {
            Schema::create('restricted_area', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255)->nullable();
                $table->text('latitude')->nullable();
                $table->text('longitude')->nullable();
                $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('restricted_area');
    }
};
