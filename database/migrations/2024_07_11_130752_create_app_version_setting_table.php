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
        if (!Schema::hasTable('app_version_setting')) {
            Schema::create('app_version_setting', function (Blueprint $table) {
                $table->id();
                $table->integer('app_type')->default(0)->comment('0:user,1:store,2:driver,3:provider');
                $table->integer('version_code')->nullable();
                $table->string('version_name', 50)->nullable();
                $table->integer('forcefully_type')->default(0)->comment('0-default,1-forcefully');
                $table->integer('app_device_type')->comment('0-d,1 :Android,2:ios,3:flutter android,4:flutter ios');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_version_setting');
    }
};
