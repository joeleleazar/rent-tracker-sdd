# Implementation Plan: Panel de Inicio — Estado de Cobranza

**Branch**: `043-panel-inicio-cobranza` | **Date**: 2026-08-30 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/043-panel-inicio-cobranza/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Una pantalla nueva de **solo lectura** que se convierte en el destino post-login (`/dashboard`) para los
perfiles Master y Administrador. Consolida el estado de cobranza en tres bloques:

1. **Morosos (P1)** — tabla de recibos con `saldo pendiente > 0`, `estado != anulado` y **fecha límite de
   pago ya vencida**, ordenada por días de atraso desc; tarjetas de resumen (recibos morosos, inquilinos
   distintos, monto adeudado vencido) y desglose por tramos de antigüedad (1–30 / 31–60 / 61–90 / +90); dos
   filtros combinables (tramo de antigüedad + rama de la jerarquía de locaciones) que **recalculan también
   las tarjetas y el desglose** sobre el subconjunto filtrado (decisión Q3=A). Estado vacío propio.
2. **Próximos vencimientos (P2)** — recibos con `saldo pendiente > 0` cuya fecha límite es hoy o futura,
   ordenados por fecha límite asc, con tarjeta de resumen (cantidad + monto). Sin filtros.
3. **Indicadores (P3)** — facturado del periodo, **cobrado de recibos del periodo** (pagos de recibos del
   mes en curso, cualquier fecha de pago), **recaudado este mes** (pagos con `fecha_pago` en el mes en curso,
   cualquier periodo), tasa de cobranza (= cobrado-de-recibos-del-periodo ÷ facturado, con guarda de división
   por cero), cartera total por cobrar (Σ saldos pendientes de todos los periodos), y **contratos por vencer**
   en tres grupos **acumulativos** 30/15/7 días (solo `estado = 'activo'` con `fecha_fin` entre hoy y hoy+N),
   cada contrato enlazando a su detalle (decisión Q2=A).

**La fecha límite de pago NO se persiste**: se deriva con el servicio ya existente
`App\Services\ServicioCalculoFechaLimitePago` (último sábado del mes del `periodo`), que es una función pura
sin consultas — evaluarla para unos cientos de recibos no compromete el objetivo de <2 s, así que la opción
de columna nueva evaluada en el spec se descarta.

**Reutiliza sin cambios**: `Recibo::total()` / `montoPagado()` / `saldoPendiente()` (ya clampa a 0),
`Recibo::scopeVigente()` (`estado != anulado`), `Contrato::inquilinoPrincipal()`,
`Locacion::rutaJerarquiaTruncada()` + `<x-ruta-jerarquia-locacion>`, el componente de estado vacío, el
patrón de filtro `<form method="GET">` + `hx-get` con degradación por `hx-boost`, y el estado `'activo'` del
enum de `contratos`.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 13.x, Blade, PostgreSQL 15+. Mismo stack del proyecto, sin
dependencias nuevas.

**Primary Dependencies**: Ninguna nueva. Se apoya en Eloquent (agregados `withSum`), el servicio existente
`ServicioCalculoFechaLimitePago`, Bootstrap 5.3 + Bootstrap Icons, y htmx (`hx-boost`) para los filtros.

**Storage**: PostgreSQL, **solo lectura**. 0 migraciones, 0 columnas nuevas, 0 tablas nuevas. Todas las
cifras se derivan en cada request de `recibos`, `recibo_conceptos`, `pagos`, `contratos`,
`contrato_inquilino`, `inquilinos` y `locaciones`.

**Testing**: Pest 4.7 — Feature test del controlador (acceso de ambos perfiles, redirección de invitado,
ausencia de controles de escritura, orden de cada listado, estados vacíos, filtros combinados y su efecto
sobre las tarjetas, enlaces correctos) y Unit tests del/los servicio(s) de cálculo (definición de morosidad
con sus tres condiciones, saldo nunca negativo, límites exactos de los tramos de antigüedad, fecha límite vía
el servicio existente, grupos acumulativos 30/15/7, aritmética de los indicadores y guarda de división por
cero).

**Target Platform**: Aplicación web Laravel servida por Herd; navegadores de escritorio y móviles modernos.

**Project Type**: Aplicación web monolítica existente — una vista nueva sobre el layout `app-bootstrap` y la
navegación ya existentes, más un controlador y uno o dos servicios de consulta. Sin cambio de estructura.

**Performance Goals**:
- El panel completo se renderiza en **< 2 s** con ≥ 300 recibos y ≥ 100 contratos (SC-006).
- Presupuesto de consultas: **≤ 5 consultas** para todo el panel — (1) recibos no anulados con
  `withSum(conceptos)` y `withSum(pagos)` + `with(contrato.inquilinos, locacion)`, reutilizada para morosos,
  próximos vencimientos, cartera total y facturado/cobrado del periodo; (2) `pagos` por `fecha_pago` del mes
  para "recaudado este mes"; (3) contratos `activo` con `fecha_fin` en ventana para "por vencer"; (4) árbol
  de locaciones para el `<select>` del filtro; (5) IDs de la rama seleccionada (solo si hay filtro de
  locación activo).
