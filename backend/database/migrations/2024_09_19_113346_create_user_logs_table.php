<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('user_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('action')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_logs');
    }
};
