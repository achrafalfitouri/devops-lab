<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderReceiptsTable extends Migration
{
    public function up()
    {
        Schema::create('order_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->date('due_date')->nullable();
            $table->float('amount')->nullable();
            $table->float('discount')->nullable();
            $table->float('discounted_amount')->nullable();
            $table->boolean('is_taxable')->default(false)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0)->nullable();
            $table->decimal('final_amount', 15, 2)->nullable();
            $table->string('total_phrase')->nullable();
            $table->text('receipt_comment')->nullable();
            $table->float('payed_amount')->nullable()->default(0);
            $table->string('status')->nullable();
            $table->uuid('client_id')->nullable();;
            $table->uuid('user_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('quote_id')->nullable();
            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_receipts');
    }
}
