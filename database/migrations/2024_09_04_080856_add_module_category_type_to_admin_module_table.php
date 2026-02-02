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
        Schema::table('admin_module', function (Blueprint $table) {
            //
            $table->string('module_category_type',191)->nullable()->comment('1=transport, 2=store, 3=others-part-1, 4=others-part-2, 5= transport-part-2')->after('seq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_module', function (Blueprint $table) {
            //
        });
    }
};