- Sin caché ni "última actualización": se recalcula en cada carga.

**Constraints**:
- **Solo lectura**: ningún endpoint de escritura, ningún formulario que cree/edite/anule, ninguna mutación
  de datos al aplicar filtros (FR-002, FR-037, SC-008).
- **Ambos perfiles**: la ruta vive bajo `['auth','cuenta.activa']` únicamente (sin `perfil.master`), por lo
  que Master y Administrador la ven por igual (FR-001); invitado → `login` (FR-003).
- **Exclusión de anulados** en todos los cálculos, incluido "recaudado este mes" (un pago de un recibo
  anulado no cuenta) (FR-004).
- **Saldo pendiente nunca negativo**: se usa `Recibo::saldoPendiente()` (ya clampa) y, para la cartera total,
  se suma el clamp **por recibo**, nunca `Σtotal − Σpagos` global (FR-008).
- **Fecha límite** = último sábado del mes del `periodo` (si el mes termina en sábado, ese día), vía
  `ServicioCalculoFechaLimitePago` (FR-006, misma regla que specs/008).
- **Días de atraso / restantes** en días completos entre la fecha límite y `now()` (FR-009).
- **Filtro de rama de locación jerárquico**: incluye la locación elegida y todas sus descendientes (FR-019).
- **Degradación elegante**: los filtros funcionan sin JavaScript por recarga de página (`<form method="GET">`);
  htmx solo acelera (FR-020).
- Toda vista nueva pasa por la revisión con el skill `impeccable` antes de cerrarse (Constitución,
  Principio VI).

**Scale/Scope**: 1 controlador nuevo (`ControladorPanelInicio`, acción `index`); 1–2 servicios nuevos
(`ServicioPanelCobranza` para morosos/próximos/indicadores; helper de descendientes de locación —
`ServicioJerarquiaLocaciones::idsDeRama()` o método en `Locacion`); 1 vista Blade nueva
(`resources/views/panel/inicio.blade.php`) con parciales por bloque; reemplazo del cuerpo de la ruta
`dashboard` en `routes/web.php` (de `redirect()` a la acción del controlador); ajuste del ítem de navegación
"Locaciones" del sidebar (hoy apunta a `dashboard`); ~2 archivos de test nuevos. 0 migraciones.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)**: Cumple — Eloquent con agregados `withSum`
  (subconsultas SQL, sin bypass del ORM); lógica de negocio en Services desacoplados; sin migraciones ni
  cambios de esquema. Si el helper de descendientes de locación usa un `WITH RECURSIVE` de PostgreSQL, va vía
  `DB::` parametrizado y con prueba unitaria (alternativa en PHP documentada en research.md).
- **II. Nomenclatura y Código Estrictamente en Español**: Cumple — `ControladorPanelInicio`,
  `ServicioPanelCobranza`, métodos `recibosMorosos()`, `proximosVencimientos()`, `indicadoresDelPeriodo()`,
  `contratosPorVencer()`, `tramoDeAntiguedad()`, variables `saldoPendiente`, `diasDeAtraso`,
  `montoAdeudadoVencido`; vistas en `resources/views/panel/`; rutas con nombre `panel.inicio` (o se conserva
  `dashboard`). Comentarios PHPDoc en español.
- **III. Diseño Moderno e Intuitivo**: Aplica — tarjetas para los resúmenes; `table-responsive` +
  `table-hover` para los listados; `badge` con color semántico para el tramo de atraso / estado; `breadcrumb`
  (`<x-ruta-jerarquia-locacion>`) para el local; prefijo `S/` e `input-group` no aplica (solo lectura) pero
  sí `.cifra` con numerales tabulares en toda columna de monto; sin acciones destructivas (pantalla de
  consulta). Estado vacío con el componente firma del proyecto.
- **IV. Pruebas Automatizadas Exhaustivas (Modelos y Controladores)**: Aplica — Feature test del controlador
  (200 para ambos perfiles, redirección de invitado, orden de cada listado, estados vacíos, filtros y su
  efecto en tarjetas, enlaces, ausencia de controles de escritura) y Unit tests del servicio (las tres
  condiciones de morosidad, clamping del saldo, límites de tramos 30/60/90, grupos acumulativos de contratos,
  tasa de cobranza y división por cero, "recaudado este mes" vs "cobrado de recibos del periodo"). Ninguna
  integración con tests en rojo.
