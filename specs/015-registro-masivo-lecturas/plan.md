# Implementation Plan: Registro Masivo de Lecturas de Luz

**Branch**: `015-registro-masivo-lecturas` | **Date**: 2026-08-24 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/015-registro-masivo-lecturas/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Hoy registrar una lectura de luz requiere entrar a `locaciones/{locacion}/lecturas/crear` una locación a la vez. Esta feature agrega una pantalla nueva, accesible desde la navegación principal, que lista **todas** las locaciones alquilables agrupadas por la misma jerarquía que ya usa `/locaciones` (reutilizando `ServicioConstruccionArbolLocaciones`), con un campo de lectura actual por fila y la lectura del periodo anterior visible como referencia (reutilizando `ServicioCalculoConsumoMedidor::sugerirLecturaAnterior`). Un único envío guarda todas las filas completadas; las filas con error no descartan las demás (persistencia fila por fila, no un único `DB::transaction` para todo el lote). Además, mientras el usuario completa la pantalla, sus valores se autoguardan cada 2 minutos como borrador persistido en el servidor (tabla nueva `borradores_lectura_medidor`, por usuario + periodo + locación), que se restaura solo al reabrir la pantalla y se descarta al completar el guardado final — implementado con el `hx-trigger="every 120s"` nativo de htmx (ya la única capa de interactividad de escritura del proyecto, specs/011), sin JavaScript de temporizador custom.

Una ronda de `/speckit-clarify` posterior amplió el alcance (FR-013 a FR-017): la pantalla suma un totalizado por consumo (una tarifa por kWh editable, reutilizando la ya existente `ConfiguracionGeneral.tarifa_luz_por_unidad` de specs/005, con total por fila y total general calculados en vivo en el navegador), exportación de la vista a Excel y PDF, y una forma de editar en línea (sin navegar a otra pantalla) una lectura ya registrada, reemplazando el badge de texto "Completada" por un ícono discreto con equivalente textual accesible.

## Technical Context

**Language/Version**: PHP 8.3 (constitución exige 8.2+), Laravel 13.17

**Primary Dependencies**: Laravel Blade Components, Bootstrap 5.3 (Sass) + Bootstrap Icons, htmx (`hx-boost` +, por primera vez en el proyecto, `hx-trigger="every 120s"` para el autoguardado — Principio VI de la constitución). Se agregan dos dependencias nuevas de Composer para FR-016: `barryvdh/laravel-dompdf` (PDF) y `maatwebsite/excel` (Excel), ambas librerías PHP puras sin binarios externos (ver Decisión 8 de `research.md`).

**Storage**: PostgreSQL. Reutiliza `lecturas_medidor` sin cambios de esquema; agrega una tabla nueva `borradores_lectura_medidor` (usuario_id, periodo, locacion_id, lectura_actual, timestamps) para el borrador de User Story 3. FR-013/FR-015 reutilizan `configuracion_general.tarifa_luz_por_unidad` (ya existente desde specs/005), sin columnas nuevas.

**Testing**: Pest 4 (`pestphp/pest`) sobre PHPUnit 12, patrón `tests/Feature/LecturaMedidorControllerTest.php` (a crear) siguiendo la convención de `tests/Feature/ReciboControllerTest.php`. Se agregan casos para exportación (Excel/PDF), edición en línea, y actualización de la tarifa desde la pantalla.

**Target Platform**: Aplicación web Laravel servida vía navegador (sin app móvil nativa)

**Project Type**: Aplicación web monolítica Laravel (single project)

**Performance Goals**: La consulta de la pantalla masiva DEBE evitar N+1 al calcular la lectura anterior sugerida de cada locación (reutilizar el patrón de una sola consulta agrupada de `ServicioConstruccionArbolLocaciones`, no N llamadas a `sugerirLecturaAnterior` con una query cada una si la cantidad de locaciones crece). El autoguardado cada 2 minutos es una escritura ligera (upsert de pocas filas), sin impacto de carga relevante para el volumen esperado (decenas de locaciones, un usuario administrador). Las exportaciones (FR-016) reutilizan exactamente la misma consulta N+1-segura que `index()`, sin queries adicionales por fila. Los totales por fila y el total general (FR-013/FR-014) se calculan en el navegador (JavaScript puro, sin round-trip al servidor), igual que el patrón ya usado en `resources/js/costos-fijos-contrato.js`.

**Constraints**: Debe respetar el Principio VI (Bootstrap 5, htmx como única capa de escritura asíncrona, sin Alpine.js) y el Principio V (persistencia fila por fila con `DB::transaction` por fila, no un lote atómico único, para cumplir FR-009 sin sacrificar la integridad transaccional de cada inserción individual). El autoguardado NO debe disparar el mismo tratamiento visual de "Guardando…"/deshabilitar botón que ya aplica `resources/js/htmx.js` a los envíos del usuario (ver Decisión 3 de `research.md`). El ícono no invasivo que reemplaza el badge "Completada" (FR-005) DEBE conservar un equivalente textual explícito (Principio III) vía `aria-label`/tooltip, aunque no sea visible permanentemente. La edición en línea (FR-017) y la actualización de la tarifa (FR-015) se implementan con htmx, no con JavaScript de formulario custom ni Alpine.js.

