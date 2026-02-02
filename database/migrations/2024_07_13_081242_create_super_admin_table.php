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
        if (!Schema::hasTable('super_admin')) {
            Schema::create('super_admin', function (Blueprint $table) {
                $table->id();
                $table->string('name', 30);
                $table->string('email', 191);
                $table->string('password', 191);
                $table->string('roles', 20);
                $table->integer('is_restrict_admin')->default(0);
                $table->enum('admin_type', ['s', 'g'])->default('g')->comment('s:super admin,g:guest');
                $table->string('access_token', 191)->nullable();
                $table->string('device_token', 255)->nullable();
                $table->string('remember_token', 100)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin');
    }
};
