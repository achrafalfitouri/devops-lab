<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_documents', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('(UUID())'));
            $table->uuid('order_receipt_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->uuid('payment_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('order_receipt_id')->references('id')->on('order_receipts');
            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->foreign('payment_id')->references('id')->on('payments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_documents');
    }
};
