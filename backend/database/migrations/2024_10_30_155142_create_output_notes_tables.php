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
        Schema::create('output_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->float('amount')->nullable();
            $table->boolean('is_taxable')->nullable();
            $table->float('tax_amount')->nullable();
            $table->float('final_amount')->nullable();
            $table->string('total_phrase')->nullable();
            $table->enum('status', ['Terminé', 'Brouillon', 'Rejeté', 'En attente', 'Validé'])->nullable();
            $table->text('output_comment')->nullable();
            $table->date('output_date')->nullable();

            $table->uuid('client_id')->nullable();;
            $table->uuid('user_id')->nullable();
            $table->uuid('production_note_id')->nullable();
            // $table->uuid('output_user_id')->nullable();
            $table->uuid('quote_id')->nullable();

            // Foreign key constraints

            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('production_note_id')->references('id')->on('production_notes');
            // $table->foreign('output_user_id')->references('id')->on('users');

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
        Schema::dropIfExists('output_notes');
    }
};
