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
            $table->decimal('agent_average_rating', 3, 2)->nullable()->after('deadline_in_days')->comment('Agent config: average rating');
            $table->integer('agent_total_completed_order')->default(0)->after('agent_average_rating')->comment('Agent config: total completed orders');
            $table->integer('agent_num_of_rating')->default(0)->after('agent_total_completed_order')->comment('Agent config: number of ratings');
            $table->tinyInteger('agent_licensed')->default(0)->after('agent_num_of_rating')->comment('Agent config: licensed status (1=yes, 0=no)');
            $table->json('agent_package_list')->nullable()->after('agent_licensed')->comment('Agent config: package list as JSON');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_services', function (Blueprint $table) {
            $table->dropColumn([
                'agent_average_rating',
                'agent_total_completed_order',
                'agent_num_of_rating',
                'agent_licensed',
                'agent_package_list'
            ]);
        });
    }
};
