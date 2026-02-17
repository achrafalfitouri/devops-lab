<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_note_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('(UUID())'));
            $table->uuid('quote_id');
            $table->uuid('return_note_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('quote_id')->references('id')->on('quotes');
            $table->foreign('return_note_id')->references('id')->on('return_notes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_note_quotes');
    }
};
