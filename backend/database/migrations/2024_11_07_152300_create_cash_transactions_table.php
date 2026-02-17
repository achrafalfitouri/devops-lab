<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashTransactionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->float('amount')->nullable();;
            $table->string('name')->nullable();
            $table->date('date')->nullable();
            $table->string('comment')->nullable();
            $table->boolean('balance_reset')->nullable()->default(false);
            $table->uuid('user_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('target_user_id')->nullable();
            $table->uuid('target_cash_register_id')->nullable();
            $table->uuid('cash_register_id')->nullable();
            $table->uuid('cash_transaction_type_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('cash_register_id')->references('id')->on('cash_registers');
            $table->foreign('cash_transaction_type_id')->references('id')->on('cash_transaction_types');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
}
