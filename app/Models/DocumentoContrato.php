<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentoContrato extends Model
{
    use HasFactory;

    protected $table = 'documentos_contrato';

    protected $fillable = [
        'contrato_id',
        'nombre_archivo',
        'ruta_archivo',
        'tipo_archivo',
        'secuencia',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }
}
