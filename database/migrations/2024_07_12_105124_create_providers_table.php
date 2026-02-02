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
        if (!Schema::hasTable('providers')) {
            Schema::create('providers', function (Blueprint $table) {
                $table->id();
                $table->string('first_name', 30);
                $table->string('last_name', 255)->nullable();
                $table->string('email', 191)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('web_verified_at')->nullable();
                $table->string('contact_number', 15)->nullable();
                $table->tinyInteger('provider_type')->default(0)->comment('1:store,2:driver,3:provider');
                $table->string('avatar', 191)->nullable();
                $table->string('password', 191)->nullable();
                $table->string('login_id', 191)->nullable();
                $table->string('login_type', 10)->default('email');
                $table->double('service_radius')->nullable();
                $table->tinyInteger('gender')->default(0)->comment('1=male, 2=female');
                $table->string('access_token', 191)->nullable();
                $table->string('device_token', 191)->nullable();
                $table->string('country_code', 10)->nullable();
                $table->string('currency', 10)->nullable();
                $table->string('language', 10)->nullable();
                $table->tinyInteger('status')->default(3)->comment('0=pending, 1=activate, 2=blocked, 3=not-apply');
                $table->dateTime('last_active')->useCurrent();
                $table->tinyInteger('login_device')->default(1)->comment('1 :Android,2:ios,3:flutter android,4:flutter ios');
                $table->integer('is_register')->unsigned()->default(0)->comment('0-not-register, 1-register');
                $table->integer('is_default_user')->default(0)->comment('0:mean regular verify ,1:mean verify by 1234 otp');
                $table->tinyInteger('fix_user_show')->default(0)->comment('0:Providers can be deleted and regularly verified. ,1:Providers cannot be deleted and verified by 1234 otp.');
                $table->string('app_version', 50)->nullable();
                $table->timestamp('blocked_at')->nullable();
                $table->string('remember_token', 191)->nullable();
                $table->tinyInteger('completed_step')->default(0);
                $table->string('ip_address', 51)->nullable();
                $table->string('time_zone', 51)->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