- **V. Integridad de Datos y Seguridad Transaccional**: Aplica de forma acotada — no hay operaciones de
  escritura, por lo tanto no hay `DB::transaction`; los montos usan los mismos tipos `decimal:2` y helpers ya
  existentes de `Recibo`/`Pago` (precisión exacta); CSRF no aplica a un `GET` de consulta; sin exposición de
  datos fuera de lo que el usuario ya puede ver en las pantallas de detalle enlazadas.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple — solo primitivas Bootstrap (`card`,
  `table-responsive`/`table-hover`, `badge`, `breadcrumb`), iconografía `bi-*` consistente, filtros con
  `hx-boost`/`hx-get` y degradación a `GET` clásico; `@media print` no requerido. El ítem de navegación
  reetiquetado y la vista nueva pasan por `/impeccable` (`polish`/`audit`) antes de cerrar, con actualización
  de `DESIGN.md` si introduce un patrón nuevo (p. ej. la fila de "stat cards" del panel).

**Resultado del gate**: **PASA** sin excepciones ni entradas en Complexity Tracking. No se modifican reglas
de la Constitución ni de `DESIGN.md`; la feature es aditiva y de solo lectura.

**Re-evaluación post-diseño (Phase 1)**: PASA sin cambios. `research.md`, `data-model.md`,
`contracts/panel-inicio.md` y `quickstart.md` no introdujeron entidades persistidas, dependencias ni patrones
nuevos: 1 controlador, 1–2 servicios de consulta, 1 vista con parciales, reutilización de
`ServicioCalculoFechaLimitePago` y de los helpers de `Recibo`/`Contrato`/`Locacion`. Sin Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/043-panel-inicio-cobranza/
├── spec.md                       # Especificación (entrada)
├── plan.md                       # Este archivo (/speckit-plan)
├── research.md                   # Fase 0 — decisiones: no persistir fecha límite, agregados withSum vs child-collections,
│                                 #   descendientes de locación (CTE vs PHP), reuso de ServicioCalculoFechaLimitePago,
│                                 #   cableado de la ruta dashboard, "contratos por vencer" vs servicio de notificación existente
├── data-model.md                 # Fase 1 — sin entidades persistidas; entidades derivadas y sus reglas
├── contracts/
│   └── panel-inicio.md           # Fase 1 — contrato de la ruta GET del panel, parámetros de filtro, forma de cada bloque
├── quickstart.md                 # Fase 1 — guía de verificación (datos semilla + escenarios + suite + <2 s)
└── checklists/
    └── requirements.md           # Checklist de calidad de la spec (completo)
```

### Source Code (repository root)

```text
app/
├── Http/
│   └── Controllers/
│       └── ControladorPanelInicio.php        # acción index(Request): arma los 3 bloques y los pasa a la vista;
│                                             #   lee los filtros `tramo` y `locacion` de la query string
├── Services/
│   ├── ServicioPanelCobranza.php             # recibosMorosos(?tramo, ?idsRama), proximosVencimientos(),
│   │                                         #   resumenMorosidad(coleccionFiltrada), indicadoresDelPeriodo(),
│   │                                         #   contratosPorVencer() — todo de solo lectura
│   └── ServicioJerarquiaLocaciones.php       # idsDeRama(int $locacionId): array<int>  (locación + descendientes)
│                                             #   [alternativa: método en App\Models\Locacion, ver research.md]

resources/views/
└── panel/
    ├── inicio.blade.php                       # layout de la pantalla: 3 bloques apilados sobre app-bootstrap
    └── partials/
        ├── morosos.blade.php                  # tarjetas de resumen + desglose por antigüedad + filtros + tabla / estado vacío
        ├── proximos-vencimientos.blade.php    # tarjeta de resumen + tabla / estado vacío
        └── indicadores.blade.php              # 5 stat cards + 3 grupos acumulativos de contratos por vencer

routes/web.php                                 # el cuerpo de Route::get('/dashboard', ...) pasa de redirect()
                                               #   a [ControladorPanelInicio::class, 'index']; nombre 'dashboard' conservado

resources/views/components/layouts/
└── app-bootstrap.blade.php                    # el <li> de navegación que apunta a route('dashboard') se
                                               #   reetiqueta ("Inicio" / "Panel") con un ícono propio; el ítem
                                               #   "Gestionar Locaciones" → locaciones.index no cambia

DESIGN.md                                      # + patrón "fila de stat cards del panel" si la revisión impeccable
                                               #   lo considera un componente nuevo del sistema

tests/
├── Feature/
│   └── PanelInicioTest.php                    # acceso ambos perfiles / invitado; orden de listados; estados vacíos;
│                                              #   filtros combinados y su efecto en tarjetas; enlaces; sin escritura
└── Unit/
    └── ServicioPanelCobranzaTest.php          # morosidad (3 condiciones), saldo ≥ 0, tramos 30/60/90,
                                               #   acumulativos 30/15/7, indicadores + división por cero
```

**Structure Decision**: Sin cambio de estructura de proyecto. Se sigue el patrón ya usado por
`SeguimientoPagosController` (specs/032): un controlador delgado + un Service de consulta + una vista con
parciales, todo de solo lectura. La ruta `dashboard` existente se reutiliza como punto de entrada (era un
`redirect()` de relleno desde specs/013) para no tocar `AuthenticatedSessionController` ni la ruta raíz.

## Complexity Tracking

> Sin entradas — el Constitution Check pasó sin excepciones.
