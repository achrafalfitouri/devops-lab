<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_receipt_items', function (Blueprint $table) {
            $table->uuid('quote_id')->nullable();
            $table->foreign('quote_id')->references('id')->on('quotes');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_receipt_items');
    }
};
