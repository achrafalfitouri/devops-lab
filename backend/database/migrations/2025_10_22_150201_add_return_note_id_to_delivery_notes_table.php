<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->uuid('return_note_id')->nullable();
            $table->foreign('return_note_id')->references('id')->on('return_notes');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
