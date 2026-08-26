<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo dinámico de conceptos de gasto fijo (specs/024) — reemplaza los 5
 * conceptos antes codificados como columnas fijas de `Contrato`/`Recibo`.
 * `clave` identifica de forma estable (no editable) los 2 conceptos con una
 * fuente de valor especial: "renta" (usa `Recibo::monto_renta` con prorrateo)
 * y "luz" (usa la lectura de medidor del periodo) — ver `esProtegido()`.
 */
class ConceptoGastoFijo extends Model
{
    use HasFactory;

    protected $table = 'conceptos_gasto_fijo';

    protected $attributes = [
        'activo' => true,
    ];

    protected $fillable = [
        'nombre',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function scopeActivos(Builder $consulta): Builder
    {
        return $consulta->where('activo', true);
    }

    public function scopeOrdenados(Builder $consulta): Builder
    {
        return $consulta->orderBy('orden');
    }

    /**
     * "Renta" (`clave='renta'`) no puede desactivarse ni eliminarse (FR-002)
     * porque el cálculo central de alquiler y prorrateo del sistema depende
     * de que siempre exista.
     */
    public function esProtegido(): bool
    {
        return $this->clave !== null;
    }

    public function esRenta(): bool
    {
        return $this->clave === 'renta';
    }

    public function esLuz(): bool
    {
        return $this->clave === 'luz';
    }

    public function valoresConcepto(): HasMany
    {
        return $this->hasMany(ValorConceptoContrato::class);
    }

    public function reciboConceptos(): HasMany
    {
        return $this->hasMany(ReciboConcepto::class);
    }
}
