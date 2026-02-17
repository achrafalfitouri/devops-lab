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
        Schema::create('refund_note_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('description');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('undiscounted_amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('amount', 10, 2);
            $table->integer('order')->nullable();
            $table->string('status')->nullable();
            
            $table->uuid('production_note_id')->nullable();
            $table->uuid('refund_note_id')->nullable();
            
           
            $table->foreign('production_note_id')
                  ->references('id')->on('production_notes');
        
            $table->foreign('refund_note_id')
                  ->references('id')->on('refund_notes');
               
        
            $table->timestamps();
            $table->softDeletes();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_note_items');
    }
};
