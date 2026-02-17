<?php
// database/migrations/xxxx_xx_xx_create_roles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();;
            $table->string('description')->nullable();
            $table->string('title')->nullable();
            $table->string('icon')->nullable(); 
            $table->timestamps();
            $table->softDeletes();
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
