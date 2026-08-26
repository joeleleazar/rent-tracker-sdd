# Implementation Plan: Catálogo Dinámico de Conceptos de Gastos Fijos, Periodo Ágil y Totales por Locación

**Branch**: `024-conceptos-gastos-fijos-dinamicos` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

## Summary

Reemplaza los 5 conceptos de gasto fijo hoy codificados como columnas fijas (`costo_agua`/`costo_luz`/
`costo_pasadizo`/`costo_seguridad` en `Contrato`; `incluye_*`/`monto_*` en `Recibo`) por un catálogo
mantenible (`ConceptoGastoFijo`) y dos tablas de detalle (valor de referencia por contrato, monto por
recibo) — con "Renta" y "Luz" conservando sus fuentes de valor especiales ya existentes (monto de renta con
prorrateo; lectura de medidor). Además, cambia el selector de periodo de las dos pantallas de registro
masivo (lecturas, recibos) a flechas + autoenvío sin recarga completa, y agrega total facturado + cantidad
de recibos por locación a la pantalla de registro masivo de recibos.

Es la feature de mayor alcance de esta sesión: reemplaza directamente la estructura de datos que
specs/004/005/019/023 construyeron para "conceptos de recibo", así que casi todo lo que esas specs tocaron
(modelos, controladores, form requests, vistas, tests, factories) se ve afectado.

## Technical Context

**Language/Version**: PHP 8.2+ (binario de Herd en esta máquina: `C:\Users\joel5\.config\herd\bin\php.bat`)

**Primary Dependencies**: Laravel 11.x (Eloquent, migraciones, Form Requests), htmx (`hx-get` en `change` y en
enlaces de flecha, reemplazando formularios con botón — Principio VI), Bootstrap 5.3

**Storage**: PostgreSQL 15+ — 1 tabla nueva (`conceptos_gasto_fijo`) + 2 tablas de detalle nuevas
(`contrato_valores_concepto`, `recibo_conceptos`); se eliminan 4 columnas de `contratos`
(`costo_agua`/`costo_luz`/`costo_pasadizo`/`costo_seguridad`) y 10 columnas de `recibos`
(`incluye_alquiler`/`incluye_agua`/`incluye_luz`/`incluye_pasadizo`/`incluye_seguridad`/`monto_agua`/
`monto_luz`/`monto_pasadizo`/`monto_seguridad`; `monto_renta` se conserva en `recibos` como el monto real
cobrado de renta, fuera de `recibo_conceptos`)

**Testing**: Pest — reescritura extensa de tests existentes (`ReciboControllerTest`,
`RegistroMasivoRecibosControllerTest`, `ServicioGeneracionReciboPeriodoTest`, tests de `ContratoController`
relacionados a costos) + tests nuevos para el CRUD de conceptos y para el periodo ágil

**Target Platform**: Web (navegador), rutas autenticadas del panel administrativo existente

**Project Type**: Aplicación web Laravel monolítica existente — esta feature es una migración de modelo de
datos + una pantalla CRUD nueva + cambios de interacción en pantallas ya existentes; no se agrega ninguna
capa arquitectónica nueva

**Performance Goals**: sin metas nuevas — el catálogo tiene un volumen trivial (decenas de conceptos como
máximo); las consultas de total/cantidad por locación deben seguir el mismo criterio anti-N+1 ya establecido
en specs/018/023

**Constraints**: la migración de datos existentes (Assumption A-003 de spec.md) NO puede perder información
— todo contrato y recibo ya existente debe terminar con exactamente los mismos valores efectivos que tenía
antes, expresados en la nueva estructura; "Renta" y "Luz" nunca deben ofrecerse en los formularios de valor
de referencia por contrato (FR-004/FR-006)

**Scale/Scope**: 3 tablas nuevas, 1 modelo nuevo (`ConceptoGastoFijo`) + 2 modelos de detalle
(`ValorConceptoContrato`, `ReciboConcepto`), 1 controlador CRUD nuevo, 4 controladores modificados
(`ContratoController`, `ReciboController`, `RegistroMasivoRecibosController`,
`RegistroMasivoLecturasController` solo para el periodo ágil), ~8 vistas modificadas + ~4 nuevas, reescritura
de `ServicioGeneracionReciboPeriodo` y `ConceptosReciboYaCubiertosException` para leer el catálogo en vez de
un array fijo

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: cumple — todo el cambio de esquema vía migraciones Eloquent, relaciones
  declaradas, sin SQL crudo. La constraint `CHECK` de PostgreSQL se evalúa para proteger "Renta" a nivel de
  aplicación (no de base de datos), ver Complexity Tracking.
- **II. Nomenclatura en Español**: cumple — `ConceptoGastoFijo`, `ValorConceptoContrato`, `ReciboConcepto`,
  `conceptos_gasto_fijo`, `contrato_valores_concepto`, `recibo_conceptos`.
- **III. Diseño Moderno e Intuitivo**: cumple — el periodo ágil (flechas + autoenvío) es exactamente el tipo
  de fluidez que este principio prioriza; el CRUD de conceptos reutiliza los mismos patrones de formulario
  ya establecidos (card, confirmación para acciones irreversibles al intentar eliminar un concepto en uso).
- **IV. Pruebas Automatizadas Exhaustivas**: cumple — feature de alto riesgo de regresión (toca lógica de
  facturación ya probada); exige tests de migración de datos (specs/004/005/019/023 no tienen precedente de
  "migrar y eliminar columnas con datos reales" en este proyecto, salvo specs/018) además de los tests
  funcionales nuevos.
