<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturaMedidor extends Model
{
    use HasFactory;

    protected $table = 'lecturas_medidor';

    protected $fillable = [
        'locacion_id',
        'periodo',
        'lectura_anterior',
        'lectura_actual',
        'total',
        'fecha_registro',
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'date',
            'lectura_anterior' => 'decimal:2',
            'lectura_actual' => 'decimal:2',
            'total' => 'decimal:2',
            'fecha_registro' => 'datetime',
        ];
    }

    /**
     * specs/021: consumo derivado en el momento a partir de lectura_actual y
     * lectura_anterior — ya no es una columna propia, para que no pueda volver a
     * desincronizarse de esos dos valores como ya ocurrió (specs/016, specs/019).
     * Sin lectura anterior, se usa 0 (FR-005/Q1:A: mismo criterio en todo el
     * sistema, no solo en el registro masivo).
     */
    protected function consumoCalculado(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format(
                (float) $this->lectura_actual - (float) ($this->lectura_anterior ?? 0),
                2,
                '.',
                '',
            ),
        );
    }

    public function locacion(): BelongsTo
    {
        return $this->belongsTo(Locacion::class);
    }

    /**
     * Detecta si la `lectura_actual` de este periodo difiere de la `lectura_anterior`
     * usada en el periodo cronológicamente siguiente de la misma locación (FR-007,
     * specs/006). Solo informativo — no bloquea ninguna operación de guardado.
     */
    public function discrepanciaConSiguiente(): bool
    {
        $siguiente = static::where('locacion_id', $this->locacion_id)
            ->where('periodo', '>', $this->periodo->format('Y-m-d'))
            ->orderBy('periodo')
            ->first();

        if ($siguiente === null || $siguiente->lectura_anterior === null) {
            return false;
        }

        return round((float) $this->lectura_actual, 2) !== round((float) $siguiente->lectura_anterior, 2);
    }
}
