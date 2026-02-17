<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->date('date')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('comment')->nullable();
            $table->uuid('payment_type_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->uuid('order_receipt_id')->nullable();

            $table->string('check_number')->nullable();
            $table->string('wire_transfer_number')->nullable();
            $table->string('effect_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_type_id')->references('id')->on('payment_types');
            $table->foreign('order_receipt_id')->references('id')->on('order_receipts');
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('invoice_id')->references('id')->on('invoices');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
