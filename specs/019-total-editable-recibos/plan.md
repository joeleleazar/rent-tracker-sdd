# Implementation Plan: Lectura Anterior por Defecto y Total Editable y Persistido

**Branch**: `019-total-editable-recibos` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/019-total-editable-recibos/spec.md`

## Summary

Dos cambios acotados al registro masivo de lecturas (specs/015/016/017) con impacto en la
generación de recibos (specs/005): (1) cuando una locación no tiene ninguna lectura de un periodo
anterior, el consumo de su primer registro se calcula usando 0 como lectura anterior, en vez de
quedar sin consumo calculable; (2) el monto "Total" (hoy solo calculado en el navegador y nunca
guardado) pasa a ser un campo editable durante el registro inicial y se persiste como columna
propia de `lecturas_medidor`, para que la generación de recibos use ese valor ya fijado en vez de
recalcular consumo × tarifa vigente al momento de generar el recibo (que hoy puede ya no coincidir
con la tarifa que regía cuando se registró la lectura).

El enfoque técnico es: una columna `total` nueva en `lecturas_medidor` (con backfill de las filas
históricas, FR-008); un input editable en la celda "Total" del registro masivo solo para filas
todavía no guardadas (la fila ya completada sigue mostrando su total persistido, de solo lectura,
sin la edición en línea que si tiene "Lectura Actual"); el propio navegador sigue sugiriendo el
valor por defecto (consumo × tarifa) igual que hoy, y el usuario puede sobrescribirlo antes de
enviar el lote; el servidor guarda lo que llegue en ese campo, o recalcula el mismo valor sugerido
si llega vacío (degradación sin JavaScript); y `ServicioGeneracionReciboPeriodo::
calcularMontoLuzSugerido()` pasa a leer ese total persistido en vez de recalcular consumo × tarifa
vigente. El resto del diseño ya establecido (borrador, autoguardado, exportaciones, columna
Consumo) se extiende de forma consistente, no se reemplaza.

## Technical Context

**Language/Version**: PHP 8.2+ (proyecto corre localmente con PHP 8.4.12 vía Herd; el PHP 8.0.30
de PATH por defecto no cumple el mínimo de `composer.lock` — usar
`C:\Users\joel5\.config\herd\bin\php.bat` para `artisan`/`pest` en esta máquina).

**Primary Dependencies**: Laravel 11.x, htmx (`hx-boost`, sin JS de temporizador custom —
Principio VI), Bootstrap 5.3, Pest 4 — sin dependencias nuevas; misma pila que specs/015-018.

**Storage**: PostgreSQL — se agrega la columna `total` (`decimal(12,2)`, `NOT NULL` tras backfill)
a `lecturas_medidor`, y una columna `total` (`decimal(12,2)`, nullable) a
`borradores_lectura_medidor` para que el autoguardado no pierda un total editado a mano antes de
guardar (ver research.md Decisión 4). Ninguna migración de specs/018 (esquema clave-valor de
`configuracion_general`, timestamptz, índices) necesita tocarse ni se ve afectada por esta feature.

**Testing**: Pest (Feature) — se amplían `tests/Feature/RegistroMasivoLecturasControllerTest.php` y
se agregan pruebas en `tests/Unit/ServicioGeneracionReciboPeriodoTest.php` (o el archivo
equivalente ya existente) para `calcularMontoLuzSugerido()`. El recálculo en vivo del total
sugerido en el navegador no es verificable con Pest (no hay Dusk/Playwright) y se valida
manualmente vía `quickstart.md`, igual que specs/016/017.

**Target Platform**: Mismas pantallas ya existentes — `/lecturas/registro-masivo` y el formulario
de generación/edición de recibo (`ReciboController`, specs/005).

**Project Type**: Aplicación web Laravel (Blade + htmx), monolito.

**Performance Goals**: Sin impacto — un campo más por fila, sin consultas nuevas en el camino
caliente de `index()`/`store()` (el backfill de FR-008 es una migración de una sola vez).

**Constraints**: El total guardado NO DEBE recalcularse automáticamente si la tarifa cambia después
(FR-005) — una vez persistido, es un valor congelado hasta que se vuelva a registrar la lectura.

**Scale/Scope**: Mismo alcance que specs/015-017 (todas las locaciones alquilables del registro
masivo) más el punto único de consumo en specs/005 (`calcularMontoLuzSugerido()`).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack)**: Sin cambios de stack; la columna nueva usa el ORM y `DECIMAL` de
  PostgreSQL, no SQL directo fuera de la migración de backfill (que ya es el patrón establecido en
  specs/006/018 para migraciones con datos). PASA.
- **Principio II (Español)**: Nombres nuevos en español (`total`, coherente con el resto del
  dominio — `monto_luz`, `monto_agua`, etc. en `Recibo`; `total` ya es el nombre visible en la UI
  desde specs/015). PASA.
- **Principio III (Diseño Moderno)**: El input de total reutiliza el mismo patrón de campo editable
  ya usado para "Lectura Actual" (`x-text-input`, `form-control-sm`), sin un componente nuevo.
  PASA.
- **Principio IV (Pruebas Exhaustivas)**: Se amplían pruebas de controlador (persistencia y
  validación del total, comportamiento del backfill) y de servicio
  (`calcularMontoLuzSugerido()` leyendo el total persistido). PASA.
- **Principio V (Integridad de Datos)**: `total` es `DECIMAL(12,2)` (no flotante), `NOT NULL` tras
  el backfill de FR-008 — ninguna lectura queda con un total ambiguo. Validación de servidor
  (numérico, ≥ 0) igual que el resto de los campos monetarios/de lectura ya validados a mano en
  `store()` (specs/015, por el mismo motivo: una `FormRequest` no puede abortar la fila inválida
  sin descartar las demás del lote). PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: Se modifican vistas Blade del registro
  masivo (`fila-registro-masivo.blade.php`, `campo-lectura-registro-masivo.blade.php`) — DEBE
  pasar por revisión `impeccable` antes de cerrarse, igual que specs/016/017. Ningún cambio de
  interactividad de escritura nuevo: el input de total se envía con el mismo `<form>`/htmx ya
  existente, sin introducir JS de temporizador ni Alpine.js. PASA condicionado a esa revisión en
  implementación.

Sin violaciones. No aplica Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/019-total-editable-recibos/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

Ningún directorio nuevo: esta feature agrega una columna a dos tablas ya existentes y extiende
archivos ya creados por specs/005/015-018, sin controladores, servicios ni rutas nuevos.

```text
database/migrations/
├── YYYY_MM_DD_HHMMSS_agregar_total_a_lecturas_medidor.php        # columna + backfill (FR-004/FR-008)
└── YYYY_MM_DD_HHMMSS_agregar_total_a_borradores_lectura_medidor.php  # columna nullable (ver research.md Decisión 4)

