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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_identidad', 20)->unique();
            $table->boolean('es_extranjero')->default(false);
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->date('fecha_nacimiento');
            $table->integer('edad');
            $table->enum('estado_civil', ['soltero', 'casado', 'union_libre', 'viudo', 'divorciado']);
            $table->string('telefono', 15)->nullable();
            $table->text('direccion')->nullable();
            $table->string('equipo_servicio', 50)->nullable(); // Equipo al que pertenece en la iglesia
            $table->enum('tipo_familia', ['pequeña', 'mediana', 'grande']);
            $table->integer('miembros_sirven_iglesia')->default(0);
            $table->integer('dependientes')->default(0);

            // Campos para casos especiales
            $table->boolean('es_especial')->default(false);
            $table->integer('entregas_mes_permitidas')->default(1); // 1, 2 o 3
            $table->boolean('especial_indefinido')->default(false);
            $table->text('especial_observaciones')->nullable();

            $table->timestamps();

            // Índices para optimización
            $table->index('numero_identidad');
            $table->index(['es_especial', 'activo']);
            $table->index('tipo_familia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