**Scale/Scope**: Una pantalla nueva (índice + guardado + autoguardado + totalizado + exportación + edición en línea), una tabla nueva de borrador, sin tocar el flujo individual de lecturas ya existente (`locaciones/{locacion}/lecturas/*`, sin cambios de comportamiento — solo se reutiliza su lógica de validación/actualización desde la nueva edición en línea).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — PHP/Laravel/PostgreSQL sin desviaciones; la tabla nueva usa migraciones, claves foráneas y `NUMERIC`/`decimal` para la lectura, consistente con `lecturas_medidor`. Las dos librerías nuevas (`barryvdh/laravel-dompdf`, `maatwebsite/excel`) son paquetes Composer instalados vía `composer require` como cualquier otra dependencia PHP del proyecto, sin bypass del ORM ni SQL directo.
- **II. Nomenclatura en Español**: Cumple — tabla `borradores_lectura_medidor`, modelo `BorradorLecturaMedidor`, controlador y rutas en español, siguiendo la convención ya usada por `LecturaMedidorController`. Las clases de exportación nuevas (ej. `ExportacionRegistroMasivoLecturas`) también en español; solo implementan interfaces del paquete (`FromCollection`, `WithHeadings`) cuyo nombre en inglés es parte del contrato de la librería, igual que `Model`/`Controller` de Laravel.
- **III. Diseño Moderno e Intuitivo**: Cumple — tabla jerárquica reutilizando el patrón visual de `/locaciones`; sin necesidad de modal de confirmación destructiva (no hay eliminación en esta feature). Feedback de guardado exitoso vía alerta persistente igual que el resto de la app; el autoguardado usa un indicador discreto, no una alerta modal, por ser una operación de fondo. **Matiz FR-005/FR-017**: el ícono no invasivo que reemplaza el badge "Completada" no lleva una etiqueta de texto visible permanentemente — se satisface el requisito de "refuerzo visual, nunca reemplazo de una etiqueta textual explícita" con un `aria-label` + tooltip Bootstrap (`title`) que expone el mismo texto a lectores de pantalla y al pasar el cursor, igual que otros íconos de acción ya existentes en la app cuando el espacio horizontal es limitado.
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — pruebas Feature nuevas para el índice, el guardado masivo (con filas mixtas válidas/inválidas), la confirmación de consumo negativo por fila, el autoguardado y su restauración, el descarte del borrador al guardar, la exportación a Excel/PDF (contenido y formato de respuesta), la edición en línea (éxito y confirmación de consumo negativo), y la actualización de la tarifa desde la pantalla. Pruebas Unit para el nuevo modelo/servicio de borrador si concentra lógica no trivial.
- **V. Integridad de Datos y Seguridad Transaccional**: Aplica con matiz — cada fila del lote se persiste en su propia `DB::transaction` (reutilizando el mismo patrón ya usado en `LecturaMedidorController@store`), no en una transacción única para todo el lote, precisamente para que un error de validación en una fila (FR-009) no revierta las demás ya válidas. Montos/lecturas siguen usando `decimal`, sin floats. La edición en línea (FR-017) reutiliza el mismo patrón transaccional que `LecturaMedidorController@update`.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — reutiliza `card`/`table-responsive` según corresponda, `btn`/`input-group` (con sufijo de tarifa), iconografía consistente (`bi-speedometer2` ya usado para lecturas, `bi-check-circle-fill` para el nuevo indicador de completado, `bi-file-earmark-excel`/`bi-file-earmark-pdf` para los botones de exportación). El autoguardado, la edición en línea y la actualización de la tarifa se implementan con atributos htmx (`hx-trigger`, `hx-get`, `hx-patch`), la única vía de interactividad de escritura autorizada por este principio — no se introduce Alpine.js. El cálculo en vivo de los totales (FR-013/FR-014) es JavaScript puro puramente presentacional (no escribe al servidor), siguiendo el precedente ya establecido por `resources/js/costos-fijos-contrato.js`, que tampoco es una excepción a este principio porque no reemplaza a htmx como capa de escritura. Toda vista Blade nueva/modificada requiere revisión con el skill `impeccable` antes de completarse.

**Resultado**: PASS — sin violaciones que requieran justificación en Complexity Tracking.

**Re-check post-diseño (Fase 1)**: `data-model.md` confirma que la única tabla nueva
(`borradores_lectura_medidor`) sigue el mismo patrón relacional que `lecturas_medidor`
(claves foráneas, `decimal` para la lectura, sin JSON de esquema libre); FR-013/FR-015 no agregan
columnas, reutilizan `configuracion_general.tarifa_luz_por_unidad`. `contracts/` documenta 8
rutas nuevas en total, todas dentro del mismo namespace de controlador en español ya establecido.
El Constitution Check se mantiene en PASS sin cambios, con el matiz de Principio III ya
documentado arriba.

