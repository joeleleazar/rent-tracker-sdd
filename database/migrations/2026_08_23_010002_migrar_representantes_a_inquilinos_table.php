<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolida el modelo de dos entidades (Inquilino simple + Representante
 * separado, ver specs/003-representantes-contrato) en un único directorio de
 * Inquilinos, sin perder datos ya existentes.
 *
 * Política de migración (ver research.md §1 de la feature 003, revisión
 * 2026-08-23): el `Inquilino` ya referenciado por `contratos.inquilino_id`
 * se preserva como el Inquilino Principal de cada contrato (era ya, en la
 * práctica, "el inquilino del contrato" antes de esta corrección). Los
 * `Representante` existentes no tienen forma confiable de correlacionarse
 * con un `Inquilino` ya existente (no compartían DNI ni ningún identificador
 * común), por lo que se migran como registros nuevos en `inquilinos` y se
 * asocian a sus contratos como inquilinos adicionales (no Principal), para
 * no arriesgar una fusión de identidad incorrecta ni violar la regla de
 * "exactamente un Principal" con datos ambiguos.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            // 1) Cada contrato ya tiene, por definición, exactamente un inquilino
            //    (el de contratos.inquilino_id, NOT NULL desde specs/002): se
            //    convierte en la fila Principal del nuevo pivote.
            DB::table('contratos')->select('id', 'inquilino_id')->orderBy('id')->get()->each(function ($contrato) {
                DB::table('contrato_inquilino')->insert([
                    'contrato_id' => $contrato->id,
                    'inquilino_id' => $contrato->inquilino_id,
                    'es_principal' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

            // 2) Cada Representante se migra a un nuevo registro de Inquilino
            //    (con sus datos personales completos), manteniendo un mapa
            //    representante_id => inquilino_id para reescribir el pivote.
            $mapaRepresentanteAInquilino = [];

            DB::table('representantes')->orderBy('id')->get()->each(function ($representante) use (&$mapaRepresentanteAInquilino) {
                $mapaRepresentanteAInquilino[$representante->id] = DB::table('inquilinos')->insertGetId([
                    // 'nombre' todavía es NOT NULL en este punto de la consolidación
                    // (se elimina recién en la migración siguiente); se completa con
                    // el mismo formato "Apellidos, Nombres" que ya usaban los
                    // registros de Inquilino existentes, para no violar la restricción.
                    'nombre' => "{$representante->apellidos}, {$representante->nombres}",
                    'apellidos' => $representante->apellidos,
                    'nombres' => $representante->nombres,
                    'dni' => $representante->dni,
                    'fecha_nacimiento' => $representante->fecha_nacimiento,
                    'created_at' => $representante->created_at,
                    'updated_at' => now(),
                ]);
            });

            // 3) Cada asociación contrato_representante se reescribe como
            //    contrato_inquilino, siempre como no-Principal (ver política
            //    arriba), evitando duplicar la fila si por alguna razón ya
            //    existiera (contrato_id, inquilino_id) — no debería ocurrir,
            //    pero se usa insertOrIgnore por seguridad ante reintentos.
            DB::table('contrato_representante')->orderBy('id')->get()->each(function ($fila) use ($mapaRepresentanteAInquilino) {
                DB::table('contrato_inquilino')->insertOrIgnore([
                    'contrato_id' => $fila->contrato_id,
                    'inquilino_id' => $mapaRepresentanteAInquilino[$fila->representante_id],
                    'es_principal' => false,
                    'created_at' => $fila->created_at,
                    'updated_at' => now(),
                ]);
            });
        });
    }

    public function down(): void
    {
        // Migración de datos de un único sentido: revertirla exigiría
        // reconstruir representantes/contrato_representante desde
        // contrato_inquilino, lo cual es ambiguo (no hay forma de distinguir
        // qué filas de contrato_inquilino vinieron de representantes vs. del
        // inquilino_id original). No se soporta rollback automático.
    }
};
