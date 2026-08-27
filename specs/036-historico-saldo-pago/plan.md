# Implementation Plan: Saldo Histórico en el Comprobante de Pago

**Branch**: `036-historico-saldo-pago` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/036-historico-saldo-pago/spec.md`

## Summary

Corregir el cálculo de "Pagado hasta ahora" y "Saldo pendiente" en el comprobante de un pago individual
(specs/035): en vez de leer el acumulado/saldo **actual** del recibo (`Recibo::montoPagado()`/
`saldoPendiente()`, que suman TODOS los pagos existentes hoy), el comprobante debe sumar únicamente los
pagos del recibo registrados hasta ese pago inclusive, en orden de registro (`id` ascendente — resuelto en
la Clarification del spec). Se implementa como 2 métodos nuevos en `Pago`, calculados sobre la colección de
pagos ya cargada por el controlador (sin consultas nuevas), sin ninguna columna ni migración — la
recalculación automática ante ediciones/eliminaciones de pagos anteriores (FR-003) es una consecuencia
directa de calcular siempre "al vuelo" sobre los pagos que existen en ese momento, no de mantener un valor
persistido que haya que sincronizar.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel 11.x

**Primary Dependencies**: Eloquent (colecciones ya cargadas, sin consultas nuevas), Pest

**Storage**: PostgreSQL — sin cambios de esquema (sin migración; no se persiste ningún valor histórico, se
recalcula siempre desde los pagos que existen)

**Testing**: Pest (Feature tests), binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`)

**Target Platform**: Aplicación web Laravel, dominio de desarrollo `rent-tracker-sdd.test`

**Project Type**: Web (Laravel monolito con Blade)

**Performance Goals**: N/A — el filtro `id <= $this->id` se aplica en memoria sobre una colección que el
controlador ya carga completa (`recibo.pagos`); no agrega consultas.

**Constraints**: El monto propio de un pago (`$pago->monto`) y el resto del comprobante (specs/035) no
cambian — solo el acumulado y el saldo pendiente que lo acompañan (FR-004).

**Scale/Scope**: 2 métodos nuevos en `app/Models/Pago.php`, 2 líneas cambiadas en
`resources/views/pagos/comprobante.blade.php`. Sin controladores, rutas, modelos, migraciones ni vistas
nuevas.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — Eloquent puro, sin SQL directo.
- **II. Nomenclatura en Español**: Cumple — `montoAcumuladoHastaEstePago()`, `saldoPendienteHastaEstePago()`.
- **III. Diseño Moderno e Intuitivo**: N/A — no hay cambio de interfaz visual, solo de la cifra que ya se
  muestra (mismo layout de `comprobante.blade.php` ya auditado en specs/035).
- **IV. Pruebas Automatizadas Exhaustivas**: Cumple — se agregan tests de Feature para el escenario que
  motivó la corrección (comprobante del primer pago tras un segundo pago que completa el recibo) y para la
  recalculación ante edición/eliminación de un pago anterior (FR-003).
- **V. Integridad de Datos**: Cumple — no hay escritura de datos nueva; el cálculo se deriva siempre de los
  pagos que existen en el momento de la consulta, nunca de un valor persistido que pudiera desincronizarse.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: N/A — no se modifica ningún componente visual, solo
  el valor de dos interpolaciones ya existentes en la vista.

Sin violaciones. No se requiere `Complexity Tracking`. No se requiere revisión con `impeccable` (Principio
VI aplica a vistas Blade nuevas o modificadas en su marcado/diseño; aquí solo cambia la expresión PHP que
alimenta dos valores ya existentes, no el marcado).

## Project Structure

### Documentation (this feature)

```text
specs/036-historico-saldo-pago/
├── plan.md              # Este archivo
├── research.md          # Fase 0
├── quickstart.md         # Fase 1 (validación manual)
└── tasks.md              # Fase 2 (/speckit-tasks)
```

Sin `data-model.md` (no hay entidad ni atributo persistente nuevo) ni `contracts/` (no hay ruta HTTP nueva
ni contrato de interfaz nuevo — se documenta el cambio de comportamiento del contrato ya existente de
specs/035 en `research.md`).

### Source Code (repository root)

```text
app/Models/Pago.php                                    # + montoAcumuladoHastaEstePago(), saldoPendienteHastaEstePago()
resources/views/pagos/comprobante.blade.php             # $recibo->montoPagado()/saldoPendiente() → $pago->montoAcumuladoHastaEstePago()/saldoPendienteHastaEstePago()
tests/Feature/ComprobantePagoControllerTest.php          # + tests del escenario corregido y de FR-003
```

**Structure Decision**: Corrección quirúrgica de 1 modelo y 1 vista ya existentes (specs/035), sin
controladores, rutas, modelos, ni migraciones nuevas.

## Complexity Tracking

*(vacío — sin violaciones que justificar)*
