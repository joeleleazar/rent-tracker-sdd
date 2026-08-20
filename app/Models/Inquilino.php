<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inquilino extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
    ];

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }
}
