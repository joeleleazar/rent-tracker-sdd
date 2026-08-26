# Research: Simplificar Navegación de Periodo

**Feature**: `027-simplificar-navegacion-periodo` | **Date**: 2026-08-26

## R1 — Retirar el botón "Ir" sin romper el aislamiento del formulario de periodo (FR-001/FR-004)

**Decision**: Eliminar únicamente el elemento `<x-secondary-button type="submit">Ir</x-secondary-button>` (línea 74 de `index.blade.php`) y actualizar el comentario de las líneas 21-31 que citaba a "Ir" como la razón de que el `<form>` de periodo sea un elemento separado del resto de controles (tarifa, exportar). El `<form method="GET">` que envuelve las flechas y el campo de fecha se mantiene como elemento propio — la razón de fondo (que el autoenvío `hx-trigger="change"` del campo de fecha no incluya accidentalmente los campos de tarifa/exportar de los otros `<div>` hermanos) sigue siendo válida sin el botón.

**Rationale**: Las flechas (`<a href>`) no dependen del `<form>` para funcionar — son enlaces independientes con su propio `href`, así que remover "Ir" no las afecta. El campo de fecha ya se autoenvía por sí solo vía `hx-trigger="change"`; el `<form>` sigue sirviendo como el objetivo (`action`) de ese autoenvío GET, sin necesitar ningún control de envío visible.

**Alternatives considered**:
- *Quitar también el `<form>` envolvente, dejando el campo de fecha con `hx-get` directo sin formulario*: descartado — el `<form>` no es solo el origen del envío del botón "Ir", también provee el `action`/método GET base que htmx usa como fallback de configuración; quitarlo es un cambio de mayor superficie sin ningún beneficio (el problema reportado era específicamente el botón visible, no la existencia del `<form>`).

## R2 — Prueba automatizada del botón "Ir" (FR-005)

**Decision**: Eliminar el test `'el boton de confirmar periodo declara type submit para el envio sin JavaScript'` (`RegistroMasivoLecturasControllerTest.php:482-492`) y no reemplazarlo por uno equivalente sobre un botón que ya no existe. Se agrega, en su lugar, un test que confirma explícitamente que el botón "Ir" ya NO aparece en el marcado, para dejar la decisión (FR-001/SC-003) verificada por la suite en vez de solo por inspección manual.

**Rationale**: El test existente afirmaba precisamente el comportamiento que este feature revierte (`type="submit"` del botón "Ir" como fallback deliberado) — mantenerlo tal cual haría fallar la suite; reescribirlo como una aserción negativa ("Ir" no existe) documenta la decisión de forma ejecutable, más fuerte que solo borrar el test sin dejar rastro.

**Alternatives considered**: Borrar el test sin agregar ningún reemplazo — descartado, porque no dejaría ninguna protección automatizada contra una reintroducción accidental del botón en el futuro.
