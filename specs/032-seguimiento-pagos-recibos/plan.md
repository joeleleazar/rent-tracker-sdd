# Implementation Plan: Registro y Seguimiento de Pagos de Recibos

**Branch**: `032-seguimiento-pagos-recibos` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/032-seguimiento-pagos-recibos/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Agregar una entidad `Pago` (uno o varios pagos, parciales o totales, contra un recibo ya emitido), calcular
a partir de ellos el estado Pendiente/Pagado del recibo (retirando el toggle manual entre esos dos estados,
conservando la transición manual hacia/desde Anulado ya existente), y agregar una nueva pantalla de
"Seguimiento de Pagos" que reutiliza tal cual el árbol jerárquico de locaciones ya usado en el registro
masivo de recibos (`ServicioConstruccionArbolLocaciones`), mostrando el avance de pago por locación para el
período elegido.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel (sin cambios), Blade, PostgreSQL — mismo stack ya usado en todo el
proyecto.

**Primary Dependencies**: Ninguna nueva. Reutiliza `ServicioConstruccionArbolLocaciones` (specs/013) tal
cual para el árbol de la nueva pantalla, y el mismo patrón grid/`fila-arbol` ya usado por
`recibos/registro-masivo` y `lecturas/registro-masivo`.

**Storage**: PostgreSQL — tabla nueva `pagos` (recibo_id, monto, fecha_pago, registrado_por_id,
timestamps); ningún cambio de esquema en `recibos` más allá de seguir usando su columna `estado` ya
existente, ahora recalculada en vez de asignada a mano para Pendiente/Pagado.

**Testing**: Pest — tests nuevos para `ServicioGestionPagosRecibo` (Unit), el controlador de pagos
(Feature), la nueva pantalla de seguimiento (Feature); se reescriben los tests existentes de
`ServicioCambioEstadoRecibo` y las aserciones de `ReciboControllerTest`/`recibos/show.blade.php` que hoy
dependen del toggle manual Pendiente/Pagado retirado por FR-006.

**Target Platform**: Aplicación web Laravel servida por Herd (sin cambios).

**Project Type**: Aplicación web monolítica existente — nueva entidad + nueva pantalla + refactor de un
servicio ya existente, sin cambios de estructura.

**Performance Goals**: N/A — mismo patrón anti-N+1 ya usado por `RegistroMasivoRecibosController` (una
consulta agrupada en memoria por locación, no una consulta por fila del árbol).

**Constraints**: El estado Pendiente/Pagado deja de ser editable directamente (FR-006, ya resuelto en
Clarifications) — cualquier código o test que hoy dependa de `PATCH recibos/{recibo}/estado` con
`nuevo_estado=pendiente|pagado` deja de ser válido y se reemplaza por el registro de pagos.

**Scale/Scope**: 1 tabla nueva (`pagos`), 1 modelo nuevo (`Pago`), 1 servicio nuevo
(`ServicioGestionPagosRecibo`), 1 servicio refactorizado (`ServicioCambioEstadoRecibo`), 1 controlador
nuevo (pagos de un recibo) + 1 controlador nuevo (pantalla de seguimiento) + 2 rutas de árbol nuevas, 2
vistas nuevas (pantalla de seguimiento + sus parciales) + 1 vista existente extendida
(`recibos/show.blade.php`).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)**: Cumple — `Pago` es un modelo Eloquent
  estándar, con migración PostgreSQL (`decimal` para el monto, claves foráneas con la integridad
  referencial correspondiente); ninguna consulta SQL directa fuera del ORM.
- **II. Nomenclatura en Español**: Cumple — `Pago`, `ServicioGestionPagosRecibo`, `montoPagado()`,
  `saldoPendiente()`, `registrado_por_id`, nombres de ruta y vista en español, siguiendo la convención ya
  usada en el resto del proyecto.
- **III. Diseño Moderno e Intuitivo**: Aplica — eliminar un pago (acción destructiva) exige confirmación
  explícita con lenguaje claro (Principio III), igual que ya exige el proyecto para anular un recibo; el
  estado de avance de pago se comunica con badge + texto, nunca solo color.
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — cobertura Unit de `ServicioGestionPagosRecibo` y del
  `ServicioCambioEstadoRecibo` refactorizado, cobertura Feature del controlador de pagos y de la nueva
  pantalla de seguimiento.
