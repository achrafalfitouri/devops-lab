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
        Schema::create('recoveries', function (Blueprint $table) {
              $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->date('date')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('recovery_balance', 15, 2)->nullable();
            $table->text('comment')->nullable();
            $table->uuid('payment_type_id')->nullable();
            $table->uuid('client_id')->nullable();
            $table->string('check_number')->nullable();
            $table->string('wire_transfer_number')->nullable();
            $table->string('effect_number')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('payment_type_id')->references('id')->on('payment_types');
            $table->foreign('client_id')->references('id')->on('clients');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recoveries');
    }
};
