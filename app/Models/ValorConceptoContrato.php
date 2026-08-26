<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValorConceptoContrato extends Model
{
    use HasFactory;

    protected $table = 'contrato_valores_concepto';

    protected $fillable = [
        'contrato_id',
        'concepto_gasto_fijo_id',
        'valor',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function conceptoGastoFijo(): BelongsTo
    {
        return $this->belongsTo(ConceptoGastoFijo::class);
    }
}