- **V. Integridad de Datos y Seguridad Transaccional**: cumple — la migración de backfill (columnas viejas →
  tablas nuevas) DEBE correr dentro de una transacción por tabla; `DECIMAL`/`decimal:2` en toda columna de
  monto nueva, consistente con lo ya establecido.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: aplica en forma completa — múltiples vistas nuevas y
  modificadas, requiere revisión `impeccable` antes de cerrar la feature.

**Resultado**: PASS, con una desviación documentada en Complexity Tracking (protección de "Renta" a nivel de
aplicación, no de constraint de base de datos).

## Project Structure

### Documentation (this feature)

```text
specs/024-conceptos-gastos-fijos-dinamicos/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   ├── conceptos-gasto-fijo-crud.md
│   ├── contrato-valores-concepto.md
│   ├── recibo-conceptos-dinamico.md
│   ├── periodo-agil.md
│   └── totales-por-locacion.md
├── checklists/
│   └── requirements.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── ConceptoGastoFijo.php              # nuevo
│   ├── ValorConceptoContrato.php          # nuevo (tabla contrato_valores_concepto)
│   ├── ReciboConcepto.php                 # nuevo (tabla recibo_conceptos)
│   ├── Contrato.php                       # modificado — quita costo_*, agrega valoresConceptos()
│   └── Recibo.php                         # modificado — quita incluye_*/monto_agua|luz|pasadizo|seguridad, agrega conceptos()
├── Http/
│   ├── Controllers/
│   │   ├── ConceptoGastoFijoController.php        # nuevo — index/create/store/edit/update (sin destroy real)
│   │   ├── ContratoController.php                 # modificado — actualizarCostos() pasa a leer conceptos dinámicos
│   │   ├── ReciboController.php                   # modificado — create()/store()/update() con conceptos dinámicos
│   │   └── RegistroMasivoRecibosController.php    # modificado — modal()/store()/index() con conceptos dinámicos + totales
│   └── Requests/
│       ├── SolicitudGuardarConceptoGastoFijo.php  # nuevo
│       ├── SolicitudGuardarRecibo.php             # modificado — conceptos dinámicos
│       ├── SolicitudGuardarReciboRegistroMasivo.php  # modificado — conceptos dinámicos
│       └── SolicitudGuardarCostosContrato.php     # modificado — conceptos dinámicos (posible renombre)
└── Services/
    └── ServicioGeneracionReciboPeriodo.php    # modificado — lee el catálogo en vez de un array fijo

database/
├── migrations/  # crear conceptos_gasto_fijo (+ seed 5 filas) → crear contrato_valores_concepto (+ backfill
│                # desde costo_*) → crear recibo_conceptos (+ backfill desde incluye_*/monto_*) → eliminar
│                # columnas viejas de contratos y recibos
└── factories/
    ├── ConceptoGastoFijoFactory.php   # nuevo
    ├── ContratoFactory.php            # modificado
    └── ReciboFactory.php              # modificado

resources/
├── js/
│   └── periodo-agil.js                # nuevo — pequeño helper htmx compartido por ambas pantallas de registro masivo
└── views/
    ├── conceptos-gasto-fijo/
    │   ├── index.blade.php            # nuevo
    │   ├── create.blade.php           # nuevo
    │   └── edit.blade.php             # nuevo
    ├── contratos/partials/costos-fijos-contrato.blade.php   # modificado — loop dinámico
    ├── locaciones/recibos/{create,edit,show,comprobante}.blade.php   # modificados — conceptos dinámicos
    ├── recibos/registro-masivo/{index,partials/*}.blade.php          # modificados — conceptos + totales + periodo ágil
    ├── lecturas/registro-masivo/index.blade.php                      # modificado — periodo ágil
    └── components/layouts/app-bootstrap.blade.php                    # modificado — enlace "Conceptos de Gasto Fijo"

routes/web.php   # rutas conceptosGastoFijo.*; periodo ágil no agrega rutas (reutiliza las existentes con
                 # query param periodo)
```

**Structure Decision**: aplicación Laravel monolítica existente — sin capas nuevas. La complejidad está en la
migración de datos y en la cantidad de puntos de escritura/lectura ya existentes que dependían de la
estructura fija anterior, no en la arquitectura del código nuevo en sí.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|---------------------------------------|
| Protección de "Renta" (no eliminar/desactivar) implementada en la capa de aplicación, no como constraint de base de datos | FR-002 exige impedirlo con un mensaje explicativo para el usuario; una constraint de BD solo daría un error SQL genérico | Una constraint `CHECK`/trigger de PostgreSQL sería más difícil de mantener y de mensaje menos claro que una validación de aplicación en el controlador/servicio — el proyecto ya resuelve protecciones similares (ej. no eliminar una locación con contratos) en la capa de aplicación, no en la base de datos |
| Dos tablas de detalle nuevas (`contrato_valores_concepto`, `recibo_conceptos`) en vez de seguir agregando columnas fijas | FR-001 exige que agregar un concepto nuevo no requiera cambio de código — una columna fija por concepto viola eso por definición | Seguir con columnas fijas (agregar una columna por cada concepto nuevo) fue exactamente el patrón que esta feature reemplaza a pedido explícito del usuario |
