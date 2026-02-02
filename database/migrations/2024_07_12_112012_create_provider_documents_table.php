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
        if (!Schema::hasTable('provider_documents')) {
            Schema::create('provider_documents', function (Blueprint $table) {
                $table->id();
                $table->integer('provider_service_id')->unsigned();
                $table->integer('req_document_id')->unsigned();
                $table->string('document_file', 25);
                $table->tinyInteger('status')->default(0)->comment('0=pending,1=approved,2=rejected');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_documents');
    }
};
