<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Parámetros administrativos globales del sistema, almacenados como filas
 * clave-valor (specs/018, FR-007) en vez de una fila ancha con una columna
 * por parámetro — así, agregar un parámetro nuevo es insertar una fila, no
 * una migración de esquema. La interfaz pública (los 4 atributos de abajo,
 * `actual()` y `update()`) se mantiene idéntica a la forma anterior para que
 * ningún controlador o servicio que ya la use necesite cambiar — ver
 * specs/018-optimizacion-esquema-postgresql/contracts/contrato-configuracion-general.md.
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

    /**
     * Hidrata una instancia en memoria con el valor vigente de cada
     * configuración conocida (fila clave-valor decodificada, o su valor por
     * defecto si la clave todavía no existe), para que el resto del código
     * siga leyendo `->tarifa_luz_por_unidad`, etc. como si fueran columnas
     * reales de una única fila.
     */
    public static function actual(): self
    {
        $valoresGuardados = static::query()
            ->pluck('valor', 'clave')
            ->map(fn (string $valorJson) => json_decode($valorJson, true));

        $atributos = array_merge(static::valoresPorDefecto(), $valoresGuardados->all());

        $instancia = new static();
        $instancia->setRawAttributes($atributos, true);
        $instancia->exists = true;

        return $instancia;
    }

    /**
     * La instancia virtual devuelta por `actual()` no tiene una clave primaria
     * de una sola fila real (spans varias filas clave-valor), así que
     * `fresh()` no puede resolverla por `id` como haría Eloquent por defecto.
     * Se sobrescribe para conservar su uso ya existente en la aplicación
     * (`ConfiguracionGeneral::actual()->fresh()`) con el mismo significado:
     * releer el estado vigente de la configuración.
     */
    public function fresh($with = []): ?self
    {
        return static::actual();
    }

    /**
     * Persiste cada atributo modificado (`getDirty()`) como su propia fila
     * clave-valor, en vez del `UPDATE` de una sola fila que generaría
     * Eloquent por defecto — es el único punto que traduce la interfaz
     * pública de columnas hacia el almacenamiento clave-valor real.
     */
    public function save(array $options = []): bool
    {
        $cambios = $this->getDirty();

        if ($cambios === []) {
            return true;
        }

        $ahora = now();

        foreach ($cambios as $clave => $valor) {
            DB::table($this->getTable())->updateOrInsert(
                ['clave' => $clave],
                ['valor' => json_encode($valor), 'updated_at' => $ahora],
            );
        }

        $this->syncOriginal();

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private static function valoresPorDefecto(): array
    {
        return [
            'correo_notificaciones_vencimiento' => config('mail.from.address', 'hello@example.com'),
            'tarifa_luz_por_unidad' => 0,
            'dias_anticipacion_alerta_pago' => 5,
            'alerta_pago_mes_enviada_en' => null,
        ];
    }
}
