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
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->float('amount')->nullable();
            $table->boolean('is_taxable')->nullable();
            $table->float('tax_amount')->nullable();
            $table->float('final_amount')->nullable();
            $table->string('total_phrase')->nullable();
            $table->enum('status', [ 'Brouillon', 'Rejeté', 'En attente','Validé','Terminé'])->nullable();            $table->text('delivery_comment')->nullable();
            $table->uuid('output_note_id')->nullable();
            $table->foreign('output_note_id')->references('id')->on('output_notes');
            $table->uuid('order_receipt_id')->nullable();
            $table->foreign('order_receipt_id')->references('id')->on('order_receipts');

            // $table->uuid('delivery_user_id');
            // $table->foreign('delivery_user_note')->references('id')->on('delivery_notes');
            $table->uuid('client_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('quote_id')->nullable();
            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
