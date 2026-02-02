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
        if (!Schema::hasTable('user_wallet_transaction')) {
            Schema::create('user_wallet_transaction', function (Blueprint $table) {
                $table->id();
                $table->integer('user_id')->unsigned();
                $table->integer('wallet_provider_type')->default(0)->comment('0:user,1:store,2:driver,3:provider');
                $table->tinyInteger('transaction_type')->comment('1=credit,2=debit');
                $table->double('amount');
                $table->string('order_no', 51);
                $table->string('subject', 191);
                $table->integer('subject_code')->default(0);
                $table->double('remaining_balance')->default(0.00);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_wallet_transaction');
    }
};
