<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Fila única (singleton, id = 1) de parámetros administrativos globales del sistema.
 * Ver specs/004-condiciones-contrato-recibo/research.md §2.
 */
class ConfiguracionGeneral extends Model
{
    use HasFactory;

    protected $table = 'configuracion_general';

    protected $fillable = [
        'correo_notificaciones_vencimiento',
        'tarifa_luz_por_unidad',
        'dias_anticipacion_alerta_pago',
        'alerta_pago_mes_enviada_en',
    ];

    protected function casts(): array
    {
        return [
            'tarifa_luz_por_unidad' => 'decimal:4',
            'dias_anticipacion_alerta_pago' => 'integer',
            'alerta_pago_mes_enviada_en' => 'datetime',
        ];
    }

    public static function actual(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['correo_notificaciones_vencimiento' => config('mail.from.address', 'hello@example.com')],
        );
    }
}
