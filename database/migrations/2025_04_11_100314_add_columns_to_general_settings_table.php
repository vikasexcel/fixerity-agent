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
        Schema::table('general_settings', function (Blueprint $table) {
            //
            $table->string('server_map_key', '191')->nullable()->after('map_key');
            $table->integer('matrix_api_route_preference')->after('server_map_key')->comment('0:TRAFFIC UNAWARE(not consider traffic),1:TRAFFIC AWARE(consider traffic)')->default(0);
            $table->string('map_lat', 51)->default(0)->after('matrix_api_route_preference');
            $table->string('map_long', 51)->default(0)->after('map_lat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            //
            $table->dropColumn('server_map_key');
            $table->dropColumn('matrix_api_route_preference');
            $table->dropColumn('map_lat');
            $table->dropColumn('map_long');
        });
    }
};
