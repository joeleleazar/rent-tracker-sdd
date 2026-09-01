<?php

namespace App\Support\Importacion;

/**
 * specs/044: recuento del resultado de confirmar una importación por plantilla.
 * `mensaje()` arma el resumen efímero que ve el usuario (FR-009).
 */
class ResultadoImportacion
{
    public function __construct(
        public int $creadas = 0,
        public int $actualizadas = 0,
        public int $omitidas = 0,
    ) {}

    public function registrarCreacion(): void
    {
        $this->creadas++;
    }

    public function registrarActualizacion(): void
    {
        $this->actualizadas++;
    }

    public function registrarOmision(): void
    {
        $this->omitidas++;
    }

    public function totalProcesadas(): int
    {
        return $this->creadas + $this->actualizadas + $this->omitidas;
    }

    public function nadaGuardado(): bool
    {
        return $this->creadas === 0 && $this->actualizadas === 0;
    }

    /**
     * @param  'femenino'|'masculino'  $genero  Concordancia: "creadas"/"actualizadas" (lecturas) vs "creados"/"actualizados" (recibos).
     */
    public function mensaje(string $genero = 'femenino'): string
    {
        $a = $genero === 'masculino' ? 'creados' : 'creadas';
        $b = $genero === 'masculino' ? 'actualizados' : 'actualizadas';
        $c = $genero === 'masculino' ? 'omitidos' : 'omitidas';

        return "Importación: {$this->creadas} {$a}, {$this->actualizadas} {$b}, {$this->omitidas} {$c}.";
    }
}
