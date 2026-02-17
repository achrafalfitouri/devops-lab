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
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->date('validity_date')->nullable();;
            $table->float('amount')->nullable();
            $table->boolean('is_taxable')->nullable();;
            $table->float('tax_amount')->nullable();;
            $table->string('status')->nullable();
            $table->float('final_amount')->nullable();

            $table->string('total_phrase')->nullable();
            $table->text('quote_comment')->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
