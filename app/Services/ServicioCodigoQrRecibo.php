<?php

namespace App\Services;

use App\Models\Recibo;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\URL;

/**
 * specs/044 (US3): genera el código QR que se imprime en el comprobante de un
 * recibo y que abre la vista de cobro rápido. El QR codifica una URL firmada
 * (`URL::signedRoute`) — un id crudo no debe poder abrir el formulario de
 * pago (FR-023/FR-030). Sin expiración: un recibo impreso puede cobrarse
 * semanas después.
 */
class ServicioCodigoQrRecibo
{
    /** URL firmada, absoluta y no adulterable, hacia la vista de cobro del recibo. */
    public function enlace(Recibo $recibo): string
    {
        return URL::signedRoute('cobro.recibo', $recibo);
    }

    /**
     * PNG del QR como data-URI, listo para `<img src="...">`. Se usa PNG (no
     * SVG) para que la captura con html2canvas del comprobante (specs/031) no
     * tenga problemas. Si la generación falla (por ejemplo sin extensión GD),
     * devuelve `null` y el comprobante muestra el número de recibo en grande
     * como alternativa (research.md Decisión 8).
     */
    public function dataUri(Recibo $recibo, int $tamano = 220): ?string
    {
        try {
            return (new Builder(
                data: $this->enlace($recibo),
                size: $tamano,
                margin: 8,
            ))->build()->getDataUri();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Un número de recibo tecleado a mano es válido si es un entero positivo. */
    public function numeroEsValido(?string $numero): bool
    {
        return $numero !== null && $numero !== '' && ctype_digit(ltrim($numero, '#'));
    }

    /** Extrae el id numérico de un número tecleado (admite el prefijo "#"). */
    public function idDesdeNumero(string $numero): int
    {
        return (int) ltrim(trim($numero), '#');
    }
}
