<?php

namespace App\Exports;

use App\Services\ServicioPlantillaLecturas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * specs/044 (US1): plantilla descargable de carga masiva de lecturas de luz
 * para un periodo. Una fila por locación alquilable; la primera columna
 * `periodo` es técnica y permite rechazar al importar un archivo generado
 * para otro periodo (FR-010).
 */
class PlantillaLecturasExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly Carbon $periodo,
        private readonly ServicioPlantillaLecturas $servicio,
    ) {}

    public function collection(): Collection
    {
        return collect($this->servicio->filas($this->periodo))->map(fn (array $fila) => [
            $fila['periodo'],
            $fila['local_id'],
            $fila['Locación'],
            $fila['Lectura Periodo Anterior'],
            $fila['Lectura Actual'],
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ServicioPlantillaLecturas::ENCABEZADOS;
    }
}
