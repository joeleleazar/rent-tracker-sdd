<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * specs/024 (research.md Decisión 4): migra los valores de `costo_agua`/
 * `costo_pasadizo`/`costo_seguridad` de cada contrato existente hacia
 * `contrato_valores_concepto`, antes de eliminar esas columnas (migración
 * separada, T021). `costo_luz` se descarta sin backfill (Decisión 5): nunca
 * se usó para sugerir el monto de luz de un recibo.
 */
return new class extends Migration
{
    private const MAPA_COLUMNA_A_NOMBRE = [
        'costo_agua' => 'Agua',
        'costo_pasadizo' => 'Luz de Pasadizo',
        'costo_seguridad' => 'Seguridad',
    ];

    public function up(): void
    {
        $conceptos = DB::table('conceptos_gasto_fijo')
            ->whereIn('nombre', array_values(self::MAPA_COLUMNA_A_NOMBRE))
            ->pluck('id', 'nombre');

        $ahora = now();
        $filas = [];

        foreach (DB::table('contratos')->select('id', 'costo_agua', 'costo_pasadizo', 'costo_seguridad')->cursor() as $contrato) {
            foreach (self::MAPA_COLUMNA_A_NOMBRE as $columna => $nombreConcepto) {
                $valor = $contrato->{$columna};

                if ($valor === null) {
                    continue;
                }

                $filas[] = [
                    'contrato_id' => $contrato->id,
                    'concepto_gasto_fijo_id' => $conceptos[$nombreConcepto],
                    'valor' => $valor,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }
        }

        foreach (array_chunk($filas, 500) as $lote) {
            DB::table('contrato_valores_concepto')->insert($lote);
        }
    }

    public function down(): void
    {
        DB::table('contrato_valores_concepto')->truncate();
    }
};
