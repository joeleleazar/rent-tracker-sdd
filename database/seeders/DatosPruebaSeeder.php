<?php

namespace Database\Seeders;

use App\Models\ConceptoGastoFijo;
use App\Models\ConfiguracionGeneral;
use App\Models\Contrato;
use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use App\Models\Pago;
use App\Models\Recibo;
use App\Models\User;
use App\Models\ValorConceptoContrato;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Set de datos para PRUEBAS manuales (no automatizadas): 10 locales
 * alquilables, cada uno con un contrato activo, lecturas de medidor de julio y
 * agosto 2026, recibos de agosto por pagar y recibos de julio ya pagados.
 *
 * Es destructivo sobre las tablas del dominio (locaciones, inquilinos,
 * contratos, lecturas, recibos, pagos) para poder re-ejecutarse: NO tocar en
 * una base con datos reales. Conserva usuarios, catálogo de conceptos y
 * configuración general.
 *
 *   php artisan db:seed --class=Database\\Seeders\\DatosPruebaSeeder
 */
class DatosPruebaSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Periodos de trabajo (siempre día 1: lo exige el CHECK check_periodo_dia_uno). */
    private Carbon $julio;

    private Carbon $agosto;

    public function run(): void
    {
        $this->julio = Carbon::create(2026, 7, 1)->startOfDay();
        $this->agosto = Carbon::create(2026, 8, 1)->startOfDay();

        $this->limpiarDominio();

        // --- Tarifa de luz usada para valorizar las lecturas ------------------
        $config = ConfiguracionGeneral::actual();
        $config->tarifa_luz_por_unidad = 0.85;
        if (empty($config->correo_notificaciones_vencimiento)) {
            $config->correo_notificaciones_vencimiento = 'admin@rent-tracker.test';
        }
        $config->save();
        $tarifa = 0.85;

        // --- Catálogo de conceptos (sembrado por migración) ------------------
        $conceptoAgua = ConceptoGastoFijo::firstWhere('nombre', 'Agua');
        $conceptoLuz = ConceptoGastoFijo::firstWhere('clave', 'luz');
        $conceptoPasadizo = ConceptoGastoFijo::firstWhere('nombre', 'Luz de Pasadizo');
        $conceptoSeguridad = ConceptoGastoFijo::firstWhere('nombre', 'Seguridad');

        $registradoPor = User::query()->orderBy('id')->value('id');

        // --- Jerarquía contenedora -----------------------------------------
        $galeria = Locacion::create([
            'nombre' => 'Galería Central',
            'tamano' => 800,
            'ubicacion_fisica' => 'Av. Mercaderes 450, Arequipa',
            'descripcion' => 'Galería comercial de dos pisos con 10 locales.',
            'locacion_padre_id' => null,
            'es_alquilable' => false,
            'tipo' => 'galeria',
        ]);

        $pisos = [];
        foreach ([1, 2] as $numeroPiso) {
            $pisos[$numeroPiso] = Locacion::create([
                'nombre' => "Piso {$numeroPiso}",
                'tamano' => 400,
                'ubicacion_fisica' => $numeroPiso === 1 ? 'Nivel calle' : 'Segundo nivel',
                'descripcion' => "Piso {$numeroPiso} de la Galería Central.",
                'locacion_padre_id' => $galeria->id,
                'es_alquilable' => false,
                'tipo' => 'piso',
            ]);
        }

        // --- Nombres de inquilinos de ejemplo ------------------------------
        $personas = [
            ['Quispe Mamani', 'Lucía'],
            ['Huamán Torres', 'Diego'],
            ['Flores Ccama', 'Rosa'],
            ['Choque Apaza', 'Julio'],
            ['Vargas Ríos', 'Carmen'],
            ['Condori Nina', 'Marco'],
            ['Salas Pauca', 'Elena'],
            ['Ticona Cruz', 'Hernán'],
            ['Ramos Zúñiga', 'Patricia'],
            ['Mendoza Álvarez', 'Óscar'],
        ];

        $resumen = [];

        for ($i = 1; $i <= 10; $i++) {
            $piso = $i <= 5 ? 1 : 2;
            $numeroLocal = $piso === 1 ? 100 + $i : 200 + ($i - 5);

            $local = Locacion::create([
                'nombre' => "Local {$numeroLocal}",
                'tamano' => 18 + ($i % 4) * 4,
                'ubicacion_fisica' => "Piso {$piso}, módulo {$numeroLocal}",
                'descripcion' => "Local comercial {$numeroLocal} de la Galería Central.",
                'locacion_padre_id' => $pisos[$piso]->id,
                'es_alquilable' => true,
                'tipo' => 'local',
            ]);

            [$apellidos, $nombres] = $personas[$i - 1];
            $inquilino = Inquilino::create([
                'apellidos' => $apellidos,
                'nombres' => $nombres,
                'dni' => str_pad((string) (40000000 + $i), 8, '0', STR_PAD_LEFT),
                'fecha_nacimiento' => Carbon::create(1970 + $i, ($i % 12) + 1, 10)->format('Y-m-d'),
            ]);

            $montoRenta = 700 + $i * 50;                // 750 .. 1200
            $valorAgua = 35 + ($i % 3) * 5;             // 35 / 40 / 45
            $valorPasadizo = 10;
            $valorSeguridad = 20;

            $contrato = Contrato::create([
                'locacion_id' => $local->id,
                'fecha_inicio' => Carbon::create(2026, 1, 1)->addMonths(($i - 1) % 4)->startOfMonth()->format('Y-m-d'),
                'fecha_fin' => Carbon::create(2027, 6, 30)->format('Y-m-d'),
                'monto_renta' => $montoRenta,
                'estado' => 'activo',
                'monto_garantia' => $montoRenta,
                'fecha_entrega_garantia' => Carbon::create(2026, 1, 1)->format('Y-m-d'),
                'medio_entrega_garantia' => $i % 2 === 0 ? 'transferencia' : 'efectivo',
                'estado_garantia' => 'entregada',
            ]);
            $contrato->inquilinos()->attach($inquilino->id, ['es_principal' => true]);

            foreach ([
                $conceptoAgua->id => $valorAgua,
                $conceptoPasadizo->id => $valorPasadizo,
                $conceptoSeguridad->id => $valorSeguridad,
            ] as $conceptoId => $valor) {
                ValorConceptoContrato::create([
                    'contrato_id' => $contrato->id,
                    'concepto_gasto_fijo_id' => $conceptoId,
                    'valor' => $valor,
                ]);
            }

            // --- Lecturas de medidor: julio y agosto -----------------------
            $lecturaBase = 1000 + $i * 130;
            $consumoJulio = 90 + ($i % 5) * 15;         // 90 .. 150
            $consumoAgosto = 100 + (($i + 2) % 5) * 15; // 100 .. 160

            $lecturaJulioActual = $lecturaBase + $consumoJulio;
            $lecturaAgostoActual = $lecturaJulioActual + $consumoAgosto;

            $lecturaJulio = LecturaMedidor::create([
                'locacion_id' => $local->id,
                'periodo' => $this->julio->format('Y-m-d'),
                'lectura_anterior' => $lecturaBase,
                'lectura_actual' => $lecturaJulioActual,
                'total' => round($consumoJulio * $tarifa, 2),
                'fecha_registro' => $this->julio->copy()->addDays(2),
            ]);

            $lecturaAgosto = LecturaMedidor::create([
                'locacion_id' => $local->id,
                'periodo' => $this->agosto->format('Y-m-d'),
                'lectura_anterior' => $lecturaJulioActual,
                'lectura_actual' => $lecturaAgostoActual,
                'total' => round($consumoAgosto * $tarifa, 2),
                'fecha_registro' => $this->agosto->copy()->addDays(2),
            ]);

            // --- Recibo de julio: ya PAGADO -------------------------------
            $reciboJulio = Recibo::create([
                'contrato_id' => $contrato->id,
                'locacion_id' => $local->id,
                'lectura_medidor_id' => $lecturaJulio->id,
                'monto_renta' => $montoRenta,
                'periodo' => $this->julio->format('Y-m-d'),
                'fecha_emision' => $this->julio->copy()->addDays(3)->format('Y-m-d'),
                'estado' => 'pagado',
                'fecha_pago' => $this->julio->copy()->addDays(9),
            ]);
            $montosJulio = [
                $conceptoAgua->id => $valorAgua,
                $conceptoLuz->id => (float) $lecturaJulio->total,
                $conceptoPasadizo->id => $valorPasadizo,
                $conceptoSeguridad->id => $valorSeguridad,
            ];
            foreach ($montosJulio as $conceptoId => $monto) {
                $reciboJulio->conceptos()->create([
                    'concepto_gasto_fijo_id' => $conceptoId,
                    'monto' => $monto,
                ]);
            }
            Pago::create([
                'recibo_id' => $reciboJulio->id,
                'monto' => $montoRenta + array_sum($montosJulio),
                'fecha_pago' => $this->julio->copy()->addDays(9)->format('Y-m-d'),
                'registrado_por_id' => $registradoPor,
            ]);

            // --- Recibo de agosto: PENDIENTE (por pagar) ------------------
            $reciboAgosto = Recibo::create([
                'contrato_id' => $contrato->id,
                'locacion_id' => $local->id,
                'lectura_medidor_id' => $lecturaAgosto->id,
                'monto_renta' => $montoRenta,
                'periodo' => $this->agosto->format('Y-m-d'),
                'fecha_emision' => $this->agosto->copy()->addDays(3)->format('Y-m-d'),
                'estado' => 'pendiente',
            ]);
            $montosAgosto = [
                $conceptoAgua->id => $valorAgua,
                $conceptoLuz->id => (float) $lecturaAgosto->total,
                $conceptoPasadizo->id => $valorPasadizo,
                $conceptoSeguridad->id => $valorSeguridad,
            ];
            foreach ($montosAgosto as $conceptoId => $monto) {
                $reciboAgosto->conceptos()->create([
                    'concepto_gasto_fijo_id' => $conceptoId,
                    'monto' => $monto,
                ]);
            }

            $resumen[] = sprintf(
                '  %-10s  %-22s  renta %6.2f  luz ago %6.2f  total ago %7.2f',
                $local->nombre,
                "{$apellidos}, {$nombres}",
                $montoRenta,
                (float) $lecturaAgosto->total,
                $montoRenta + array_sum($montosAgosto),
            );
        }

        $this->command?->info('');
        $this->command?->info('DatosPruebaSeeder — 10 locales alquilables con contrato activo:');
        foreach ($resumen as $linea) {
            $this->command?->info($linea);
        }
        $this->command?->info('');
        $this->command?->info('  Lecturas de medidor: julio y agosto 2026 (tarifa 0.85 / unidad).');
        $this->command?->info('  Recibos de agosto 2026: PENDIENTES (por pagar).');
        $this->command?->info('  Recibos de julio 2026: PAGADOS, con su pago registrado.');
    }

    /**
     * Vacía las tablas del dominio en orden seguro para las claves foráneas.
     */
    private function limpiarDominio(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ([
            'pagos',
            'recibo_conceptos',
            'recibos',
            'borradores_recibo',
            'borradores_lectura_medidor',
            'contrato_valores_concepto',
            'contrato_inquilino',
            'documentos_contrato',
            'lecturas_medidor',
            'contratos',
            'inquilinos',
            'locaciones',
        ] as $tabla) {
            if (Schema::hasTable($tabla)) {
                DB::table($tabla)->delete();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
