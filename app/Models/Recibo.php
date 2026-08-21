<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'monto_agua',
        'monto_luz',
        'monto_pasadizo',
        'monto_seguridad',
        'incluye_alquiler',
        'incluye_luz',
        'incluye_agua',
        'incluye_seguridad',
        'incluye_pasadizo',
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
            'monto_agua' => 'decimal:2',
            'monto_luz' => 'decimal:2',
            'monto_pasadizo' => 'decimal:2',
            'monto_seguridad' => 'decimal:2',
            'incluye_alquiler' => 'boolean',
            'incluye_luz' => 'boolean',
            'incluye_agua' => 'boolean',
            'incluye_seguridad' => 'boolean',
            'incluye_pasadizo' => 'boolean',
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

    /**
     * Suma únicamente los conceptos incluidos (FR-005 de
     * specs/005-lecturas-medidor-recibo-periodo): un concepto excluido no aporta al
     * total, aunque su monto siga guardado en la fila (por si se vuelve a incluir).
     */
    public function total(): float
    {
        $total = 0.0;

        if ($this->incluye_alquiler) {
            $total += (float) $this->monto_renta;
        }
        if ($this->incluye_agua) {
            $total += (float) $this->monto_agua;
        }
        if ($this->incluye_luz) {
            $total += (float) $this->monto_luz;
        }
        if ($this->incluye_pasadizo) {
            $total += (float) $this->monto_pasadizo;
        }
        if ($this->incluye_seguridad) {
            $total += (float) $this->monto_seguridad;
        }

        return $total;
    }
}
