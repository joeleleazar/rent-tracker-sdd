<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Inquilino extends Model
{
    use HasFactory;

    protected $fillable = [
        'apellidos',
        'nombres',
        'dni',
        'fecha_nacimiento',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
        ];
    }

    public function contratos(): BelongsToMany
    {
        return $this->belongsToMany(Contrato::class, 'contrato_inquilino')
            ->withPivot('es_principal')
            ->withTimestamps();
    }

    public function nombreCompleto(): string
    {
        return "{$this->apellidos}, {$this->nombres}";
    }
}
