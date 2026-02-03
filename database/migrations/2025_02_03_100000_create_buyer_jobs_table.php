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
        if (!Schema::hasTable('buyer_jobs')) {
            Schema::create('buyer_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('budget_min', 12, 2)->nullable();
                $table->decimal('budget_max', 12, 2)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->unsignedInteger('service_category_id')->nullable();
                $table->unsignedInteger('sub_category_id')->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('long', 10, 7)->nullable();
                $table->string('status', 20)->default('open')->comment('open, matched, completed');
                $table->json('priorities')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_jobs');
    }
};
