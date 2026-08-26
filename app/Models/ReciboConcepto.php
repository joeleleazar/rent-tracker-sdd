<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReciboConcepto extends Model
{
    use HasFactory;

    protected $table = 'recibo_conceptos';

    protected $fillable = [
        'recibo_id',
        'concepto_gasto_fijo_id',
        'monto',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function recibo(): BelongsTo
    {
        return $this->belongsTo(Recibo::class);
    }

    public function conceptoGastoFijo(): BelongsTo
    {
        return $this->belongsTo(ConceptoGastoFijo::class);
    }
}
