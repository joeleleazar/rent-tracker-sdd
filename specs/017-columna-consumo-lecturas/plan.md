# Implementation Plan: Columna de Consumo y Alineación del Ícono de Completado en Registro Masivo

**Branch**: `017-columna-consumo-lecturas` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/017-columna-consumo-lecturas/spec.md`

## Summary

El registro masivo de lecturas (specs/015, corregido en specs/016) ya calcula y persiste el
consumo de cada locación (`consumo_calculado`) y ya lo expone como columna "Consumo (kWh)" en sus
exportaciones a Excel/PDF, pero la pantalla interactiva no lo muestra — solo muestra el "Total" en
soles. Esta feature agrega esa columna faltante a la pantalla en vivo (FR-001 a FR-005) y
reposiciona el ícono verde de "lectura completada" para que quede a la izquierda del valor en vez
de a la derecha (FR-006).

El enfoque técnico es puramente de presentación: no hace falta tocar el controlador, ningún
servicio ni ninguna ruta. El valor de consumo por fila ya está disponible en el cliente vía el
atributo `data-consumo`/`data-lectura-anterior` de `campo-lectura-registro-masivo.blade.php`, y
`resources/js/registro-masivo-lecturas.js` ya lo calcula internamente (función
`calcularConsumoDeCampo`) para alimentar la columna "Total" — solo falta escribir ese mismo valor,
ya calculado, en una celda visible nueva. Se sigue exactamente el mismo patrón ya establecido por
la columna "Total" (specs/015, Decisión 7: cálculo en el navegador, celda que arranca en "—" y se
completa al cargar/recalcular), para no introducir un segundo patrón de renderizado dentro de la
misma tabla.

## Technical Context

**Language/Version**: PHP 8.2+ (sin cambios de backend en esta feature — ver Summary). Igual nota
de entorno que specs/016: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`)
para `artisan`/`pest` en esta máquina, no el PHP 8.0.30 de PATH.

**Primary Dependencies**: Ninguna nueva. Blade + SCSS (Bootstrap 5.3) + JS vanilla ya existente en
`resources/js/registro-masivo-lecturas.js` — sin htmx nuevo (no hay escritura al servidor
involucrada, es una columna derivada en el cliente, igual que "Total").

**Storage**: PostgreSQL — sin cambios de esquema. Se reutiliza `lecturas_medidor.consumo_calculado`
ya existente (specs/006).

**Testing**: Pest (Feature) — se amplían las pruebas ya existentes de
`tests/Feature/RegistroMasivoLecturasControllerTest.php` para el HTML server-rendered (encabezado
nuevo, celda `id="consumo-fila-{id}"` presente, orden ícono/valor); el recálculo en vivo en el
navegador (FR-003) no es verificable con Pest (no hay Dusk/Playwright, misma limitación que
specs/016) y se valida manualmente vía `quickstart.md`.

**Target Platform**: Misma pantalla `/lecturas/registro-masivo` ya existente.

**Project Type**: Aplicación web Laravel (Blade + htmx), monolito.

**Performance Goals**: Sin impacto — no se agrega ninguna consulta nueva; el dato ya viaja al
cliente hoy en `data-consumo`/`data-lectura-anterior`, sin cambios en `datosDelPeriodo()`.

**Constraints**: El valor mostrado en pantalla DEBE coincidir exactamente con el ya expuesto en las
exportaciones a Excel/PDF de la misma pantalla (FR-002, SC-001) — ambos ya derivan del mismo
`consumo_calculado`, así que basta con no introducir una segunda fórmula de cálculo del lado del
cliente.

**Scale/Scope**: Mismo alcance que specs/015/016 — todas las locaciones alquilables de la pantalla.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack)**: Sin cambios de stack, sin SQL nuevo. PASA.
- **Principio II (Español)**: Sin nombres nuevos fuera de español (`consumo-fila-{id}`,
  `fila-registro-masivo__consumo`, análogos a los ya existentes `total-fila-{id}`,
  `fila-registro-masivo__total`). PASA.
- **Principio III (Diseño Moderno)**: La nueva columna sigue la misma jerarquía tipográfica y
  alineación (`cifra`, alineado a la derecha) que "Total"; el reacomodo del ícono no introduce un
  patrón de interacción nuevo. PASA.
- **Principio IV (Pruebas Exhaustivas)**: Se amplían las pruebas de controlador/vista para cubrir
  el HTML server-rendered de la columna nueva y el nuevo orden del ícono. PASA.
- **Principio V (Integridad de Datos)**: No se persiste nada nuevo; se reutiliza
  `consumo_calculado` (`decimal:2`) ya validado por `ServicioCalculoConsumoMedidor`. PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: Esta feature modifica vistas Blade
  (`index.blade.php`, `fila-registro-masivo.blade.php`, `campo-lectura-registro-masivo.blade.php`)
  y `resources/css/bootstrap.scss` — DEBE pasar por revisión `impeccable` antes de cerrarse
  (`/impeccable polish` o `audit`), igual que cualquier vista modificada. La excepción de
  interactividad asíncrona (htmx, no JS custom) no aplica aquí porque no hay escritura al
  servidor — el cálculo en el navegador ya es el patrón vigente para "Total" (Decisión 7 de
  specs/015/research.md), no una excepción nueva. PASA condicionado a esa revisión en
  implementación.

Sin violaciones. No aplica Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/017-columna-consumo-lecturas/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

Ningún archivo ni directorio nuevo: esta feature agrega una columna y reordena un ícono dentro de
archivos ya existentes de specs/015/016, sin backend nuevo.

```text
resources/
├── css/bootstrap.scss                                          # $registro-masivo-columnas: 4 → 5 tracks
├── js/registro-masivo-lecturas.js                               # recalcularTotales() también escribe #consumo-fila-{id}
└── views/lecturas/registro-masivo/
    ├── index.blade.php                                           # encabezado: nueva celda "Consumo"
    └── partials/
        ├── fila-registro-masivo.blade.php                        # nueva celda #consumo-fila-{id}; fila total-general +1 celda
        └── campo-lectura-registro-masivo.blade.php                 # ícono check reordenado antes del valor

tests/Feature/RegistroMasivoLecturasControllerTest.php           # pruebas a ampliar (research.md)
```

**Structure Decision**: Se reutiliza íntegramente la estructura de specs/015/016 — no hay
controlador, servicio, ruta ni migración nuevos; todo el cambio vive en la capa de presentación ya
existente de la pantalla de registro masivo.
