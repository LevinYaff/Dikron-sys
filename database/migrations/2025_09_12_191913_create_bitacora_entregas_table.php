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
        Schema::create('bitacora_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->onDelete('cascade');
            $table->string('folio_boleta', 20);
            $table->enum('tipo_ayuda', ['alimentos', 'ropa', 'medicina', 'pañales', 'leche_formula', 'mixta']);
            $table->enum('estado', ['aprobada', 'en_preparacion', 'lista', 'entregada', 'vencida'])
                  ->default('aprobada');
            $table->string('equipo_responsable', 2); // AJ, BF, CI, etc.

            // Fechas del flujo
            $table->timestamp('fecha_aprobacion')->useCurrent();
            $table->timestamp('fecha_preparacion')->nullable();
            $table->timestamp('fecha_lista')->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->timestamp('fecha_vencimiento')->nullable(); // fecha_preparacion + 7 días

            // Usuarios responsables
            $table->foreignId('usuario_aprobo')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('usuario_preparo')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('usuario_entrego')->nullable()->constrained('users')->onDelete('set null');

            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices para optimización según el roadmap
            $table->index('persona_id');
            $table->index('estado');
            $table->index('equipo_responsable');
            $table->index('fecha_aprobacion');
            $table->index(['estado', 'fecha_vencimiento']);
            $table->index(['equipo_responsable', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_entregas');
    }
};
