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
        Schema::create('document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description')->nullable();
            $table->float('price')->nullable();
            $table->float('order')->nullable();
            $table->float('quantity')->nullable();
            $table->float('undiscounted_amount')->nullable();
            $table->float('discount')->nullable();
            $table->float('amount')->nullable();
            $table->uuid('quote_id')->nullable();
            $table->uuid('production_note_id')->nullable();
            $table->uuid('output_note_id')->nullable();
            $table->uuid('delivery_note_id')->nullable();
            $table->uuid('return_note_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->uuid('order_note_id')->nullable();
            $table->uuid('order_receipt_id')->nullable();
            $table->uuid('invoice_credit_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->foreign('production_note_id')->references('id')->on('production_notes');
            $table->foreign('output_note_id')->references('id')->on('output_notes');
            $table->foreign('delivery_note_id')->references('id')->on('delivery_notes');
            $table->foreign('return_note_id')->references('id')->on('return_notes');
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('invoice_credit_id')->references('id')->on('invoice_credits');
            $table->foreign('order_note_id')->references('id')->on('order_notes');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_items');
    }
};
