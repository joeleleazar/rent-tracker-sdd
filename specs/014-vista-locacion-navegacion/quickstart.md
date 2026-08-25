# Quickstart: Navegación a Contratos y Recibos desde las Vistas de Locación

**Feature**: `014-vista-locacion-navegacion` | **Date**: 2026-08-24 (revisado tras `/speckit-clarify`)

Guía de validación end-to-end. Ver `data-model.md` para el detalle de las entidades (sin
cambios de esquema) y `tasks.md` para las tareas de construcción.

**Nota de alcance**: los Escenarios 1-3 cubren User Story 1 (P1/MVP, pendiente de construir —
menú desplegable en la fila de la tabla jerárquica). Los Escenarios 4-7 cubren User Story 2 (P3,
ya implementada — botones en el detalle de la locación).

## Prerrequisitos

- Migraciones ya ejecutadas (sin migraciones nuevas en esta feature).
- Usuario autenticado.
- Datos de ejemplo: una locación alquilable "Local A" (`es_alquilable = true`) con al menos un
  contrato (vigente o finalizado) y al menos un recibo emitido; una locación no alquilable
  contenedora "Galería El Sol" (`es_alquilable = false`) con "Local A" como hija (para probar
  indentación); y una locación no alquilable "Piso 1" sin hijos.

## Escenario 1 — Menú de acciones en la fila de una locación alquilable (US1)

1. Ir a `/locaciones`.
2. En la fila de "Local A", localizar el botón trigger del menú (ícono de 3 puntos verticales, junto al botón "+").
3. Presionar el trigger.
4. **Resultado esperado**: se despliega un menú con "Editar", "Ver Contratos" y "Ver Recibos"; el botón "+" (crear hija) sigue visible fuera del menú, sin cambios.

## Escenario 2 — Navegar a recibos y contratos desde el menú de la fila (US1)

1. Con el menú de "Local A" desplegado (Escenario 1), seleccionar "Ver Recibos".
2. **Resultado esperado**: se llega al historial de recibos de "Local A" (`/locaciones/{id}/recibos`), mostrando los recibos ya emitidos.
3. Volver a `/locaciones`, abrir de nuevo el menú de "Local A" y seleccionar "Ver Contratos".
4. **Resultado esperado**: se llega al historial completo de contratos de "Local A" (vigentes y finalizados), desde donde es posible crear o editar un contrato sin salir de esa ruta.

## Escenario 3 — Fila de una locación no alquilable y filas indentadas (US1, Edge Cases)

1. En `/locaciones`, localizar la fila de "Piso 1" (no alquilable).
2. **Resultado esperado**: el menú de acciones de esa fila solo contiene "Editar" — sin "Ver Contratos" ni "Ver Recibos".
3. Desplegar el menú de "Local A" (hija indentada de "Galería El Sol") con la ventana del navegador angosta o cerca del borde inferior de la pantalla.
4. **Resultado esperado**: el menú se abre completo, sin recortarse por el contenedor con scroll propio de la tabla (`overflow-x: auto`) ni producir scroll horizontal en la página completa.

## Escenario 4 — Acceder al historial de recibos desde el detalle de la locación (US2, ya implementado)

1. Ir al detalle de "Local A" (`/locaciones/{id}`).
2. **Resultado esperado**: se ve un botón "Ver Recibos" junto al botón "Ver Contratos" ya existente.
3. Presionar "Ver Recibos".
4. **Resultado esperado**: se llega al historial de recibos de "Local A", mostrando los recibos ya emitidos.

## Escenario 5 — Estado vacío de recibos (US2, ya implementado)

1. Crear una locación alquilable nueva sin recibos emitidos todavía.
2. Ir a su detalle y presionar "Ver Recibos".
3. **Resultado esperado**: se llega al historial de recibos y se muestra un estado vacío claro (sin recibos), sin error.

## Escenario 6 — Historial y CRUD de contratos desde el detalle de la locación (US2, ya implementado)

1. Ir al detalle de "Local A" y presionar "Ver Contratos".
2. **Resultado esperado**: se llega al historial completo de contratos de "Local A" (vigentes y finalizados).
3. Desde esa pantalla, crear un contrato nuevo.
4. **Resultado esperado**: el contrato se crea sin haber tenido que salir de la ruta iniciada desde la locación.
5. Volver al historial y editar un contrato existente.
6. **Resultado esperado**: la edición se completa sin haber tenido que buscar la locación por otra vía.

## Escenario 7 — Locación no alquilable en el detalle (US2, ya implementado)

1. Ir al detalle de "Galería El Sol" (`es_alquilable = false`).
2. **Resultado esperado**: no se muestra ni el botón "Ver Contratos" ni el botón "Ver Recibos".

## Validación automatizada (referencia)

```bash
php artisan test --filter=Locacion
```

**Cobertura esperada** (Principio IV):
- `LocacionController@index` (US1, pendiente): menú de acciones con "Ver Contratos"/"Ver Recibos" presentes en una fila alquilable y ausentes en una fila no alquilable.
- `LocacionController@show` (US2, ya cubierto): botones "Ver Contratos" y "Ver Recibos" presentes cuando `es_alquilable = true`, ausentes cuando `es_alquilable = false`.

## Revisión de diseño (Principio VI)

Al modificar `resources/views/locaciones/partials/fila-arbol-locacion.blade.php` y
`resources/css/bootstrap.scss`, ejecutar el skill `impeccable` (`/impeccable polish` o `audit`,
según el alcance del cambio) antes de dar la tarea por completa, según exige el Principio VI de
la constitución — con atención particular a que el menú desplegable sea operable por teclado y
mantenga foco visible (Principio III).
