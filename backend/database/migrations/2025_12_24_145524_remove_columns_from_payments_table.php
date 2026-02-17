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
    Schema::table('payments', function (Blueprint $table) {
        // 1. First, drop the foreign key constraints
        // Using an array allows Laravel to guess the constraint name automatically
        $table->dropForeign(['invoice_id']);
        $table->dropForeign(['order_receipt_id']);

        // 2. Then, drop the actual columns
        $table->dropColumn([
            'effect_number', 
            'wire_transfer_number', 
            'check_number', 
            'invoice_id', 
            'order_receipt_id'
        ]); 
    });
}

public function down(): void
{
    Schema::table('payments', function (Blueprint $table) {
        $table->string('check_number')->nullable();
        $table->string('wire_transfer_number')->nullable();
        $table->string('effect_number')->nullable();
        
        // Re-add UUID columns and restore their foreign key constraints
        $table->uuid('invoice_id')->nullable();
        $table->foreign('invoice_id')->references('id')->on('invoices'); // Adjust table name if different
        
        $table->uuid('order_receipt_id')->nullable();
        $table->foreign('order_receipt_id')->references('id')->on('order_receipts');
    });
}
};
