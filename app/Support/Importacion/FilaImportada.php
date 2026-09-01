<?php

namespace App\Support\Importacion;

/**
 * specs/044: una fila de la vista previa de una importación por plantilla
 * (lecturas o recibos). Artefacto transitorio — vive solo durante la petición
 * de previsualización y no se persiste (FR-013).
 */
class FilaImportada
{
    public const ACCION_CREAR = 'crear';

    public const ACCION_ACTUALIZAR = 'actualizar';

    public const ACCION_OMITIR = 'omitir';

    /**
     * @param  array<string, mixed>  $valores  Valores editables de la fila (ej. lectura_actual, renta, conceptos…).
     * @param  array<int, string>  $motivos  Textos de validación cuando la fila es inválida.
     */
    public function __construct(
        public readonly ?int $localId,
        public readonly string $nombre,
        public array $valores,
        public bool $valida = true,
        public array $motivos = [],
        public string $accion = self::ACCION_OMITIR,
        /** Error que el usuario NO puede corregir editando celdas (local inexistente, periodo distinto…). */
        public bool $errorNoRecuperable = false,
    ) {}

    public function invalidar(string $motivo, bool $noRecuperable = false): void
    {
        $this->valida = false;
        $this->accion = self::ACCION_OMITIR;
        $this->motivos[] = $motivo;

        if ($noRecuperable) {
            $this->errorNoRecuperable = true;
        }
    }

    public function esCreacion(): bool
    {
        return $this->valida && $this->accion === self::ACCION_CREAR;
    }

    public function esActualizacion(): bool
    {
        return $this->valida && $this->accion === self::ACCION_ACTUALIZAR;
    }
}
