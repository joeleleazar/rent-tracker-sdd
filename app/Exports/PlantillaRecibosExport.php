<?php

namespace App\Exports;

use App\Services\ServicioPlantillaRecibos;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * specs/044 (US2): plantilla descargable de carga masiva de recibos para un
 * periodo. Columnas dinámicas — "Renta" y "Luz" fijas más una por cada
 * concepto de gasto fijo activo (specs/024). La primera columna `periodo` es
 * técnica (FR-010).
 */
class PlantillaRecibosExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly Carbon $periodo,
        private readonly ServicioPlantillaRecibos $servicio,
    ) {
    }

    public function collection(): Collection
    {
        $encabezados = $this->servicio->encabezados();

        return collect($this->servicio->filas($this->periodo))->map(
            fn (array $fila) => array_map(fn (string $col) => $fila[$col] ?? null, $encabezados),
        );
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->servicio->encabezados();
    }
}
