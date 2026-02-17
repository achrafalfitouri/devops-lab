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
        Schema::create('cash_register_daily_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->float('balance')->nullable();;
            $table->float('inflows')->nullable();;
            $table->float('outflows')->nullable();;
            $table->uuid('cash_register_id')->nullable();
            $table->foreign('cash_register_id')->references('id')->on('cash_registers');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_daily_balances');    }
};
