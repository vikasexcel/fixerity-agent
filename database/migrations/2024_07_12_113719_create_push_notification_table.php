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
        if (!Schema::hasTable('push_notification')) {
            Schema::create('push_notification', function (Blueprint $table) {
                $table->id();
                $table->tinyInteger('notification_type')->comment('1= all user & drivers & stores & provider, 2= all users, 3= all drivers, 4= all stores, 5= all providers');
                $table->string('title', 80)->nullable();
                $table->text('message');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('push_notification');
    }
};
