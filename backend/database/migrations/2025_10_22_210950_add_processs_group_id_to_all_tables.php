<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('order_notes', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('production_notes', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('output_notes', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('order_receipts', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('return_notes', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('refund_notes', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('invoice_credits', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });
        Schema::table('quote_requests', function (Blueprint $table) {
            $table->uuid('process_group_id')->nullable();
        });


    }

    public function down(): void
    {
        // Schema::dropIfExists('delivery_notes');
    }
};
