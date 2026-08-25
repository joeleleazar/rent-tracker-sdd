<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * FR-016 (specs/015): exporta exactamente las mismas filas que ve el usuario
 * en la pantalla de registro masivo (completadas y pendientes), reunidas por
 * `RegistroMasivoLecturasController::filasExportables()` a partir de la misma
 * consulta que `index()`, para que el contenido nunca se desincronice.
 */
class ExportacionRegistroMasivoLecturas implements FromCollection, WithHeadings
{
    /**
     * @param  array<int, array{ubicacion: string, lectura_anterior: string|null, lectura_actual: string|null, consumo: string|null, total: float|null}>  $filas
     */
    public function __construct(private readonly array $filas)
    {
    }

    public function collection(): Collection
    {
        return collect($this->filas)->map(fn (array $fila) => [
            $fila['ubicacion'],
            $fila['lectura_anterior'],
            $fila['lectura_actual'],
            $fila['consumo'],
            $fila['total'],
        ]);
    }

    public function headings(): array
    {
        return ['Local', 'Lectura Periodo Anterior', 'Lectura Actual', 'Consumo (kWh)', 'Total (S/)'];
    }
}
