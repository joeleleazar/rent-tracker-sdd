<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FR-007: reestructura `configuracion_general` de una fila ancha (una columna
 * por parámetro) a una tabla clave-valor, de forma que agregar un parámetro
 * nuevo en el futuro sea insertar una fila, no una migración de esquema (ver
 * research.md R3, data-model.md). `timestampsTz()` desde el origen evita
 * cualquier dependencia de orden con la migración de timestamptz de FR-005.
 */
return new class extends Migration
{
    private const CLAVES_CONOCIDAS = [
        'correo_notificaciones_vencimiento',
        'tarifa_luz_por_unidad',
        'dias_anticipacion_alerta_pago',
        'alerta_pago_mes_enviada_en',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $filaExistente = DB::table('configuracion_general')->first();

            Schema::drop('configuracion_general');

            Schema::create('configuracion_general', function (Blueprint $table) {
                $table->id();
                $table->string('clave')->unique();
                $table->jsonb('valor');
                $table->timestampsTz();
            });

            if ($filaExistente === null) {
                return;
            }

            $ahora = now();

            $filas = collect(self::CLAVES_CONOCIDAS)
                ->filter(fn ($clave) => property_exists($filaExistente, $clave) && $filaExistente->{$clave} !== null)
                ->map(fn ($clave) => [
                    'clave' => $clave,
                    'valor' => json_encode($filaExistente->{$clave}),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ])
                ->values()
                ->all();

            if (! empty($filas)) {
                DB::table('configuracion_general')->insert($filas);
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $valores = DB::table('configuracion_general')->pluck('valor', 'clave');

            Schema::drop('configuracion_general');

            Schema::create('configuracion_general', function (Blueprint $table) {
                $table->id();
                $table->string('correo_notificaciones_vencimiento');
                $table->timestamps();
                $table->decimal('tarifa_luz_por_unidad', 12, 4)->default(0);
                $table->unsignedInteger('dias_anticipacion_alerta_pago')->default(5);
                $table->timestamp('alerta_pago_mes_enviada_en')->nullable();
            });

            DB::table('configuracion_general')->insert([
                'id' => 1,
                'correo_notificaciones_vencimiento' => isset($valores['correo_notificaciones_vencimiento'])
                    ? json_decode($valores['correo_notificaciones_vencimiento'], true)
                    : config('mail.from.address', 'hello@example.com'),
                'tarifa_luz_por_unidad' => isset($valores['tarifa_luz_por_unidad']) ? json_decode($valores['tarifa_luz_por_unidad'], true) : 0,
                'dias_anticipacion_alerta_pago' => isset($valores['dias_anticipacion_alerta_pago']) ? json_decode($valores['dias_anticipacion_alerta_pago'], true) : 5,
                'alerta_pago_mes_enviada_en' => isset($valores['alerta_pago_mes_enviada_en']) ? json_decode($valores['alerta_pago_mes_enviada_en'], true) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
};
