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
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name', 30)->nullable();
                $table->string('last_name', 20)->nullable();
                $table->string('email', 191)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->string('contact_number', 15)->nullable();
                $table->string('login_type', 10)->nullable()->comment('email,facebook,google,apple');
                $table->string('login_id', 191)->nullable();
                $table->string('password', 191)->nullable();
                $table->string('avatar', 191)->nullable();
                $table->tinyInteger('gender')->default(0)->comment('1=male, 2=female');
                $table->string('invite_code', 191)->nullable();
                $table->bigInteger('access_token')->nullable();
                $table->string('device_token', 191)->nullable();
                $table->string('country_code', 10)->nullable();
                $table->string('currency', 10)->default('$');
                $table->string('language', 10)->default('en');
                $table->tinyInteger('status')->default(1)->comment('1=active, 0=block');
                $table->double('rating')->default(0.00);
                $table->string('emergency_contact', 191)->nullable();
                $table->tinyInteger('login_device')->default(1)->comment('1:Flutter-Android, 2:Flutter-Ios');
                $table->string('app_version', 50)->nullable();
                $table->double('pending_refer_discount')->default(0.00);
                $table->string('remember_token', 100)->nullable();
                $table->integer('is_register')->default(0)->comment('0-not-register, 1-register');
                $table->integer('is_default_user')->default(0)->comment('0:mean regular verify ,1:mean verify by 1234 otp');
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
        Schema::dropIfExists('users');
    }
};
