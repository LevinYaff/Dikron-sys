<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use App\Models\User;
use App\Models\BitacoraEntrega;


class Equipo extends Model
{
    use HasFactory;

    protected $table = 'equipos';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nombre',
        'fecha_inicio_ciclo',
        'activo',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'fecha_inicio_ciclo' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Los equipos disponibles en el sistema
     */
    const EQUIPOS_DISPONIBLES = [
        'AJ' => 'Equipo AJ',
        'BF' => 'Equipo BF',
        'CI' => 'Equipo CI',
        'DG' => 'Equipo DG',
        'EH' => 'Equipo EH',
        'KL' => 'Equipo KL'
    ];

    /**
     * Usuarios que pertenecen a este equipo
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Usuarios activos de este equipo
     */
    public function usuariosActivos(): HasMany
    {
        return $this->hasMany(User::class)->where('activo', true);
    }

    /**
     * Entregas responsabilidad de este equipo
     */
    public function entregas(): HasMany
    {
        return $this->hasMany(BitacoraEntrega::class, 'equipo_responsable', 'nombre');
    }

    /**
     * Scope para equipos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Obtener el equipo que está actualmente en turno
     * Basado en el cálculo rotativo desde fecha_inicio_ciclo
     */
    public static function getEquipoEnTurno(): ?self
    {
        $fechaInicio = Carbon::parse('2025-08-03'); // Fecha de referencia fija
        $hoy = Carbon::now()->startOfDay();
        $diasTranscurridos = $hoy->diffInDays($fechaInicio);
        $semanasTranscurridas = floor($diasTranscurridos / 7);

        $equipos = ['AJ', 'BF', 'CI', 'DG', 'EH', 'KL'];
        $indiceActivo = $semanasTranscurridas % 6;
        $equipoNombre = $equipos[$indiceActivo];

        return self::where('nombre', $equipoNombre)->first();
    }

    /**
     * Verificar si este equipo está actualmente en turno
     */
    public function estaEnTurno(): bool
    {
        $equipoActivo = self::getEquipoEnTurno();
        return $equipoActivo && $this->id === $equipoActivo->id;
    }

    /**
     * Obtener las próximas fechas de turno para este equipo
     */
    public function getProximasFechasTurno(int $cantidad = 3): array
    {
        $fechas = [];
        $fechaInicio = Carbon::parse('2025-08-03');
        $equipos = ['AJ', 'BF', 'CI', 'DG', 'EH', 'KL'];
        $indiceEsteEquipo = array_search($this->nombre, $equipos);

        if ($indiceEsteEquipo === false) {
            return [];
        }

        $hoy = Carbon::now()->startOfDay();
        $diasTranscurridos = $hoy->diffInDays($fechaInicio);
        $semanasTranscurridas = floor($diasTranscurridos / 7);

        // Encontrar la próxima semana de este equipo
        $proximaSemana = $semanasTranscurridas;
        while (($proximaSemana % 6) !== $indiceEsteEquipo) {
            $proximaSemana++;
        }

        // Generar las próximas fechas
        for ($i = 0; $i < $cantidad; $i++) {
            $semana = $proximaSemana + ($i * 6); // Cada 6 semanas repite
            $fechaInicioTurno = $fechaInicio->copy()->addWeeks($semana);
            $fechaFinTurno = $fechaInicioTurno->copy()->addDays(6);

            $fechas[] = [
                'inicio' => $fechaInicioTurno,
                'fin' => $fechaFinTurno,
                'semana' => $semana + 1
            ];
        }

        return $fechas;
    }

    /**
     * Obtener estadísticas de entregas para este equipo
     */
    public function getEstadisticasEntregas(Carbon $fechaInicio = null, Carbon $fechaFin = null): array
    {
        $query = $this->entregas();

        if ($fechaInicio) {
            $query->where('fecha_aprobacion', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->where('fecha_aprobacion', '<=', $fechaFin);
        }

        $entregas = $query->get();

        return [
            'total' => $entregas->count(),
            'aprobadas' => $entregas->where('estado', 'aprobada')->count(),
            'en_preparacion' => $entregas->where('estado', 'en_preparacion')->count(),
            'listas' => $entregas->where('estado', 'lista')->count(),
            'entregadas' => $entregas->where('estado', 'entregada')->count(),
            'vencidas' => $entregas->where('estado', 'vencida')->count(),
            'por_tipo' => $entregas->groupBy('tipo_ayuda')->map->count(),
        ];
    }

    /**
     * Obtener el nombre completo del equipo
     */
    public function getNombreCompletoAttribute(): string
    {
        return self::EQUIPOS_DISPONIBLES[$this->nombre] ?? "Equipo {$this->nombre}";
    }
}
