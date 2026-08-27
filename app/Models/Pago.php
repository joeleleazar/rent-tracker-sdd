<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'recibo_id',
        'monto',
        'fecha_pago',
        'registrado_por_id',
        'evidencia_ruta',
        'evidencia_nombre_archivo',
        'evidencia_tipo',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'date',
        ];
    }

    public function recibo(): BelongsTo
    {
        return $this->belongsTo(Recibo::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function tieneEvidencia(): bool
    {
        return $this->evidencia_ruta !== null;
    }

    /**
     * specs/036: acumulado histórico hasta este pago inclusive — suma solo los pagos del
     * recibo registrados hasta este (orden de `id`, no `fecha_pago`, ver research.md
     * Decisión 2), calculado siempre sobre los pagos que existen ahora mismo (sin
     * persistir ningún valor) para que la edición o eliminación de un pago anterior lo
     * recalcule automáticamente (research.md Decisión 1).
     */
    public function montoAcumuladoHastaEstePago(): float
    {
        return (float) $this->recibo->pagos
            ->where('id', '<=', $this->id)
            ->sum('monto');
    }

    /**
     * Nunca negativo, igual criterio que Recibo::saldoPendiente().
     */
    public function saldoPendienteHastaEstePago(): float
    {
        return max(0.0, $this->recibo->total() - $this->montoAcumuladoHastaEstePago());
    }
}
