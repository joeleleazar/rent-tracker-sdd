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
        'fecha_inicio',
        'fecha_fin',
        'monto_renta',
        'estado',
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

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoContrato::class);
    }

    public function inquilinos(): BelongsToMany
    {
        return $this->belongsToMany(Inquilino::class, 'contrato_inquilino')
            ->withPivot('es_principal')
            ->withTimestamps();
    }

    /**
     * El inquilino designado como Principal del contrato (specs/003-representantes-contrato,
     * corrección 2026-08-23: el inquilino ES el representante del contrato, no
     * existe una entidad "representante" separada). Usa la colección ya
     * cargada por `inquilinos` cuando está disponible, para evitar una
     * consulta adicional en listados con eager loading.
     */
    public function inquilinoPrincipal(): ?Inquilino
    {
        if ($this->relationLoaded('inquilinos')) {
            return $this->inquilinos->first(fn (Inquilino $inquilino) => (bool) $inquilino->pivot->es_principal)
                ?? $this->inquilinos->first();
        }

        return $this->inquilinos()->wherePivot('es_principal', true)->first()
            ?? $this->inquilinos()->first();
    }

    public function recibos(): HasMany
    {
        return $this->hasMany(Recibo::class);
    }

    public function valoresConceptos(): HasMany
    {
        return $this->hasMany(ValorConceptoContrato::class);
    }

    /**
     * specs/024: valor de referencia configurado para un concepto de gasto
     * fijo en este contrato (nunca para "Renta" ni "Luz", que no se
     * configuran aquí — ver ConceptoGastoFijo::esProtegido()). `null` si el
     * contrato todavía no tiene un valor configurado para ese concepto.
     */
    public function valorDeConcepto(ConceptoGastoFijo $concepto): ?float
    {
        if ($this->relationLoaded('valoresConceptos')) {
            $valor = $this->valoresConceptos->firstWhere('concepto_gasto_fijo_id', $concepto->id);
        } else {
            $valor = $this->valoresConceptos()->where('concepto_gasto_fijo_id', $concepto->id)->first();
        }

        return $valor !== null ? (float) $valor->valor : null;
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
