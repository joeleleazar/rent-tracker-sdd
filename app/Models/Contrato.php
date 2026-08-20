<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    use HasFactory;

    protected $attributes = [
        'estado' => 'borrador',
    ];

    protected $fillable = [
        'locacion_id',
        'inquilino_id',
        'fecha_inicio',
        'fecha_fin',
        'monto_renta',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'monto_renta' => 'decimal:2',
        ];
    }

    public function locacion(): BelongsTo
    {
        return $this->belongsTo(Locacion::class);
    }

    public function inquilino(): BelongsTo
    {
        return $this->belongsTo(Inquilino::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoContrato::class);
    }

    public function scopeHistorialCronologico(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('fecha_inicio');
    }
}
