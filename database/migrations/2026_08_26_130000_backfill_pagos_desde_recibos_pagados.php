<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * specs/032: hallazgo durante la implementación — recibos ya marcados
 * "pagado" bajo el sistema manual anterior (antes de esta feature) no tienen
 * ninguna fila en `pagos`, así que `montoPagado()`/`saldoPendiente()` los
 * mostraría como "sin pagos" pese a que `recibos.estado` sigue diciendo
 * "pagado" — una contradicción visible en `recibos/show.blade.php` (el badge
 * de arriba dice "Pagado" mientras la sección de Pagos dice "todavía no se
 * registró ningún pago"). Se reconstruye, para cada uno, un pago histórico
 * por el total ya calculado del recibo, fechado en su `fecha_pago` original
 * (o `fecha_emision` si no la tuviera) — así el estado ya calculado sigue
 * respaldado por evidencia real en vez de revertir silenciosamente un recibo
 * que el negocio ya consideraba pagado.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $recibosPagadosSinPagos = DB::table('recibos')
                ->where('estado', 'pagado')
                ->whereNotExists(function ($consulta) {
                    $consulta->select(DB::raw(1))
                        ->from('pagos')
                        ->whereColumn('pagos.recibo_id', 'recibos.id');
                })
                ->get();

            foreach ($recibosPagadosSinPagos as $recibo) {
                $montoConceptos = (float) (DB::table('recibo_conceptos')
                    ->where('recibo_id', $recibo->id)
                    ->sum('monto') ?? 0);
                $total = (float) ($recibo->monto_renta ?? 0) + $montoConceptos;

                if ($total <= 0) {
                    continue;
                }

                DB::table('pagos')->insert([
                    'recibo_id' => $recibo->id,
                    'monto' => $total,
                    'fecha_pago' => $recibo->fecha_pago ?? $recibo->fecha_emision ?? now(),
                    'registrado_por_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * No se revierte: no hay forma de distinguir un pago reconstruido por
     * este backfill de uno real ingresado después de correrlo.
     */
    public function down(): void
    {
    }
};
