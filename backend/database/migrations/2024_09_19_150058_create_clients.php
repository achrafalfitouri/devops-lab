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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('logo')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('code')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->float('balance')->nullable()->default(0);
            $table->uuid('city_id')->nullable();
            $table->string('address')->nullable();
            $table->string('ice')->nullable();
            $table->string('if')->nullable();
            $table->string('tp')->nullable();
            $table->string('rc')->nullable();
            $table->uuid('client_type_id')->nullable(); 
            $table->uuid('gamut_id')->nullable();
            $table->uuid('status_id')->nullable(); 
            $table->uuid('business_sector_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('set null');
            $table->foreign('client_type_id')->references('id')->on('client_types')->onDelete('set null');
            $table->foreign('gamut_id')->references('id')->on('gamutes')->onDelete('set null');
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('set null');
            $table->foreign('business_sector_id')->references('id')->on('business_sectors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropForeign(['client_type_id']);
            $table->dropForeign(['gamut_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['business_sector_id']);
        });

        Schema::dropIfExists('clients');
    }
};
