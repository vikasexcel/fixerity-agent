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
            $table->tinyInteger('report_chat_history_delete')->default(0)->comment('0:No,1:Yes')->after('max_driver_reassign');
            $table->integer('chat_deletion_days_after_issue_resolution')->default(0)->comment('No of days for chat deletion resolved')->after('report_chat_history_delete');
            $table->integer('min_report_issue_image_upload')->default(0)->after('chat_deletion_days_after_issue_resolution')->comment('Min report issue image upload count');
            $table->integer('max_report_issue_image_upload')->default(0)->after('min_report_issue_image_upload')->comment('Max report issue image upload count');
            $table->string('general_report_issue_icon',50)->nullable()->after('max_report_issue_image_upload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            //
            $table->dropColumn(['report_chat_history_delete']);
            $table->dropColumn(['chat_deletion_days_after_resolution']);
        });
    }
};
