# Contrato de Interfaz: Rutas web de Estado, Impresión y Envío de Recibos

**Feature**: `007-estado-envio-recibo` | **Date**: 2026-08-20

Aplicación monolítica Laravel con vistas Blade server-rendered, consistente con `specs/001-006`. Rutas protegidas por `middleware(['auth'])`. Todas las rutas mutantes exigen CSRF.

## Estado de pago del recibo

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| PATCH | `/recibos/{recibo}/estado` | `ReciboController@actualizarEstado` | Cambia el `estado` del recibo (`pendiente`/`pagado`/`anulado`) | 302 en éxito; 422 si la transición involucra `anulado` sin `confirmado=true` (FR-004) |

**Body esperado**: `{ nuevo_estado: 'pendiente'|'pagado'|'anulado', confirmado: boolean }`. El parámetro `confirmado` solo es relevante (y obligatorio en `true`) cuando la transición entra o sale de `anulado`; se ignora para transiciones `pendiente ⇄ pagado`.

## Comprobante (impresión e imagen)

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/recibos/{recibo}/comprobante` | `ReciboController@comprobante` | Vista única del comprobante (conceptos, montos, periodo, estado), con marca "ANULADO" si corresponde (FR-009); botones "Imprimir Recibo" (`window.print()`) y "Enviar por WhatsApp" (captura `html2canvas` + `navigator.share`) | 200 |

No existe una ruta de servidor para "enviar por WhatsApp": la acción ocurre íntegramente en el navegador del Administrador (ver `research.md` §1); el servidor solo sirve el HTML del comprobante.

## Form Requests (validación de entrada)

- `SolicitudActualizarEstadoRecibo` (`actualizarEstado` de `ReciboController`): valida `nuevo_estado` (`required`, `in:pendiente,pagado,anulado`), `confirmado` (`boolean`, `required_if` la transición involucra `anulado`, verificado en `ServicioCambioEstadoRecibo` no solo en el Form Request para evitar condiciones de carrera).

## Errores y mensajes (Senior-First)

- Antes de invocar `PATCH /recibos/{recibo}/estado` hacia o desde `anulado`, la vista MUST mostrar un modal de confirmación con botones "Sí, anular recibo" / "No, cancelar" (o el texto equivalente para revertir), consistente con el Principio III.
- La marca "ANULADO" MUST ser visible y de alto contraste en el comprobante (impreso o capturado como imagen) de cualquier recibo en ese estado (FR-009), sin excepción.
- Si `navigator.share` no está disponible o falla (ej. sin WhatsApp instalado), el JS MUST ofrecer una alternativa de descarga de la imagen generada (Edge Case "Recibo sin conexión a WhatsApp instalado"), con un mensaje explícito indicando que no se pudo completar el envío directo.
