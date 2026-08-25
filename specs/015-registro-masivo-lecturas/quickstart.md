# Quickstart: Registro Masivo de Lecturas de Luz

**Feature**: `015-registro-masivo-lecturas` | **Date**: 2026-08-24

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-registro-masivo-lecturas.md`
para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migración de `borradores_lectura_medidor` ejecutada.
- `composer require barryvdh/laravel-dompdf maatwebsite/excel` instalado (FR-016).
- Usuario autenticado.
- Datos de ejemplo: al menos 3 locaciones alquilables ("Local A", "Local B", "Local C"), donde
  "Local A" ya tiene una lectura registrada del periodo anterior (para ver la referencia) y del
  periodo actual (para ver el estado "completada"); "Local B" y "Local C" sin lectura del periodo
  actual todavía.
- Un valor de `tarifa_luz_por_unidad` configurado en Configuración General (mayor a 0), para que
  el totalizado tenga un valor por defecto visible al abrir la pantalla.

## Escenario 1 — Ver todas las locaciones agrupadas, con estado por fila (US1, US2)

1. Ir al nuevo ítem de navegación "Registrar Lecturas" (o similar).
2. **Resultado esperado**: se ve una tabla jerárquica (mismo agrupamiento que `/locaciones`) con
   "Local A" mostrando su lectura ya registrada (de solo lectura, con enlace a editar) y "Local
   B"/"Local C" con un campo de lectura vacío, cada uno mostrando la lectura del periodo anterior
   como referencia (o "sin lectura previa" si no existe).

## Escenario 2 — Guardar varias lecturas en una sola acción (US1)

1. Completar el campo de lectura actual de "Local B" y "Local C", dejando otras filas vacías si
   las hay.
2. Guardar.
3. **Resultado esperado**: ambas lecturas quedan registradas; las filas vacías no producen ningún
   error; "Local B" y "Local C" ahora aparecen como completadas.

## Escenario 3 — Un error en una fila no descarta las demás (US1, FR-009)

1. Completar dos locaciones con valores válidos y una tercera con un valor que produzca consumo
   negativo, sin marcar la confirmación.
2. Guardar.
3. **Resultado esperado**: las dos filas válidas quedan registradas; la fila con consumo negativo
   se muestra de nuevo con su checkbox de confirmación visible, sin perder lo ya guardado.

## Escenario 4 — Autoguardado y restauración del borrador (US3)

1. Completar algunas filas sin guardar y esperar a que transcurran 2 minutos (o disparar
   manualmente el ciclo de autoguardado en un entorno de prueba).
2. Cerrar la sesión o el navegador sin guardar el lote.
3. Volver a iniciar sesión y abrir la pantalla de registro masivo para el mismo periodo.
4. **Resultado esperado**: los valores ya escritos antes de cerrar la sesión aparecen
   precargados automáticamente en sus campos correspondientes, sin ninguna acción adicional.

## Escenario 5 — El borrador se descarta al guardar exitosamente (US3, FR-012)

1. Con un borrador restaurado (Escenario 4), completar el guardado final del lote con éxito.
2. Volver a abrir la pantalla de registro masivo para el mismo periodo.
3. **Resultado esperado**: no hay ningún borrador que restaurar — las filas ya guardadas
   aparecen como completadas y el resto vacías, sin rastro del borrador anterior.

## Escenario 6 — Totalizado por consumo con tarifa editable (FR-013, FR-014, FR-015)

1. Abrir la pantalla de registro masivo; el campo de tarifa por kWh aparece precargado con el
   valor vigente de Configuración General.
2. Completar la lectura actual de "Local B" (con lectura anterior conocida, para que tenga
   consumo calculable).
3. **Resultado esperado**: el total de esa fila (consumo × tarifa) y el total general se
   actualizan en el momento, sin recargar la página.
4. Cambiar el valor de la tarifa a uno distinto y quitar el foco del campo (blur/change).
5. **Resultado esperado**: los totales de todas las filas y el total general se recalculan de
   inmediato con la nueva tarifa; al volver a entrar a Configuración General, el nuevo valor de
   `tarifa_luz_por_unidad` ya quedó guardado ahí.

## Escenario 7 — Exportar la pantalla a Excel y a PDF (FR-016)

1. Con la pantalla de registro masivo abierta para un periodo con filas completadas y pendientes,
   usar el botón "Exportar a Excel".
2. **Resultado esperado**: se descarga un `.xlsx` con todas las locaciones alquilables del
   periodo (completadas y pendientes), lectura anterior, lectura actual, consumo, total por fila
   y total general, igual que lo visible en pantalla.
3. Repetir con el botón "Exportar a PDF".
4. **Resultado esperado**: se descarga un `.pdf` con el mismo contenido, en un formato legible
   para impresión.

## Escenario 8 — Editar en línea una lectura ya registrada (FR-005, FR-017)

1. En una fila ya completada ("Local A"), hacer clic en el ícono no invasivo (no en un badge de
   texto).
2. **Resultado esperado**: la fila cambia a modo edición, con un input prellenado con el valor ya
   registrado, sin navegar a otra pantalla ni recargar.
3. Cambiar el valor a uno menor a la lectura anterior de esa locación, sin confirmar, y guardar.
4. **Resultado esperado**: el sistema exige la misma confirmación de consumo negativo que el
   registro individual, dentro de la misma fila.
5. Confirmar y guardar.
6. **Resultado esperado**: la fila vuelve a modo lectura con el valor actualizado y el ícono no
   invasivo, sin haber salido de la pantalla de registro masivo en ningún momento.

## Validación automatizada (referencia)

```bash
php artisan test --filter=RegistroMasivoLecturas
```

**Cobertura esperada** (Principio IV): índice con agrupamiento jerárquico y estado por fila
(completada vs. pendiente), guardado con filas mixtas válidas/inválidas (persistencia parcial),
confirmación de consumo negativo por fila, autoguardado (upsert por usuario+periodo+locación) y
su restauración al reabrir la pantalla, descarte del borrador tras el guardado final exitoso,
actualización de la tarifa desde la pantalla (persistida en `configuracion_general`), contenido y
formato de las exportaciones a Excel y PDF, y la edición en línea (éxito y confirmación de
consumo negativo) sin redirección.

## Revisión de diseño (Principio VI)

Al crear `resources/views/lecturas/registro-masivo/index.blade.php` y su parcial de fila,
ejecutar el skill `impeccable` (`/impeccable polish` o `audit`) antes de dar la tarea por
completa — primer uso de `hx-trigger="every ..."` en el proyecto, prestar atención a que el
indicador de autoguardado no sea intrusivo ni compita visualmente con el guardado manual.
