<?php

namespace App\Exceptions;

use App\Models\ConceptoGastoFijo;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * specs/023: reemplaza a ReciboDuplicadoPeriodoException — la regla de negocio pasó de
 * "un solo recibo por locación y periodo" a "ningún concepto puede repetirse entre los
 * recibos de una misma locación y periodo". specs/024: los conceptos superpuestos ya
 * no son claves fijas (`incluye_agua`, ...) sino instancias reales de `ConceptoGastoFijo`
 * del catálogo dinámico — el mensaje usa su `nombre` vigente, no un texto codificado.
 */
class ConceptosReciboYaCubiertosException extends RuntimeException
{
    /**
     * @param  Collection<int, ConceptoGastoFijo>  $conceptosSuperpuestos
     * @param  Collection<int, \App\Models\Recibo>  $recibosExistentes  recibos que las cubren
     */
    public function __construct(
        public readonly Collection $conceptosSuperpuestos,
        public readonly Collection $recibosExistentes,
    ) {
        $nombres = $conceptosSuperpuestos->pluck('nombre')->implode(', ');

        parent::__construct("Los siguientes conceptos ya están cubiertos por otro recibo de este periodo: {$nombres}.");
    }
}
