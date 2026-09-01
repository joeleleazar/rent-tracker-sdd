<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * specs/044 (US1): lee el archivo de importación de lecturas (.xlsx o .csv) y
 * expone las filas crudas mapeadas por encabezado. La validación por fila y el
 * upsert viven en ServicioImportacionLecturas — este import solo parsea.
 *
 * Con WithHeadingRow los encabezados llegan "slugificados": `local_id`,
 * `periodo`, `locacion`, `lectura_periodo_anterior`, `lectura_actual`.
 */
class ImportacionLecturasImport implements ToCollection, WithHeadingRow
{
    /** Encabezados slug esperados (los que consume el servicio). */
    public const COLUMNAS_REQUERIDAS = ['periodo', 'local_id', 'lectura_actual'];

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
     * Devuelve las columnas requeridas ausentes en el archivo (para el rechazo
     * 422 de FR-010).
     *
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
