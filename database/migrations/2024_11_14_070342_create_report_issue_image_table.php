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
        // Check if the table does not exist then create
        if (!Schema::hasTable('report_issue_image')) {
            Schema::create('report_issue_image', function (Blueprint $table) {
                $table->id();
                $table->integer('report_issue_id')->length(11);
                $table->string('image', 255);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_issue_image');
    }
};