app/
├── Http/Controllers/RegistroMasivoLecturasController.php   # store(): anterior=0 por defecto (FR-001), persistir/derivar total (FR-003/FR-004); guardarBorrador(): incluir total
├── Models/LecturaMedidor.php                                 # fillable/casts: + total
├── Models/BorradorLecturaMedidor.php                         # fillable/casts: + total
└── Services/ServicioGeneracionReciboPeriodo.php              # calcularMontoLuzSugerido(): leer total persistido (FR-006)

resources/
├── views/lecturas/registro-masivo/partials/
│   ├── fila-registro-masivo.blade.php                        # celda Total: input editable (pendiente) vs. valor persistido (completada)
│   └── campo-lectura-registro-masivo.blade.php                 # sin cambios de estructura, solo referencia de contexto
└── js/registro-masivo-lecturas.js                             # no sobrescribir el input de total si el usuario ya lo editó (research.md Decisión 3)

tests/
├── Feature/RegistroMasivoLecturasControllerTest.php          # pruebas a ampliar
└── Unit/ (o Feature/) ServicioGeneracionReciboPeriodoTest.php # calcularMontoLuzSugerido() con total persistido
```

**Structure Decision**: Se reutiliza íntegramente la estructura de specs/005 y specs/015-018 — el
cambio de mayor alcance es la columna nueva en `lecturas_medidor` (con su backfill) y el ajuste de
una única función de servicio (`calcularMontoLuzSugerido()`) que ya era el único punto de lectura
de "monto de luz sugerido" en todo el sistema.
