# Contrato: comprobante de un pago

## Ruta

| Método | Ruta | Nombre | Acción |
|---|---|---|---|
| GET | `/pagos/{pago}/comprobante` | `pagos.comprobante` | Muestra el comprobante imprimible de ese pago |

Requiere sesión autenticada. Vista standalone (sin el layout de sidebar compartido), Bootstrap 5 real
(research.md Decisión 1) — enlazada desde `recibos/show.blade.php` con `hx-boost="false"`, mismo criterio
que ya usa el enlace "Ver Comprobante" del recibo.

## Contenido (bloques, de arriba hacia abajo)

1. **Encabezado**: logo institucional (ya usado en el resto de comprobantes, specs/030) + título
   "Comprobante de Pago".
2. **Metadatos**: N.° de recibo, N.° de pago, fecha del pago.
3. **Partes**: Recibí de (inquilino), Locación, Recibido por (si `ConfiguracionGeneral::actual()->nombre_propietario`
   está configurado — mismo criterio condicional que specs/031 FR-005/FR-005a).
4. **Monto de este pago**: el elemento más destacado del documento (mismo criterio de énfasis visual que
   specs/031 exige para el total del recibo completo) — spec.md FR-002.
5. **Avance del recibo**: total del recibo, pagado hasta ahora (acumulado), saldo pendiente — spec.md
   FR-002.
6. **Firma**: espacio de firma rotulado para quien recibe el pago — spec.md FR-003.
7. **Cierre**: frase breve de cierre (opcional, mismo espíritu que specs/031 bloque 6).

## Impresión

`@media print` (vía las utilidades nativas de Bootstrap `d-print-none`) oculta los controles de pantalla
(botón "Imprimir", enlace "Volver al Recibo") — spec.md FR-004, mismo criterio ya exigido para el
comprobante del recibo completo (specs/031 FR-013).

## Casos sin datos

- Si el pago no tiene `registradoPor` cargado, esa fila simplemente no se muestra (mismo criterio ya usado
  para otros datos condicionales de los comprobantes existentes).
- Si `ConfiguracionGeneral::actual()->nombre_propietario` está vacío, la fila "Recibido por" no se muestra
  (specs/031 FR-005a).
