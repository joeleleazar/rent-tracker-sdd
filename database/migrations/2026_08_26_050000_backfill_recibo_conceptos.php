<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * specs/024 (research.md Decisión 4): migra los conceptos efectivamente
 * incluidos de cada recibo existente (`incluye_agua`/`incluye_luz`/
 * `incluye_pasadizo`/`incluye_seguridad` en `true`, con su `monto_*`) hacia
 * `recibo_conceptos`, antes de eliminar esas columnas (migración separada,
 * T022). "Renta" no se migra a esta tabla — `monto_renta` se conserva tal
 * cual en `recibos`.
 */
return new class extends Migration
{
    private const MAPA_CONCEPTO = [
        'incluye_agua' => ['monto' => 'monto_agua', 'nombre' => 'Agua'],
        'incluye_luz' => ['monto' => 'monto_luz', 'nombre' => 'Luz'],
        'incluye_pasadizo' => ['monto' => 'monto_pasadizo', 'nombre' => 'Luz de Pasadizo'],
        'incluye_seguridad' => ['monto' => 'monto_seguridad', 'nombre' => 'Seguridad'],
    ];

    public function up(): void
    {
        $nombres = array_column(self::MAPA_CONCEPTO, 'nombre');
        $conceptos = DB::table('conceptos_gasto_fijo')->whereIn('nombre', $nombres)->pluck('id', 'nombre');

        $ahora = now();
        $filas = [];

        $columnas = array_merge(['id'], array_keys(self::MAPA_CONCEPTO), array_column(self::MAPA_CONCEPTO, 'monto'));

        foreach (DB::table('recibos')->select($columnas)->cursor() as $recibo) {
            foreach (self::MAPA_CONCEPTO as $columnaIncluye => $datos) {
                if (! $recibo->{$columnaIncluye}) {
                    continue;
                }

                $filas[] = [
                    'recibo_id' => $recibo->id,
                    'concepto_gasto_fijo_id' => $conceptos[$datos['nombre']],
                    'monto' => $recibo->{$datos['monto']} ?? 0,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }

        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('recibo_conceptos')->insert($lote);
        }
    }

    public function down(): void
    {
        DB::table('recibo_conceptos')->truncate();
    }
};
