# Quickstart: Estado de Recibos y Envío por WhatsApp o Impresión

**Feature**: `007-estado-envio-recibo` | **Date**: 2026-08-20

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-estado-envio-recibo.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001-006` ya ejecutadas.
- Migración de esta feature ejecutada (`recibos` con `estado`/`fecha_pago`/`fecha_anulacion`).
- `npm install && npm run build` ejecutado (incluye `html2canvas`).
- Usuario autenticado.

## Escenario 1 — Marcar el estado de pago (US1)

1. Emitir un recibo y consultar su detalle.
2. **Resultado esperado**: se muestra el estado "Pendiente" con indicador de alto contraste.
3. Presionar "Marcar como Pagado".
4. **Resultado esperado**: el estado cambia a "Pagado", se registra `fecha_pago`, y se refleja de inmediato en el listado.
5. Presionar "Anular Recibo" (incluso estando "Pagado").
6. **Resultado esperado**: aparece el modal de confirmación ("Sí, anular recibo" / "No, cancelar"); al confirmar, el estado cambia a "Anulado" y `fecha_pago` se limpia mientras se asigna `fecha_anulacion`.
7. Presionar "Revertir Anulación" y elegir "Pendiente".
8. **Resultado esperado**: se exige confirmación explícita antes de aplicar el cambio; al confirmar, `fecha_anulacion` se limpia.

## Escenario 2 — Envío como imagen por WhatsApp (US2)

1. Abrir el detalle de un recibo emitido y navegar a `/recibos/{recibo}/comprobante`.
2. Presionar "Enviar por WhatsApp".
3. **Resultado esperado**: el navegador genera una imagen legible del comprobante (todos los conceptos y montos) y abre el selector nativo de compartir (`navigator.share`), permitiendo elegir WhatsApp.
4. Confirmar el envío desde el selector nativo.
5. **Resultado esperado**: se muestra un indicador de éxito persistente confirmando que la imagen quedó lista para compartirse.

## Escenario 3 — Impresión del recibo (US3)

1. Desde `/recibos/{recibo}/comprobante`, presionar "Imprimir Recibo".
2. **Resultado esperado**: se abre el diálogo de impresión del navegador con una vista clara (tipografía ≥18px), incluyendo todos los conceptos, montos, periodo y estado actual.

## Escenario 4 — Recibo anulado con marca visible (Edge Case)

1. Anular un recibo y volver a abrir `/recibos/{recibo}/comprobante`.
2. **Resultado esperado**: tanto la vista de impresión como la imagen generada muestran la marca "ANULADO" de forma visible y de alto contraste.

## Escenario 5 — WhatsApp no disponible (Edge Case)

1. En un navegador/dispositivo sin soporte de `navigator.share` con archivos (o sin WhatsApp instalado), presionar "Enviar por WhatsApp".
2. **Resultado esperado**: el sistema informa que no se pudo completar el envío directo y ofrece descargar/guardar la imagen generada para compartirla por otro medio.

## Validación automatizada (referencia)

```bash
php artisan test --filter=Recibo
```

**Cobertura esperada** (Principio IV): modelo `Recibo` (transiciones libres, limpieza de fechas), `ServicioCambioEstadoRecibo` (exigencia de confirmación hacia/desde anulado), `ReciboController@actualizarEstado` (happy path, 422 sin confirmación), `ReciboController@comprobante` (marca ANULADO presente en el HTML cuando corresponde).
