<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avance no confirmado de la generación de un recibo (specs/026), análogo a
 * BorradorLecturaMedidor (specs/015): un registro transitorio por usuario,
 * locación y periodo, autoguardado mientras se completa el formulario y
 * descartado por completo al confirmarse la emisión del recibo correspondiente.
 */
class BorradorRecibo extends Model
{
    protected $table = 'borradores_recibo';

    protected $fillable = [
        'usuario_id',
        'periodo',
        'locacion_id',
        'incluye_alquiler',
        'monto_renta',
        'fecha_emision',
        'conceptos',
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'date',
            'incluye_alquiler' => 'boolean',
            'monto_renta' => 'decimal:2',
            'fecha_emision' => 'date',
            'conceptos' => 'array',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function locacion(): BelongsTo
    {
        return $this->belongsTo(Locacion::class);
    }
}
