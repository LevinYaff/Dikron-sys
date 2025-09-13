<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use App\Models\BitacoraEntrega;


class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'numero_identidad',
        'es_extranjero',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'edad',
        'estado_civil',
        'telefono',
        'direccion',
        'equipo_servicio',
        'tipo_familia',
        'miembros_sirven_iglesia',
        'dependientes',
        'es_especial',
        'entregas_mes_permitidas',
        'especial_indefinido',
        'especial_observaciones',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'edad' => 'integer',
        'miembros_sirven_iglesia' => 'integer',
        'dependientes' => 'integer',
        'es_extranjero' => 'boolean',
        'es_especial' => 'boolean',
        'entregas_mes_permitidas' => 'integer',
        'especial_indefinido' => 'boolean',
    ];

    /**
     * Estados civiles disponibles
     */
    const ESTADOS_CIVILES = [
        'soltero' => 'Soltero/a',
        'casado' => 'Casado/a',
        'union_libre' => 'Unión Libre',
        'viudo' => 'Viudo/a',
        'divorciado' => 'Divorciado/a'
    ];

    /**
     * Tipos de familia disponibles
     */
    const TIPOS_FAMILIA = [
        'pequeña' => 'Pequeña (1-3 miembros)',
        'mediana' => 'Mediana (4-6 miembros)',
        'grande' => 'Grande (7+ miembros)'
    ];

    /**
     * Entregas de esta persona
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(BitacoraEntrega::class);
    }

    /**
     * Entregas ordenadas por fecha (más recientes primero)
     */
    public function entregasOrdenadas(): HasMany
    {
        return $this->entregas()->orderBy('fecha_aprobacion', 'desc');
    }

    /**
     * Entregas de este mes
     */
    public function entregasEsteMes(): HasMany
    {
        return $this->entregas()->whereMonth('fecha_aprobacion', Carbon::now()->month)
                                ->whereYear('fecha_aprobacion', Carbon::now()->year);
    }

    /**
     * Última entrega realizada
     */
    public function ultimaEntrega()
    {
        return $this->entregas()->latest('fecha_aprobacion')->first();
    }

    /**
     * Scope para personas especiales
     */
    public function scopeEspeciales($query)
    {
        return $query->where('es_especial', true);
    }

    /**
     * Scope para personas extranjeras
     */
    public function scopeExtranjeros($query)
    {
        return $query->where('es_extranjero', true);
    }

    /**
     * Scope por tipo de familia
     */
    public function scopePorTipoFamilia($query, $tipo)
    {
        return $query->where('tipo_familia', $tipo);
    }

    /**
     * Scope por equipo de servicio
     */
    public function scopePorEquipoServicio($query, $equipo)
    {
        return $query->where('equipo_servicio', $equipo);
    }

    /**
     * Verificar elegibilidad para nueva entrega
     */
    public function verificarElegibilidad(): array
    {
        $ultimaEntrega = $this->ultimaEntrega();

        // Si no tiene entregas previas, siempre es elegible
        if (!$ultimaEntrega) {
            return [
                'elegible' => true,
                'motivo' => 'Primera entrega',
                'codigo' => 'PRIMERA_VEZ',
                'dias_restantes' => null,
                'entregas_mes_actual' => 0,
                'entregas_restantes_mes' => $this->entregas_mes_permitidas
            ];
        }

        $entregasEsteMes = $this->entregasEsteMes()->count();
        $fechaUltimaEntrega = $ultimaEntrega->fecha_aprobacion;
        $hoy = Carbon::now();

        // Casos especiales
        if ($this->es_especial) {
            return $this->verificarElegibilidadEspecial($entregasEsteMes, $fechaUltimaEntrega, $hoy);
        }

        // Lógica normal: máximo 6 entregas en total, 1 por mes
        $totalEntregas = $this->entregas()->count();

        // Verificar límite de 6 entregas totales
        if ($totalEntregas >= 6) {
            return [
                'elegible' => false,
                'motivo' => 'Ha alcanzado el límite máximo de 6 entregas',
                'codigo' => 'LIMITE_MAXIMO',
                'dias_restantes' => null,
                'entregas_mes_actual' => $entregasEsteMes,
                'entregas_restantes_mes' => 0
            ];
        }

        // Verificar si ya recibió entrega este mes
        if ($entregasEsteMes >= 1) {
            $proximoMes = $hoy->copy()->addMonth()->startOfMonth();
            $diasRestantes = $hoy->diffInDays($proximoMes);

            return [
                'elegible' => false,
                'motivo' => 'Ya recibió su entrega mensual',
                'codigo' => 'YA_RECIBIO_MES',
                'dias_restantes' => $diasRestantes,
                'entregas_mes_actual' => $entregasEsteMes,
                'entregas_restantes_mes' => 0
            ];
        }

        // Verificar tiempo mínimo entre entregas (30 días)
        $diasDesdeUltima = $fechaUltimaEntrega->diffInDays($hoy);
        if ($diasDesdeUltima < 30) {
            $diasRestantes = 30 - $diasDesdeUltima;
            return [
                'elegible' => false,
                'motivo' => "Debe esperar {$diasRestantes} días más",
                'codigo' => 'TIEMPO_MINIMO',
                'dias_restantes' => $diasRestantes,
                'entregas_mes_actual' => $entregasEsteMes,
                'entregas_restantes_mes' => 1
            ];
        }

        // Es elegible
        return [
            'elegible' => true,
            'motivo' => 'Elegible para nueva entrega',
            'codigo' => 'ELEGIBLE',
            'dias_restantes' => null,
            'entregas_mes_actual' => $entregasEsteMes,
            'entregas_restantes_mes' => 1
        ];
    }

    /**
     * Verificar elegibilidad para personas especiales
     */
    private function verificarElegibilidadEspecial($entregasEsteMes, $fechaUltimaEntrega, $hoy): array
    {
        // Casos especiales indefinidos siempre son elegibles
        if ($this->especial_indefinido) {
            return [
                'elegible' => true,
                'motivo' => 'Caso especial indefinido',
                'codigo' => 'ESPECIAL_INDEFINIDO',
                'dias_restantes' => null,
                'entregas_mes_actual' => $entregasEsteMes,
                'entregas_restantes_mes' => 999 // Ilimitado
            ];
        }

        // Verificar límite mensual para casos especiales
        if ($entregasEsteMes >= $this->entregas_mes_permitidas) {
            $proximoMes = $hoy->copy()->addMonth()->startOfMonth();
            $diasRestantes = $hoy->diffInDays($proximoMes);

            return [
                'elegible' => false,
                'motivo' => "Ya alcanzó el límite mensual ({$this->entregas_mes_permitidas})",
                'codigo' => 'LIMITE_ESPECIAL_MES',
                'dias_restantes' => $diasRestantes,
                'entregas_mes_actual' => $entregasEsteMes,
                'entregas_restantes_mes' => 0
            ];
        }

        // Tiempo mínimo entre entregas para especiales: 15 días
        $diasDesdeUltima = $fechaUltimaEntrega->diffInDays($hoy);
        if ($diasDesdeUltima < 15) {
            $diasRestantes = 15 - $diasDesdeUltima;
            return [
                'elegible' => false,
                'motivo' => "Debe esperar {$diasRestantes} días más (caso especial)",
                'codigo' => 'TIEMPO_MINIMO_ESPECIAL',
                'dias_restantes' => $diasRestantes,
                'entregas_mes_actual' => $entregasEsteMes,
                'entregas_restantes_mes' => $this->entregas_mes_permitidas - $entregasEsteMes
            ];
        }

        return [
            'elegible' => true,
            'motivo' => 'Caso especial elegible',
            'codigo' => 'ESPECIAL_ELEGIBLE',
            'dias_restantes' => null,
            'entregas_mes_actual' => $entregasEsteMes,
            'entregas_restantes_mes' => $this->entregas_mes_permitidas - $entregasEsteMes
        ];
    }

    /**
     * Obtener color del semáforo según elegibilidad
     */
    public function getColorSemaforo(): string
    {
        $elegibilidad = $this->verificarElegibilidad();

        if ($elegibilidad['elegible']) {
            return 'green'; // Verde - puede recibir
        }

        if ($elegibilidad['dias_restantes'] && $elegibilidad['dias_restantes'] <= 7) {
            return 'yellow'; // Amarillo - próximo a ser elegible
        }

        return 'red'; // Rojo - no puede recibir
    }

    /**
     * Marcar como caso especial
     */
    public function marcarComoEspecial(int $entregasMes = 2, bool $indefinido = false, string $observaciones = ''): bool
    {
        return $this->update([
            'es_especial' => true,
            'entregas_mes_permitidas' => $indefinido ? 999 : $entregasMes,
            'especial_indefinido' => $indefinido,
            'especial_observaciones' => $observaciones
        ]);
    }

    /**
     * Remover caso especial
     */
    public function removerEspecial(): bool
    {
        return $this->update([
            'es_especial' => false,
            'entregas_mes_permitidas' => 1,
            'especial_indefinido' => false,
            'especial_observaciones' => null
        ]);
    }

    /**
     * Calcular edad automáticamente
     */
    public function calcularEdad(): int
    {
        return $this->fecha_nacimiento->age;
    }

    /**
     * Actualizar edad basada en fecha de nacimiento
     */
    public function actualizarEdad(): bool
    {
        $nuevaEdad = $this->calcularEdad();

        if ($this->edad !== $nuevaEdad) {
            return $this->update(['edad' => $nuevaEdad]);
        }

        return true;
    }

    /**
     * Obtener el nombre completo
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Obtener el nombre del estado civil
     */
    public function getEstadoCivilNombreAttribute(): string
    {
        return self::ESTADOS_CIVILES[$this->estado_civil] ?? $this->estado_civil;
    }

    /**
     * Obtener el nombre del tipo de familia
     */
    public function getTipoFamiliaNombreAttribute(): string
    {
        return self::TIPOS_FAMILIA[$this->tipo_familia] ?? $this->tipo_familia;
    }
}
