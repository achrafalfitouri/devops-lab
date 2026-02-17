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
        Schema::create('refund_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('discount', 15, 2)->nullable();
            $table->decimal('discounted_amount', 15, 2)->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->decimal('tax_amount', 15, 2)->nullable();
            $table->decimal('final_amount', 15, 2);
            $table->text('total_phrase')->nullable();
            $table->text('refund_comment')->nullable();
            $table->string('status')->nullable();
            $table->string('type')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('user_id')->nullable();
            // $table->uuid('invoice_id')->nullable();
            $table->uuid('quote_id')->nullable();
            $table->uuid('payment_type_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('payment_type_id')->references('id')->on('payment_types')->onDelete('set null');
            // $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_notes', function (Blueprint $table) {
            //
        });
    }
};
