<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * specs/044 (US2): lee el archivo de importación de recibos (.xlsx o .csv) y
 * expone las filas crudas y la lista de encabezados detectados. La validación
 * por fila, el mapeo dinámico de columnas de concepto y el upsert viven en
 * ServicioImportacionRecibos.
 */
class ImportacionRecibosImport implements ToCollection, WithHeadingRow
{
    /** Encabezados slug siempre requeridos. */
    public const COLUMNAS_REQUERIDAS = ['periodo', 'local_id', 'total'];

    public Collection $filas;

    /** @var array<int, string> */
    public array $encabezados = [];

    public function __construct()
    {
        $this->filas = collect();
    }

    public function collection(Collection $filas): void
    {
        $this->filas = $filas;
        $this->encabezados = $filas->isNotEmpty() ? array_keys($filas->first()->toArray()) : [];
    }

    /**
     * @return array<int, string>
     */
    public function columnasFaltantes(): array
    {
        if ($this->filas->isEmpty()) {
            return self::COLUMNAS_REQUERIDAS;
        }

        return array_values(array_diff(self::COLUMNAS_REQUERIDAS, $this->encabezados));
    }
}
