<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'costo_agua',
        'costo_luz',
        'costo_pasadizo',
        'costo_seguridad',
        'notificado_30_dias_en',
        'notificado_15_dias_en',
        'notificado_7_dias_en',
        'monto_garantia',
        'fecha_entrega_garantia',
        'medio_entrega_garantia',
        'estado_garantia',
        'monto_devuelto_garantia',
        'monto_retenido_garantia',
        'motivo_retencion_garantia',
        'fecha_resolucion_garantia',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'monto_renta' => 'decimal:2',
            'costo_agua' => 'decimal:2',
            'costo_luz' => 'decimal:2',
            'costo_pasadizo' => 'decimal:2',
            'costo_seguridad' => 'decimal:2',
            'notificado_30_dias_en' => 'datetime',
            'notificado_15_dias_en' => 'datetime',
            'notificado_7_dias_en' => 'datetime',
            'monto_garantia' => 'decimal:2',
            'fecha_entrega_garantia' => 'date',
            'monto_devuelto_garantia' => 'decimal:2',
            'monto_retenido_garantia' => 'decimal:2',
            'fecha_resolucion_garantia' => 'datetime',
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

    public function representantes(): BelongsToMany
    {
        return $this->belongsToMany(Representante::class, 'contrato_representante')
            ->withPivot('es_principal')
            ->withTimestamps();
    }

    public function recibos(): HasMany
    {
        return $this->hasMany(Recibo::class);
    }

    public function scopeHistorialCronologico(Builder $consulta): Builder
    {
        return $consulta->orderByDesc('fecha_inicio');
    }

    /**
     * Un monto de garantía de 0.00 (o nulo) se trata como "sin garantía
     * registrada" para efectos de visualización y de habilitar la resolución
     * (Edge Case "Garantía con monto igual a cero").
     */
    public function tieneGarantia(): bool
    {
        return $this->monto_garantia !== null && (float) $this->monto_garantia > 0;
    }

    public function garantiaResuelta(): bool
    {
        return $this->estado_garantia === 'resuelta';
    }
}
