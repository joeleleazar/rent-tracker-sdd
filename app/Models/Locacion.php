<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Locacion extends Model
{
    use HasFactory;

    /**
     * Límite de saltos al recorrer la cadena de ancestros, como red de seguridad
     * ante datos corruptos (ver research.md de 001-jerarquia-locaciones §2).
     */
    private const MAXIMO_SALTOS_ANCESTROS = 1000;

    protected $table = 'locaciones';

    protected $fillable = [
        'nombre',
        'tamano',
        'ubicacion_fisica',
        'descripcion',
        'locacion_padre_id',
        'es_alquilable',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'decimal:2',
            'es_alquilable' => 'boolean',
        ];
    }

    public function locacionPadre(): BelongsTo
    {
        return $this->belongsTo(Locacion::class, 'locacion_padre_id');
    }

    public function locacionesHijas(): HasMany
    {
        return $this->hasMany(Locacion::class, 'locacion_padre_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }

    public function recibos(): HasMany
    {
        return $this->hasMany(Recibo::class);
    }

    public function lecturasMedidor(): HasMany
    {
        return $this->hasMany(LecturaMedidor::class);
    }

    public function scopeAlquilables(Builder $consulta): Builder
    {
        return $consulta->where('es_alquilable', true);
    }

    /**
     * Contrato vigente de esta locación durante el mes calendario completo del
     * periodo dado (mismo criterio de exclusión de estados que
     * ServicioValidacionSolapamientoContrato de specs/002). Si más de uno calificara
     * (no debería, dado que 002 impide solapamientos), se toma el de fecha_inicio
     * más reciente como salvaguarda defensiva. Ver
     * specs/005-lecturas-medidor-recibo-periodo/research.md §2 — helper adelantado
     * a specs/004 porque las rutas de recibo ya son locación-céntricas desde ahí.
     */
    public function contratoActivoEnPeriodo(Carbon $periodo): ?Contrato
    {
        $inicioDeMes = $periodo->copy()->startOfMonth();
        $finDeMes = $periodo->copy()->endOfMonth();

        return $this->contratos()
            ->where('estado', '!=', 'rescindido')
            ->where('fecha_inicio', '<=', $finDeMes)
            ->where('fecha_fin', '>=', $inicioDeMes)
            ->orderByDesc('fecha_inicio')
            ->first();
    }

    /**
     * Devuelve la cadena de ancestros (padre, abuelo, ...) empezando por el
     * padre directo hasta la raíz, sin incluir la propia locación.
     *
     * @return array<int, Locacion>
     */
    public function ancestros(): array
    {
        $ancestros = [];
        $actual = $this->locacionPadre;
        $saltos = 0;

        while ($actual !== null && $saltos < self::MAXIMO_SALTOS_ANCESTROS) {
            $ancestros[] = $actual;
            $actual = $actual->locacionPadre;
            $saltos++;
        }

        return $ancestros;
    }

    /**
     * Devuelve los últimos 3 niveles de la jerarquía (incluyendo esta locación),
     * con un indicador de omisión ("...") si la cadena real es más profunda
     * (FR-004, Senior-First: tipografía mínima de 18px sin scroll horizontal).
     *
     * @return array{omitido: bool, niveles: array<int, Locacion>}
     */
    public function rutaJerarquiaTruncada(): array
    {
        $cadenaCompleta = array_reverse($this->ancestros());
        $cadenaCompleta[] = $this;

        $totalNiveles = count($cadenaCompleta);
        $niveles = array_slice($cadenaCompleta, -3);

        return [
            'omitido' => $totalNiveles > 3,
            'niveles' => $niveles,
        ];
    }
}
