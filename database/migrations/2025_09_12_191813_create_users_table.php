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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('rol', ['filtro', 'estadistica', 'banco', 'admin', 'super_admin']);
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->onDelete('set null');
            $table->boolean('activo')->default(true);
            $table->rememberToken();
            $table->timestamps();

            // Índices para optimización
            $table->index(['activo', 'rol']);
            $table->index(['equipo_id', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
