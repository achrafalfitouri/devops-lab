<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_credit_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('(UUID())'));
            $table->uuid('quote_id');
            $table->uuid('invoice_credit_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->foreign('invoice_credit_id')->references('id')->on('invoice_credits');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_credit_quotes');
    }
};
