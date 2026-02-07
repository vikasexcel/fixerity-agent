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
        Schema::table('provider_services', function (Blueprint $table) {
            $table->decimal('min_price', 10, 2)->nullable()->after('service_cat_id');
            $table->decimal('max_price', 10, 2)->nullable()->after('min_price');
            $table->integer('deadline_in_days')->nullable()->after('max_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price', 'deadline_in_days']);
        });
    }
};
