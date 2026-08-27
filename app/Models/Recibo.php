<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recibo extends Model
{
    use HasFactory;

    protected $attributes = [
        'estado' => 'pendiente',
    ];

    protected $fillable = [
        'contrato_id',
        'locacion_id',
        'lectura_medidor_id',
        'monto_renta',
        'periodo',
        'fecha_emision',
        'estado',
        'fecha_pago',
        'fecha_anulacion',
        'dias_activos_periodo',
        'dias_totales_periodo',
    ];

    protected function casts(): array
    {
        return [
            'monto_renta' => 'decimal:2',
            'periodo' => 'date',
            'fecha_emision' => 'date',
            'fecha_pago' => 'datetime',
            'fecha_anulacion' => 'datetime',
            'dias_activos_periodo' => 'integer',
            'dias_totales_periodo' => 'integer',
        ];
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function locacion(): BelongsTo
    {
        return $this->belongsTo(Locacion::class);
    }

    public function lecturaMedidor(): BelongsTo
    {
        return $this->belongsTo(LecturaMedidor::class);
    }

    public function conceptos(): HasMany
    {
        return $this->hasMany(ReciboConcepto::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    /**
     * specs/024: "Renta" ya no es un concepto más de `conceptos()` — sigue
     * siendo `monto_renta`, con su prorrateo ya calculado (specs/008/019).
     * El resto de los conceptos incluidos en este recibo viven en
     * `recibo_conceptos`; a diferencia del modelo anterior (specs/005), un
     * concepto no incluido simplemente no tiene fila aquí — no hay ningún
     * monto "recordado pero excluido" que sumar condicionalmente.
     */
    public function total(): float
    {
        return (float) ($this->monto_renta ?? 0) + (float) $this->conceptos->sum('monto');
    }

    /**
     * specs/032: suma de los pagos registrados contra este recibo — la fuente
     * de verdad del avance de pago, en vez de un campo agregado aparte.
     */
    public function montoPagado(): float
    {
        return (float) $this->pagos->sum('monto');
    }

    /**
     * Nunca negativo: si por algún motivo la suma de pagos superara el total
     * (no debería ocurrir, ver ServicioGestionPagosRecibo), no se expone un
     * saldo pendiente negativo.
     */
    public function saldoPendiente(): float
    {
        return max(0.0, $this->total() - $this->montoPagado());
    }

    public function estaPagadoPorCompleto(): bool
    {
        return $this->saldoPendiente() <= 0.0;
    }

    /**
     * specs/026: un recibo anulado no representa cobertura vigente de sus
     * conceptos — se excluye de todo cálculo de disponibilidad, superposición
     * y "en uso" de un concepto del catálogo (research.md Decisión 1).
     */
    public function scopeVigente(Builder $query): Builder
    {
        return $query->where('estado', '!=', 'anulado');
    }
}