- **V. Integridad de Datos y Seguridad Transaccional**: Aplica directamente — registrar/editar/eliminar un
  pago y recalcular el estado del recibo ocurre dentro de una única `DB::transaction` (mismo patrón que
  `ServicioCambioEstadoRecibo` ya usa hoy); `monto` usa `decimal(12,2)`, nunca coma flotante.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple sin excepción — la nueva pantalla reutiliza
  el mismo grid `fila-arbol`/`tabla-registro-masivo-*` y los mismos componentes (`badge`, `card`, `Modal`
  nativo para confirmaciones) ya establecidos por `recibos/registro-masivo`.

**Resultado del gate**: PASA — sin violaciones ni excepciones nuevas.

## Project Structure

### Documentation (this feature)

```text
specs/032-seguimiento-pagos-recibos/
├── plan.md                              # This file (/speckit-plan command output)
├── research.md                          # Phase 0 output — decisiones de modelo, recálculo de estado y reutilización del árbol
├── data-model.md                        # Phase 1 output — entidad Pago y los métodos derivados de Recibo
├── contracts/
│   ├── gestion-pagos.md                 # Phase 1 output — rutas y validación de registrar/editar/eliminar un pago
│   └── vista-seguimiento-pagos.md       # Phase 1 output — ruta y forma de los datos de la nueva pantalla
└── quickstart.md                        # Phase 1 output — checklist de validación manual contra spec.md
```

### Source Code (repository root)

Aplicación Laravel monolítica ya existente — sin cambios de estructura. Archivos nuevos o tocados:

```text
database/migrations/
└── ..._create_pagos_table.php                        # nuevo

app/Models/
├── Pago.php                                            # nuevo
└── Recibo.php                                          # + montoPagado(), saldoPendiente(), estaPagadoPorCompleto()

app/Services/
├── ServicioGestionPagosRecibo.php                      # nuevo — registrar/actualizar/eliminar pago + recálculo de estado
└── ServicioCambioEstadoRecibo.php                      # refactor — anular()/reactivar() en vez de cambiar() genérico

app/Http/Controllers/
├── PagoReciboController.php                            # nuevo — store/update/destroy de pagos de un recibo
├── ReciboController.php                                # actualizarEstado() pasa a anular()/reactivar()
└── SeguimientoPagosController.php                      # nuevo — pantalla de árbol de avance de pago

app/Http/Requests/
├── SolicitudGuardarPago.php                            # nuevo
└── SolicitudActualizarEstadoRecibo.php                 # se simplifica: solo anulado/reactivar

resources/views/
├── pagos/seguimiento/
│   ├── index.blade.php                                 # nuevo
│   └── partials/
│       ├── fila-seguimiento-pagos.blade.php             # nuevo — recursiva, análoga a fila-registro-masivo-recibos
│       └── estado-pago-locacion.blade.php                # nuevo — análoga a estado-recibo-locacion
└── locaciones/recibos/show.blade.php                   # + lista de pagos, formulario de registro, se retira el toggle manual Pendiente/Pagado

routes/web.php                                          # + rutas de pagos y de la pantalla de seguimiento

tests/
├── Unit/ServicioGestionPagosReciboTest.php              # nuevo
├── Unit/ServicioCambioEstadoReciboTest.php               # reescrito (anular()/reactivar())
├── Feature/PagoReciboControllerTest.php                  # nuevo
├── Feature/SeguimientoPagosControllerTest.php            # nuevo
└── Feature/ReciboControllerTest.php                      # aserciones de estado.update ajustadas a FR-006
```

**Structure Decision**: Se mantiene la estructura Laravel estándar ya usada por todo el proyecto. La nueva
pantalla vive en `pagos/seguimiento/` (no dentro de `recibos/registro-masivo/`) porque, aunque reutiliza el
mismo árbol de locaciones, es una pantalla de consulta de pagos, no de emisión de recibos — mantenerla en
su propio namespace evita mezclar las dos responsabilidades en un mismo directorio de vistas, igual que
`configuracion/` vive separado de `recibos/`.

## Complexity Tracking

Sin violaciones a la constitución — tabla no aplica (ver Constitution Check arriba).
