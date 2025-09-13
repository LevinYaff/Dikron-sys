<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Equipo;
use App\Models\BitacoraEntrega;
use App\Models\Auditoria;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'password',
        'rol',
        'equipo_id',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activo' => 'boolean',
    ];

    /**
     * Los roles disponibles en el sistema
     */
    const ROLES = [
        'filtro' => 'Filtro',
        'estadistica' => 'Estadística',
        'banco' => 'Banco de Alimentos',
        'admin' => 'Administrador',
        'super_admin' => 'Super Administrador'
    ];

    /**
     * Relación con el equipo al que pertenece el usuario
     */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    /**
     * Entregas que ha aprobado este usuario
     */
    public function entregasAprobadas(): HasMany
    {
        return $this->hasMany(BitacoraEntrega::class, 'usuario_aprobo');
    }

    /**
     * Entregas que ha preparado este usuario
     */
    public function entregasPreparadas(): HasMany
    {
        return $this->hasMany(BitacoraEntrega::class, 'usuario_preparo');
    }

    /**
     * Entregas que ha entregado este usuario
     */
    public function entregasEntregadas(): HasMany
    {
        return $this->hasMany(BitacoraEntrega::class, 'usuario_entrego');
    }

    /**
     * Auditorías realizadas por este usuario
     */
    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    /**
     * Scope para usuarios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope por rol
     */
    public function scopePorRol($query, $rol)
    {
        return $query->where('rol', $rol);
    }

    /**
     * Scope por equipo
     */
    public function scopePorEquipo($query, $equipoId)
    {
        return $query->where('equipo_id', $equipoId);
    }

    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function tieneRol(string $rol): bool
    {
        return $this->rol === $rol;
    }

    /**
     * Verificar si el usuario es admin o super admin
     */
    public function esAdmin(): bool
    {
        return in_array($this->rol, ['admin', 'super_admin']);
    }

    /**
     * Verificar si el usuario puede acceder fuera de su turno
     */
    public function puedeAccederSiempreTurno(): bool
    {
        return in_array($this->rol, ['admin', 'super_admin']);
    }

    /**
     * Obtener el nombre completo del usuario
     */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    /**
     * Obtener el nombre del rol en formato legible
     */
    public function getRolNombreAttribute(): string
    {
        return self::ROLES[$this->rol] ?? $this->rol;
    }
}
