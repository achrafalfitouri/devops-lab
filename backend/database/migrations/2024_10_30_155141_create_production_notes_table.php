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
        // Ensure 'orders', 'clients', and 'users' tables are already created with UUID primary keys

        Schema::create('production_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->nullable();
            $table->boolean('is_taxable')->nullable();
            $table->string('production_comment')->nullable();
            $table->enum('status', ['Terminé', 'Retourné',"Brouillon","En cours","Validé", 'Annulé','Perte','A refaire'])->nullable();
            $table->uuid('client_id')->nullable();
            $table->uuid('user_id')->nullable();
            // $table->uuid('production_user_id')->nullable();
            $table->uuid('order_note_id')->nullable();
            $table->uuid('quote_id')->nullable();
            $table->uuid('output_note_id')->nullable();

            // Foreign key constraints
            $table->foreign('client_id')->references('id')->on('clients');
            $table->foreign('user_id')->references('id')->on('users');
            // $table->foreign('production_user_id')->references('id')->on('users');
            $table->foreign('order_note_id')->references('id')->on('order_notes');
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
        Schema::dropIfExists('production_notes');
    }
};
