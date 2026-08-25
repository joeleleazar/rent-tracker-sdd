# Implementation Plan: Estado de Recibos y Envío por WhatsApp o Impresión

**Branch**: `007-estado-envio-recibo` | **Date**: 2026-08-20 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/007-estado-envio-recibo/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Agregar a `Recibo` (de `specs/004`, extendido en `005`) un estado de pago (`pendiente`/`pagado`/`anulado`, transiciones libres con confirmación explícita hacia/desde "anulado") y una vista de comprobante única reutilizable tanto para impresión (CSS de impresión nativa del navegador, sin dependencias de PDF en el servidor) como para generación de imagen y envío por WhatsApp (captura client-side vía `html2canvas` + Web Share API del navegador, sin que el servidor almacene números de teléfono ni gestione una integración de WhatsApp Business). Enfoque técnico: migración de alteración sobre `recibos`, un `ServicioCambioEstadoRecibo` que centraliza las reglas de limpieza de fechas al cambiar de estado, y una vista Blade `recibos/comprobante.blade.php` con JS mínimo empaquetado por Vite (sin CDN, consistente con las restricciones de shared hosting de `specs/002`).

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x (misma nota de discrepancia que `specs/001` §1), Eloquent ORM, Blade, Vite (build de assets ya usado por el scaffolding de Laravel Breeze del proyecto), `html2canvas` (dependencia npm, MIT, empaquetada en el build de producción — no cargada desde CDN), Web Share API nativa del navegador (`navigator.share`, con soporte de archivos en navegadores Chromium/Android modernos), Pest 4

**Storage**: PostgreSQL; se altera `recibos` (de `specs/004`/`005`) agregando `estado`, `fecha_pago`, `fecha_anulacion`

**Testing**: Pest, `RefreshDatabase`; feature tests para `ReciboController@actualizarEstado`/`@comprobante`; unit tests para el modelo `Recibo` (transiciones, limpieza de fechas) y `ServicioCambioEstadoRecibo`

**Target Platform**: Servidor Linux de shared hosting para el backend; navegador del Administrador (escritorio o móvil) para la captura de imagen y el uso de WhatsApp Web o la app nativa vía Web Share API

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Cambio de estado reflejado de inmediato en listado/detalle (SC-001); imagen lista para compartir en <30s desde el detalle (SC-002); vista de impresión generada en <15s (SC-003)

**Constraints**: Ninguna transición de estado tiene restricción de secuencia (FR-005), pero toda transición hacia o desde "anulado" exige confirmación explícita de alta visibilidad (FR-004); el servidor NUNCA almacena ni gestiona números de teléfono ni credenciales de WhatsApp (A-003); la marca "ANULADO" MUST aparecer en imagen e impresión de cualquier recibo anulado (FR-009)

**Scale/Scope**: Mismo volumen que `Recibo` (uno por locación/periodo, de `specs/005`)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Migración Eloquent, `enum` compilado a `varchar`+`CHECK` en PostgreSQL (mismo patrón que `estado` de `Contrato` en `specs/002`), Service desacoplado, sin SQL crudo; JS de captura de imagen empaquetado vía Vite, no un servicio externo. ⚠️ Misma nota de discrepancia de versión de Laravel que `specs/001` (preexistente) |
| II. Nomenclatura en Español | ✅ Columnas `estado`/`fecha_pago`/`fecha_anulacion`, `ServicioCambioEstadoRecibo`, `SolicitudActualizarEstadoRecibo`, vista `recibos/comprobante.blade.php` |
| III. Diseño Moderno e Intuitivo | ✅ Botones "Marcar como Pagado"/"Anular Recibo"/"Enviar por WhatsApp"/"Imprimir Recibo" claros; modal de confirmación explícita ("Sí, anular recibo" / "No, cancelar") antes de cualquier transición hacia/desde "anulado"; marca "ANULADO" en alto contraste sobre el comprobante |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre modelo `Recibo` (transiciones libres, limpieza de `fecha_pago`/`fecha_anulacion` al salir de un estado), `ServicioCambioEstadoRecibo` (confirmación exigida hacia/desde anulado), `ReciboController@actualizarEstado`/`@comprobante` (happy path, 422 sin confirmación, marca ANULADO presente en el HTML del comprobante) |
| V. Integridad de Datos y Seguridad Transaccional | ✅ `DB::transaction` en cada cambio de estado (actualización de `estado` + limpieza/asignación de fechas es atómica); ningún dato de WhatsApp o de contacto se persiste en base de datos (A-003) |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/007-estado-envio-recibo/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── rutas-estado-envio-recibo.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── Recibo.php                            # Se agregan estado/fecha_pago/fecha_anulacion, cast enum, helpers de transición
├── Http/
│   ├── Controllers/
│   │   └── ReciboController.php              # Se agregan actualizarEstado()/comprobante() (de specs/004-005)
│   └── Requests/
│       └── SolicitudActualizarEstadoRecibo.php   # Nuevo
└── Services/
    └── ServicioCambioEstadoRecibo.php         # Nuevo: valida confirmación hacia/desde anulado, limpia fechas, DB::transaction

database/
└── migrations/
    └── xxxx_add_estado_to_recibos_table.php   # Nuevo (ALTER recibos: estado enum, fecha_pago, fecha_anulacion)

resources/
├── js/
│   └── recibo-comprobante.js                  # Nuevo: captura html2canvas + navigator.share, fallback de descarga
└── views/
    └── locaciones/
        └── recibos/
            └── comprobante.blade.php           # Nuevo: vista única para impresión (CSS @media print) e imagen (captura JS), marca ANULADO condicional

package.json                                    # Se agrega dependencia "html2canvas" (build-time, sin CDN)

tests/
├── Feature/
│   └── ReciboControllerTest.php               # Se extiende (de specs/004-005): actualizarEstado, comprobante, marca ANULADO
└── Unit/
    ├── ReciboTest.php                          # Se extiende (de specs/004-005): transiciones, limpieza de fechas
    └── ServicioCambioEstadoReciboTest.php       # Nuevo
```

**Structure Decision**: Aplicación Laravel monolítica única. Esta feature extiende `Recibo` (de `specs/004`/`005`) con estado y trazabilidad de fechas, y agrega una única vista de comprobante reutilizada por impresión (nativa del navegador) e imagen (captura client-side), evitando cualquier dependencia de generación de imágenes/PDF en el servidor (no garantizada en shared hosting, consistente con `specs/002/research.md` §2).

## Complexity Tracking

*No violations identified — table intentionally left empty.*
