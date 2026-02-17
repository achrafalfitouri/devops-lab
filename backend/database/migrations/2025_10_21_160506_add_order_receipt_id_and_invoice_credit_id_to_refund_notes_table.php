<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_notes', function (Blueprint $table) {
            $table->uuid('invoice_credit_id')->nullable();
            $table->foreign('invoice_credit_id')->references('id')->on('invoice_credits');
            $table->uuid('order_receipt_id')->nullable();
            $table->foreign('order_receipt_id')->references('id')->on('order_receipts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_notes');
    }
};
