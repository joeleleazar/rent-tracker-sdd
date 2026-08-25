<?php

namespace App\Console\Commands;

use App\Models\Inquilino;
use App\Models\LecturaMedidor;
use App\Models\Locacion;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('medidores:importar-historico {ruta=storage/app/private/import_medidores/extracted.json}')]
#[Description('Importa el historico de lecturas de medidor 2018-2019 (LECTURA DE MEDIDORES 2018.xlsx) ya normalizado a JSON por extract.py.')]
class ImportarLecturasMedidorHistoricas extends Command
{
    private const ETIQUETAS_NO_INQUILINO = [
        'SS.HH.', 'PASADIZO', 'COMEDOR', 'SALA CONFERENCIA',
    ];

    public function handle(): int
    {
        $ruta = base_path($this->argument('ruta'));

        if (! file_exists($ruta)) {
            $this->error("No existe el archivo: {$ruta}");

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($ruta), true, flags: JSON_THROW_ON_ERROR);
        $registros = collect($data['records'])
            ->filter(fn (array $r) => is_numeric($r['lectura_actual']))
            ->sortBy('periodo')
            ->values();

        $this->info("Registros con lectura numerica a importar: {$registros->count()}");

        DB::transaction(function () use ($registros) {
            $raiz = Locacion::create([
                'nombre' => 'Inmueble — Historico Medidores 2018-2019',
                'tamano' => 0,
                'ubicacion_fisica' => 'Importado desde LECTURA DE MEDIDORES 2018.xlsx',
                'descripcion' => 'Nodo raiz creado para agrupar el historico de lecturas de medidor importado desde la planilla original (mayo 2018 - marzo 2019).',
                'locacion_padre_id' => null,
                'es_alquilable' => false,
            ]);

            $zonas = [
                'A' => Locacion::create([
                    'nombre' => 'Zona A',
                    'tamano' => 0,
                    'ubicacion_fisica' => 'Zona A',
                    'descripcion' => 'Agrupacion de unidades con codigo A-xxx, tal como aparecen en la planilla historica de lecturas de medidor.',
                    'locacion_padre_id' => $raiz->id,
                    'es_alquilable' => false,
                ]),
                'B' => Locacion::create([
                    'nombre' => 'Zona B',
                    'tamano' => 0,
                    'ubicacion_fisica' => 'Zona B',
                    'descripcion' => 'Agrupacion de unidades con codigo B-xxx, tal como aparecen en la planilla historica de lecturas de medidor.',
                    'locacion_padre_id' => $raiz->id,
                    'es_alquilable' => false,
                ]),
            ];

            $locacionesPorCodigo = [];
            $inquilinosPorNombre = [];
            $ultimaLecturaPorLocacion = [];
            $mapeoParaReporte = [];

            $barra = $this->output->createProgressBar($registros->count());

            foreach ($registros as $r) {
                $codigo = $r['codigo'];
                $zona = str_starts_with($codigo, 'B-') ? 'B' : 'A';

                if (! isset($locacionesPorCodigo[$codigo])) {
                    $locacionesPorCodigo[$codigo] = Locacion::create([
                        'nombre' => $codigo,
                        'tamano' => 0,
                        'ubicacion_fisica' => 'Zona '.$zona,
                        'descripcion' => "Unidad importada del historico de lecturas de medidor 2018-2019 (planilla original: {$r['sheet']}, codigo original: {$r['codigo_raw']}). Tamano fisico pendiente de registrar manualmente.",
                        'locacion_padre_id' => $zonas[$zona]->id,
                        'es_alquilable' => true,
                    ]);
                    $mapeoParaReporte[$codigo] = [];
                }
                $mapeoParaReporte[$codigo][] = $r['sheet'].' -> '.$r['codigo_raw'].($r['nota_codigo'] ? ' ('.$r['nota_codigo'].')' : '');

                $nombre = $r['nombre'];
                if ($nombre !== null && ! in_array(strtoupper($nombre), self::ETIQUETAS_NO_INQUILINO, true)) {
                    if (! isset($inquilinosPorNombre[$nombre])) {
                        $inquilinosPorNombre[$nombre] = Inquilino::firstOrCreate(['nombre' => $nombre]);
                    }
                }

                $locacionId = $locacionesPorCodigo[$codigo]->id;
                $lecturaActual = round((float) $r['lectura_actual'], 2);
                $lecturaAnterior = $ultimaLecturaPorLocacion[$codigo] ?? null;
                $consumoCalculado = $lecturaAnterior === null ? null : round($lecturaActual - $lecturaAnterior, 2);

                LecturaMedidor::create([
                    'locacion_id' => $locacionId,
                    'periodo' => $r['periodo'],
                    'lectura_actual' => $lecturaActual,
                    'lectura_anterior' => $lecturaAnterior,
                    'consumo_calculado' => $consumoCalculado,
                    'fecha_registro' => $r['periodo'],
                ]);

                $ultimaLecturaPorLocacion[$codigo] = $lecturaActual;

                $barra->advance();
            }

            $barra->finish();
            $this->newLine(2);

            $this->info('Locaciones creadas: '.count($locacionesPorCodigo).' unidades + '.count($zonas).' zonas + 1 raiz.');
            $this->info('Inquilinos creados/reutilizados: '.count($inquilinosPorNombre));

            file_put_contents(
                base_path('storage/app/private/import_medidores/mapeo_codigos.json'),
                json_encode($mapeoParaReporte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        });

        return self::SUCCESS;
    }
}
