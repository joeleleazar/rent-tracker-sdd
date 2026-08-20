<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locacion extends Model
{
    use HasFactory;

    protected $table = 'locaciones';

    protected $fillable = [
        'nombre',
        'tamano',
        'ubicacion_fisica',
        'descripcion',
        'locacion_padre_id',
        'es_alquilable',
    ];

    protected function casts(): array
    {
        return [
            'tamano' => 'decimal:2',
            'es_alquilable' => 'boolean',
        ];
    }

    public function locacionPadre(): BelongsTo
    {
        return $this->belongsTo(Locacion::class, 'locacion_padre_id');
    }

    public function locacionesHijas(): HasMany
    {
        return $this->hasMany(Locacion::class, 'locacion_padre_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }
}
