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
        Schema::table('order_receipts', function (Blueprint $table) {
            $table->float('total_to_pay')->nullable()->default(0);

        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->float('total_to_pay')->nullable()->default(0);

        });
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->string('seller')->nullable();
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_receipts', function (Blueprint $table) {
            $table->dropColumn('total_to_pay');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('total_to_pay');
        });
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropColumn('seller');
        });
    }
};
