<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Valores aún no confirmados del registro masivo de lecturas (specs/015),
 * autoguardados cada 2 minutos por usuario y periodo. Estado transitorio: se
 * descarta por completo al completar el guardado final exitoso del lote.
 */
class BorradorLecturaMedidor extends Model
{
    protected $table = 'borradores_lectura_medidor';

    protected $fillable = [
        'usuario_id',
        'periodo',
        'locacion_id',
        'lectura_actual',
    ];

    protected function casts(): array
    {
        return [
            'periodo' => 'date',
            'lectura_actual' => 'decimal:2',
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