## Project Structure

### Documentation (this feature)

```text
specs/015-registro-masivo-lecturas/
├── plan.md              # Este archivo (salida de /speckit-plan)
├── research.md          # Salida de Fase 0 (/speckit-plan)
├── data-model.md         # Salida de Fase 1 (/speckit-plan)
├── quickstart.md         # Salida de Fase 1 (/speckit-plan)
├── contracts/             # Salida de Fase 1 (/speckit-plan) — rutas nuevas
└── tasks.md              # Salida de Fase 2 (/speckit-tasks — NO generado por /speckit-plan)
```

### Source Code (repository root)

```text
composer.json    # +barryvdh/laravel-dompdf, +maatwebsite/excel (FR-016)

database/migrations/
└── 2026_08_24_XXXXXX_create_borradores_lectura_medidor_table.php   # Tabla nueva
                                                                     # (FR-013/015 no agregan
                                                                     # migraciones: reutilizan
                                                                     # configuracion_general)

app/Models/
├── LecturaMedidor.php                # Sin cambios
├── ConfiguracionGeneral.php          # Sin cambios de esquema; reutilizado por FR-013/FR-015
└── BorradorLecturaMedidor.php        # Nuevo — usuario_id, periodo, locacion_id, lectura_actual

app/Http/Controllers/
├── LecturaMedidorController.php               # Sin cambios de comportamiento; su patrón de
                                                 # validación/actualización se reutiliza desde
                                                 # la edición en línea (FR-017)
└── RegistroMasivoLecturasController.php       # index (US1+US2), store (US1),
                                                # guardarBorrador (US3),
                                                # exportarExcel, exportarPdf (FR-016),
                                                # editarInline, actualizarInline (FR-017),
                                                # actualizarTarifa (FR-015)

app/Http/Requests/
├── SolicitudGuardarLecturaMedidor.php          # Sin cambios, reutilizada por fila y por la
                                                 # edición en línea
└── SolicitudGuardarRegistroMasivoLecturas.php  # Sin cambios, valida el arreglo de filas del lote

app/Services/
├── ServicioConstruccionArbolLocaciones.php    # Sin cambios, reutilizado para agrupar filas
└── ServicioCalculoConsumoMedidor.php          # Sin cambios, reutilizado por fila y por
                                                # exportación/edición en línea

app/Exports/
└── ExportacionRegistroMasivoLecturas.php      # Nuevo — filas del periodo para maatwebsite/excel
                                                # (FromCollection, WithHeadings)

routes/web.php    # +8 rutas: lecturas.registroMasivo.index/store/borrador (ya existentes) +
                  # exportarExcel/exportarPdf/editarInline/actualizarInline/actualizarTarifa

resources/views/lecturas/registro-masivo/
├── index.blade.php                    # Tabla jerárquica; +input de tarifa, fila de total
                                        # general, botones de exportación
├── exportar-pdf.blade.php             # Nuevo — plantilla dedicada para dompdf (FR-016)
└── partials/
    └── fila-registro-masivo.blade.php # Fila recursiva; +ícono no invasivo + modo edición en
                                        # línea (FR-005/FR-017), +total por fila (FR-013)

resources/views/components/layouts/app-bootstrap.blade.php   # +1 ítem de navegación principal

resources/js/
└── registro-masivo-lecturas.js        # Nuevo — recálculo en vivo de totales por fila y total
                                        # general (JS puro, patrón de costos-fijos-contrato.js)

vite.config.js    # +1 entrada: resources/js/registro-masivo-lecturas.js

tests/Feature/
└── RegistroMasivoLecturasControllerTest.php   # Existente; +casos de exportación, edición en
                                                # línea y actualización de tarifa
```

**Structure Decision**: Aplicación web monolítica Laravel de un solo proyecto. Se agrega un
controlador y una tabla nuevos, ambos aislados del flujo individual de lecturas ya existente
(que no se modifica), más un ítem de navegación y una vista nueva que reutiliza servicios y
patrones visuales ya existentes. La ampliación de alcance (FR-013 a FR-017) no agrega
controladores nuevos ni tablas nuevas: extiende el mismo `RegistroMasivoLecturasController` con
más acciones, reutiliza `ConfiguracionGeneral` ya existente, y sigue el patrón ya establecido de
un archivo JS por vista para cálculos puramente presentacionales.

## Complexity Tracking

*No aplica — el Constitution Check no registró violaciones que requieran justificación. La
persistencia fila-por-fila (en vez de una única transacción para todo el lote) no es una
excepción al Principio V sino su aplicación correcta a nivel de fila, ya exigida por FR-009 del
spec.*
