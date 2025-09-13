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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('accion', 100); // 'crear', 'actualizar', 'eliminar', 'login', etc.
            $table->string('tabla_afectada', 50)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('razon_cambio')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Índices para optimización según el roadmap
            $table->index('usuario_id');
            $table->index('created_at');
            $table->index(['tabla_afectada', 'registro_id']);
            $table->index(['accion', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
