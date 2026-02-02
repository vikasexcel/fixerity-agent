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
        if (!Schema::hasTable('admin_module')) {
            Schema::create('admin_module', function (Blueprint $table) {
                $table->id();
                $table->integer('parent_id')->default(0);
                $table->string('name', 191);
                $table->string('module_name', 191);
                $table->text('match_url');
                $table->string('route_path', 255);
                $table->string('route_path_arr', 255);
                $table->string('image', 255)->default('icon-home');
                $table->string('module_action', 191);
                $table->integer('seq');
                $table->string('status', 191)->default(1)->comment('0:inactive , 1:active');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_module');
    }
};
