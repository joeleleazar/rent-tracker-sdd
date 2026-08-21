# Contrato de Interfaz: Rutas web de Garantía de Contrato

**Feature**: `009-garantia-contrato` | **Date**: 2026-08-20

Aplicación monolítica Laravel con vistas Blade server-rendered, consistente con `specs/001-008`. Rutas protegidas por `middleware(['auth'])`. Todas las rutas mutantes exigen CSRF.

## Registro inicial de garantía

Los campos `monto_garantia`/`fecha_entrega_garantia`/`medio_entrega_garantia` se editan como parte del formulario ya existente de `ContratoController@store`/`@update` (`specs/002`, ya extendido por `specs/004` con los costos fijos); no se agregan rutas nuevas para su registro inicial.

## Resolución de la garantía

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| POST | `/contratos/{contrato}/garantia/resolucion` | `ContratoController@registrarResolucionGarantia` | Registra (o corrige, con confirmación) la resolución de la garantía: monto devuelto, monto retenido, motivo | 302 en éxito; 422 si la suma no cuadra con `monto_garantia` (FR-008), si falta el motivo con retención > 0 (FR-007), o si se intenta corregir una resolución ya `resuelta` sin `confirmado=true` (FR-010) |

**Body esperado**: `{ monto_devuelto_garantia: decimal, monto_retenido_garantia: decimal, motivo_retencion_garantia: string|null, confirmado: boolean }`.

**Precondición (Edge Case)**: la acción MUST estar disponible únicamente si `Contrato::tieneGarantia()` es verdadero; si el contrato no tiene `monto_garantia > 0`, la vista no ofrece la opción de registrar una resolución.

## Form Requests (validación de entrada)

- `SolicitudGuardarContrato` (extendida de `specs/002`/`004`): agrega `monto_garantia` (`numeric`, `nullable`, `min:0`), `fecha_entrega_garantia` (`date`, `required_if:monto_garantia,>,0`), `medio_entrega_garantia` (`nullable`, `in:efectivo,transferencia,cheque`).
- `SolicitudRegistrarResolucionGarantia` (`registrarResolucionGarantia` de `ContratoController`): valida `monto_devuelto_garantia`/`monto_retenido_garantia` (`numeric`, `required`, `min:0`), `motivo_retencion_garantia` (`required_if` monto retenido > 0), `confirmado` (`boolean`).

## Errores y mensajes (Senior-First)

- El detalle del contrato MUST mostrar "Sin garantía registrada" cuando `monto_garantia` sea `null` o `0.00`, en vez de un campo vacío ambiguo (FR-004, SC-003).
- El mensaje de discrepancia de montos (FR-008) MUST ser explícito, indicando la diferencia detectada, con tipografía ≥18px y alto contraste.
- Corregir una resolución ya registrada MUST mostrar un modal de confirmación de alta visibilidad antes de habilitar la edición de los montos (FR-010).
