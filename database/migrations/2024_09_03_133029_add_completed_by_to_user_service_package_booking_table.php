<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_service_package_booking', function (Blueprint $table) {
            //
            $table->string('completed_by',10)->nullable()->after('cancel_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_service_package_booking', function (Blueprint $table) {
            //
        });
    }
};
