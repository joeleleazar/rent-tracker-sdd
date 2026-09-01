---

description: "Task list for 043-panel-inicio-cobranza"
---

# Tasks: Panel de Inicio — Estado de Cobranza

**Input**: Design documents from `/specs/043-panel-inicio-cobranza/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/panel-inicio.md, quickstart.md

**Tests**: incluidas — el Principio IV de la Constitución exige cobertura de controladores y de la lógica de
cálculo, y el plan compromete `PanelInicioTest` (Feature) y `ServicioPanelCobranzaTest` (Unit).

**Organization**: 4 historias de usuario — US1 (morosos, P1, el núcleo), US4 (el panel es la pantalla de
inicio, P1), US2 (próximos vencimientos, P2), US3 (indicadores y contratos por vencer, P3) — más una fase
Foundational que cablea la ruta, el controlador, el servicio base y la vista shell que las cuatro comparten.

**Nota de entorno**: binario PHP de Herd `C:\Users\joel5\.config\herd\bin\php84\php.exe` para `artisan` /
`pest`; `npm run build` / `npm run dev` para assets.

**Alcance de datos**: 0 migraciones, 0 columnas, 0 tablas. Todo es lectura. El único cambio en código
existente es (a) el cuerpo de `Route::get('/dashboard')` y (b) el `<li>` de navegación que apunta a
`route('dashboard')`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y `npm run build`,
  verificar verde/sin errores; inventariar los usos de `route('dashboard')` en `tests/` y `resources/` con
  `grep -rn "route('dashboard')\|assertRedirect.*dashboard" tests/ resources/` (se espera 3 referencias en
  `tests/`; solo `LocacionControllerTest` afirma la redirección — ver T018) (research.md §7).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Propósito**: dejar la ruta, el controlador, el servicio base y la vista shell listos para que las cuatro
historias construyan encima.

**⚠️ CRITICAL**: ninguna historia puede completarse hasta terminar esta fase.

- [X] T002 En `routes/web.php`, dentro del grupo `['auth','cuenta.activa']`, cambiar el cuerpo de
  `Route::get('/dashboard', ...)` de `fn () => redirect()->route('locaciones.index')` a
  `[App\Http\Controllers\ControladorPanelInicio::class, 'index']`, **conservando** el nombre de ruta
  `dashboard`; agregar el `use` del controlador (contracts/panel-inicio.md §A; research.md §7).
- [X] T003 Crear `app/Http/Controllers/ControladorPanelInicio.php` con constructor que inyecta
  `App\Services\ServicioPanelCobranza` y `index(\Illuminate\Http\Request $solicitud): \Illuminate\View\View`
  que por ahora devuelve `view('panel.inicio', [...])` con estructuras vacías para `morosos`,
  `resumenMorosidad`, `filtros`, `proximos`, `resumenProximos`, `indicadores`, `contratosPorVencer`;
  nombres y PHPDoc en español (contracts/panel-inicio.md §B).
- [X] T004 Crear `app/Services/ServicioPanelCobranza.php` con un método privado
  `recibosBaseParaPanel(): \Illuminate\Support\Collection` que ejecuta
  `Recibo::query()->where('estado','!=','anulado')->withSum('conceptos as suma_conceptos','monto')->withSum('pagos as suma_pagos','monto')->with(['contrato.inquilinos','locacion'])->get()`,
  y los stubs públicos `recibosMorosos()`, `proximosVencimientos()`, `resumenMorosidad()`,
  `indicadoresDelPeriodo()`, `contratosPorVencer()` (research.md §2; data-model.md §1).
- [X] T005 Crear `resources/views/panel/inicio.blade.php` sobre `<x-layouts.app-bootstrap>` con un
  encabezado de página ("Inicio — Estado de cobranza") y tres `@include('panel.partials.morosos')`,
  `@include('panel.partials.proximos-vencimientos')`, `@include('panel.partials.indicadores')`; crear los
  tres parciales vacíos en `resources/views/panel/partials/` (contracts/panel-inicio.md §B).

**Checkpoint**: `/dashboard` renderiza el shell del panel para un usuario autenticado; US1–US4 pueden empezar.

---

## Phase 3: User Story 1 - Ver de un vistazo qué inquilinos están morosos (Priority: P1) 🎯 MVP

**Goal**: tabla de recibos morosos ordenada por días de atraso desc, con tarjetas de resumen y desglose por
antigüedad, filtrable por tramo y por rama de locación (el filtro recalcula también las tarjetas), con estado
vacío propio; cada fila enlaza al detalle del recibo.

**Independent Test**: con un recibo no anulado, saldo pendiente > 0 y fecha límite ya vencida, abrir el panel
y ver ese recibo en la tabla con inquilino/local/periodo/total/pagado/saldo/fecha límite/días de atraso
correctos, las tarjetas de resumen cuadrando, y la fila enlazando a `recibos.show`; aplicar `?tramo=` y
`?locacion=` y verificar que tabla y tarjetas responden juntas.

### Tests for User Story 1 ⚠️

- [X] T006 [P] [US1] Escribir `tests/Unit/ServicioPanelCobranzaTest.php` (grupo morosos) — debe fallar antes
  de T009: recibo anulado / pagado por completo / con fecha límite futura NO es moroso; no anulado + saldo>0 +
  fecha límite pasada SÍ; pagos ≥ total → saldo 0 y no moroso; `diasDeAtraso` = `now()->startOfDay()` menos
  el último sábado del mes del `periodo` (comparar contra `ServicioCalculoFechaLimitePago`); tramos en los
  límites 1/30/31/60/61/90/91; orden por `diasDeAtraso` desc; `resumenMorosidad` con `cantidadRecibos`,
  `cantidadInquilinos` (principales distintos), `montoAdeudadoVencido` y `Σ tramos == montoAdeudadoVencido`
  (data-model.md §2.1–§2.2, INV-M*/INV-R*).
- [X] T007 [P] [US1] Escribir `tests/Unit/ServicioJerarquiaLocacionesTest.php` — debe fallar antes de T008:
  `idsDeRama($id)` devuelve el id más todos sus descendientes en varios niveles, y solo `[$id]` para una
  hoja (research.md §3).
- [X] T013 [P] [US1] Escribir `tests/Feature/PanelInicioTest.php` (grupo morosos) — debe fallar antes de
  T009–T012: un usuario autenticado ve el recibo moroso con sus 8 columnas; recibo pagado/anulado/fecha
  futura ausentes; filas ordenadas por días de atraso desc; cada fila enlaza a `route('recibos.show', ...)`;
  `?tramo=31-60` combinado con `?locacion=<id de rama>` filtra las filas **y** recalcula las tarjetas de
  resumen y el desglose; estado vacío "No hay recibos vencidos impagos" sin filtros y mensaje de "sin
  resultados" con filtros (contracts/panel-inicio.md §B.1, §C; FR-010–FR-022).

### Implementation for User Story 1

- [X] T008 [P] [US1] Crear `app/Services/ServicioJerarquiaLocaciones.php` con
  `idsDeRama(int $locacionId): array` — `WITH RECURSIVE` sobre `locaciones (id, locacion_padre_id)` vía
  `DB::select` parametrizado (alternativa BFS en PHP documentada en research.md §3); devuelve el id + todos
  sus descendientes (FR-019).
- [X] T009 [US1] Implementar en `app/Services/ServicioPanelCobranza.php`:
  `recibosMorosos(?string $tramo = null, ?array $idsRama = null): \Illuminate\Support\Collection` y
  `resumenMorosidad(\Illuminate\Support\Collection $morosos): array`. Deriva por fila total/pagado/saldo,
  `fechaLimite` con `App\Services\ServicioCalculoFechaLimitePago`, filtra `saldo > 0` y
  `fechaLimite < now()->startOfDay()`, aplica los filtros de tramo y de rama, ordena por `diasDeAtraso` desc;
  el resumen se calcula sobre la colección ya filtrada (data-model.md §2.1–§2.2; FR-005–FR-017, FR-022).
- [X] T010 [US1] En `ControladorPanelInicio@index`: leer `tramo` y `locacion` de `$solicitud->query()`
  validando contra lista blanca (`1-30|31-60|61-90|90+` y una locación existente); resolver `idsRama` con
  `ServicioJerarquiaLocaciones::idsDeRama()` solo si hay `locacion`; pasar `morosos`, `resumenMorosidad` y
  `filtros` (incluye `tramo`, `locacion` y `locacionesDisponibles`) a la vista (contracts/panel-inicio.md §A).
- [X] T011 [P] [US1] Crear `resources/views/panel/partials/morosos.blade.php`: 3 tarjetas de resumen
  (`cantidadRecibos`, `cantidadInquilinos`, `montoAdeudadoVencido` con `S/` y `.cifra`); desglose por
  antigüedad en 4 celdas ("1 a 30", "31 a 60", "61 a 90", "más de 90", cada una con cantidad y monto);
  `<form method="GET" action="{{ route('dashboard') }}">` con `<select name="tramo">` y
  `<select name="locacion">` (auto-envío `hx-get`+`hx-trigger="change"` sobre el contenedor del bloque, más
  botón `type="submit"` "Filtrar" para el camino sin JS); `table-responsive` + `table-hover` con las columnas
  Inquilino · Local (`<x-ruta-jerarquia-locacion>`) · Periodo · Total · Pagado · Saldo pendiente · Fecha
  límite · Días de atraso (badge semántico por tramo, con etiqueta de texto); cada `<tr>` enlaza a
  `route('recibos.show', $fila->recibo)` (contracts/panel-inicio.md §B.1; Principio III/VI).
- [X] T012 [US1] En `resources/views/panel/partials/morosos.blade.php`, añadir el estado vacío: sin filtros y
  sin filas → `<x-estado-vacio>` con "No hay recibos vencidos impagos"; con filtros y sin filas → mensaje de
  "ningún resultado para el filtro"; nunca una tabla con encabezado y cero filas (FR-014, FR-021).

**Checkpoint**: bloque de morosos completo y filtrable — MVP desplegable junto con US4.

---

## Phase 4: User Story 4 - El panel es la pantalla de inicio tras iniciar sesión (Priority: P1)

**Goal**: al iniciar sesión, Master y Administrador aterrizan en el panel; la pantalla no ofrece ningún
control de escritura; el invitado es redirigido a login.

**Independent Test**: iniciar sesión como Master y como Administrador y verificar que ambos llegan al panel;
abrir `/dashboard` sin sesión y verificar la redirección a login; revisar el HTML del panel y confirmar que
no hay formularios ni botones de crear/editar/anular.

### Tests for User Story 4 ⚠️

- [X] T014 [P] [US4] Añadir a `tests/Feature/PanelInicioTest.php` (grupo acceso) — antes de T015/T016: un
  usuario con perfil Master recibe 200 en `route('dashboard')` y ve el panel; un Administrador recibe 200; un
  invitado es redirigido a `login`; la respuesta NO contiene `action="{{ route('pagos.store', ...) }}"`,
  `route('recibos.update')`, `route('recibos.estado.update')` ni `route('contratos.update')`, ni botones
  "Registrar Pago" / "Anular" / "Editar" (contracts/panel-inicio.md §A, §C; FR-001–FR-003, SC-008).

### Implementation for User Story 4

- [X] T015 [US4] Actualizar en `tests/Feature/LocacionControllerTest.php` la prueba "la ruta dashboard
  redirige al arbol unificado de locaciones": pasa a afirmar que `route('dashboard')` **renderiza el panel**
  (`assertOk()` + `assertSee` de un texto propio del panel), en vez de
  `assertRedirect(route('locaciones.index'))` (research.md §7; SC-007).
- [X] T016 [US4] En `resources/views/components/layouts/app-bootstrap.blade.php`, reetiquetar el `<li>` de
  navegación que apunta a `route('dashboard')`: texto "Inicio" (o "Panel"), ícono propio (p. ej.
  `bi-clipboard-data`), conservando `request()->routeIs('dashboard')` para el estado `active`; el ítem
  "Gestionar Locaciones" → `locaciones.index` no cambia (research.md §7).

**Checkpoint**: el panel es el destino post-login de ambos perfiles y no tiene controles de escritura.

---

## Phase 5: User Story 2 - Anticiparse a los pagos que están por vencer (Priority: P2)

**Goal**: bloque con los recibos con saldo pendiente cuya fecha límite aún no venció, ordenados por fecha
límite asc, con tarjeta de resumen; cada fila enlaza al detalle del recibo. Sin filtros.

**Independent Test**: con un recibo no anulado, saldo pendiente > 0 y fecha límite hoy o futura, abrir el
panel y verificar que aparece en el bloque de próximos vencimientos (no en morosos), con días restantes ≥ 0,
orden por fecha límite asc, y la tarjeta de resumen con cantidad y monto correctos.

### Tests for User Story 2 ⚠️

- [X] T017 [P] [US2] Añadir a `tests/Unit/ServicioPanelCobranzaTest.php` (grupo próximos) — antes de T018:
  recibo no anulado con saldo>0 y `fechaLimite >= hoy` aparece; recibo con `fechaLimite == hoy` → próximo con
  0 días restantes y NO moroso; orden por `fechaLimite` asc; `resumenProximos` = `{cantidad, montoTotal = Σ
  saldoPendiente}` (data-model.md §2.3, INV-P*).

### Implementation for User Story 2

- [X] T018 [US2] Implementar `ServicioPanelCobranza::proximosVencimientos(): \Illuminate\Support\Collection`
  (misma colección base; `saldo > 0` y `fechaLimite >= now()->startOfDay()`; `diasRestantes`; orden asc) y el
  cálculo de `resumenProximos`; pasarlos desde `ControladorPanelInicio@index` a la vista
  (data-model.md §2.3; FR-023–FR-025).
- [X] T019 [P] [US2] Crear `resources/views/panel/partials/proximos-vencimientos.blade.php`: tarjeta de
  resumen (cantidad + monto, `S/` + `.cifra`); `table-responsive` + `table-hover` con Inquilino · Local
  (`<x-ruta-jerarquia-locacion>`) · Periodo · Saldo pendiente · Fecha límite · Días restantes; cada `<tr>`
  enlaza a `route('recibos.show', $fila->recibo)`; estado vacío "No hay pagos próximos a vencer"
  (contracts/panel-inicio.md §B.2; FR-024–FR-027).

### Tests for User Story 2 (verificación)

- [X] T020 [US2] Añadir a `tests/Feature/PanelInicioTest.php` (grupo próximos): el recibo en plazo aparece en
  este bloque y no en morosos; orden por fecha límite asc; tarjeta de resumen correcta; estado vacío; enlace
  a `recibos.show`.

**Checkpoint**: US1, US2 y US4 funcionan de forma independiente.

---

## Phase 6: User Story 3 - Indicadores generales de cobranza y contratos por vencer (Priority: P3)

**Goal**: cinco stat cards (facturado del periodo, cobrado de recibos del periodo, recaudado este mes, tasa
de cobranza, cartera total por cobrar) y tres grupos acumulativos de contratos que vencen dentro de 30/15/7
días, cada contrato enlazando a su detalle.

**Independent Test**: con recibos y pagos del mes en curso y un contrato `activo` que vence en 5 días, abrir
el panel y verificar las cinco cards contra los datos (incluida la guarda de división por cero) y que el
contrato aparece en los tres grupos, enlazando a `contratos.show`.

### Tests for User Story 3 ⚠️

- [X] T021 [P] [US3] Añadir a `tests/Unit/ServicioPanelCobranzaTest.php` (grupo indicadores) — antes de T023:
  facturado del periodo = Σ totales de recibos no anulados con `periodo` = mes en curso; cobrado de recibos
  del periodo = Σ pagos imputados a esos recibos (cualquier `fecha_pago`); recaudado este mes = Σ pagos con
  `fecha_pago` en el mes en curso, excluye pagos de recibos anulados, incluye pagos de recibos de periodos
  anteriores; tasa = cobrado÷facturado en %, facturado 0 → `null` (vista muestra "—"); cartera total = Σ
  `max(0, total − pagado)` por recibo (data-model.md §2.4, INV-I*).
- [X] T022 [P] [US3] Añadir a `tests/Unit/ServicioPanelCobranzaTest.php` (grupo contratos por vencer) — antes
  de T023: contrato `estado='activo'` con `fecha_fin` en 5 días → en los 3 grupos; en 20 días → solo en el de
  30; `fecha_fin` ayer → en ninguno; `estado != 'activo'` → en ninguno; orden por `fecha_fin` asc dentro de
  cada grupo (data-model.md §2.5, INV-C*).

### Implementation for User Story 3

- [X] T023 [US3] Implementar en `app/Services/ServicioPanelCobranza.php`:
  `indicadoresDelPeriodo(): array` (los 5 indicadores; una consulta adicional
  `Pago::whereBetween('fecha_pago', [inicioMes, finMes])->whereHas('recibo', fn ($q) => $q->where('estado','!=','anulado'))->sum('monto')`
  para "recaudado este mes") y
  `contratosPorVencer(): array` con claves `dentro7`, `dentro15`, `dentro30`
  (`Contrato::where('estado','activo')->whereBetween('fecha_fin', [now()->startOfDay(), now()->startOfDay()->addDays(30)])->with(['locacion','inquilinos'])->orderBy('fecha_fin')->get()`
  agrupado de forma acumulativa en PHP); pasarlos desde `ControladorPanelInicio@index` a la vista
  (data-model.md §2.4–§2.5; FR-028–FR-033).
- [X] T024 [P] [US3] Crear `resources/views/panel/partials/indicadores.blade.php`: 5 stat cards (`S/` +
  `.cifra`; la tasa como `%` o "—"/"sin datos" cuando es `null`); tres grupos rotulados "Vencen en 7 días",
  "en 15 días", "en 30 días", cada uno con la lista de sus contratos (local + inquilino principal + fecha de
  fin + días restantes) enlazando a `route('contratos.show', $contrato)`; grupo sin contratos → línea
  "ninguno" discreta sin ocultar el rótulo (contracts/panel-inicio.md §B.3; FR-028–FR-033).

### Tests for User Story 3 (verificación)

- [X] T025 [US3] Añadir a `tests/Feature/PanelInicioTest.php` (grupo indicadores): las 5 cards con valores
  que cuadran con los datos; facturado 0 → "sin datos"; contrato a 5 días en los 3 grupos y contrato vencido
  en ninguno; cada contrato enlaza a `contratos.show`.

**Checkpoint**: las cuatro historias funcionan de forma independiente.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T026 Escenario de rendimiento de `quickstart.md` (Escenario 9): sembrar ≥ 300 recibos no anulados
  (varios periodos, con y sin pagos) y ≥ 100 contratos; medir el render de `/dashboard` (< 2 s) y el número
  de consultas SQL (≤ 5; con `DB::listen` o un contador en el test); ajustar el eager-loading si se excede
  (SC-006).
- [X] T027 [P] Revisión con el skill `impeccable` (`/impeccable audit` o `/impeccable polish`) sobre
  `resources/views/panel/**` y el `<li>` de navegación modificado en
  `resources/views/components/layouts/app-bootstrap.blade.php`; aplicar hallazgos; si la fila de "stat cards"
  del panel es un patrón nuevo del sistema, documentarla en `DESIGN.md` (`/impeccable document`)
  (Constitución, Principio VI).
- [X] T028 [P] Auditoría de nomenclatura en español sobre los archivos nuevos (`ControladorPanelInicio`,
  `ServicioPanelCobranza`, `ServicioJerarquiaLocaciones`, las vistas de `panel/`, variables y comentarios)
  (Constitución, Principio II).
- [X] T029 Correr `php artisan test` completo (binario Herd) — toda la suite en verde, incluida la prueba
  ajustada de `LocacionControllerTest` (T015) — y `npm run build` sin errores (SC-007).
- [X] T030 Ejecutar la guía `quickstart.md` completa (Escenarios 1–9 + verificación de interfaz) como
  verificación final del mapeo FR/SC.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de Setup. **Bloquea todas las historias.**
- **US1 (Phase 3)**: depende de Foundational.
- **US4 (Phase 4)**: depende de Foundational; se apoya en el contenido de US1 para la verificación de "sin
  controles de escritura" (T014 es más fuerte con la tabla de morosos ya renderizada), pero la ruta y el
  acceso ya existen desde Foundational.
- **US2 (Phase 5)**: depende de Foundational. Independiente de US1/US4.
- **US3 (Phase 6)**: depende de Foundational. Independiente de US1/US2/US4.
- **Polish (Phase 7)**: depende de las historias entregadas.

### Archivos compartidos (coordinar si se paraleliza entre personas)

- `app/Services/ServicioPanelCobranza.php`: T004 (stub) → T009 (US1) → T018 (US2) → T023 (US3). Secuencial;
  el orden de fases lo resuelve.
- `app/Http/Controllers/ControladorPanelInicio.php`: T003 → T010 (US1) → T018/T023 (cableado de datos).
- `resources/views/panel/inicio.blade.php`: T005; los parciales por bloque son archivos distintos.
- `tests/Feature/PanelInicioTest.php`: T013 (US1) → T014 (US4) → T020 (US2) → T025 (US3), grupos distintos.
- `tests/Unit/ServicioPanelCobranzaTest.php`: T006 → T017 → T021 → T022, grupos distintos.

### Within Each User Story

- Los tests de historia se escriben primero y deben fallar antes de implementar.
- US1: T006/T007/T013 (tests) → T008 y T011 en paralelo → T009 → T010 → T012.
- US4: T014 (test) → T015 y T016 en paralelo.
- US2: T017 (test) → T018 → T019.
- US3: T021/T022 (tests) → T023 → T024.

### Parallel Opportunities

- Setup: —
- Foundational: T002, T003, T004, T005 tocan archivos distintos; T003 referencia a T004 por el type-hint,
  así que hacer T004 antes o a la vez.
- US1: **T006, T007, T013** (tests, archivos distintos) en paralelo; luego **T008** (`ServicioJerarquiaLocaciones`)
  y **T011** (`morosos.blade.php`) en paralelo con **T009** (`ServicioPanelCobranza`).
- US4: **T014, T015, T016** en paralelo (test / test ajeno / layout).
- US2: **T017** en paralelo con la implementación de US3 si hay dos personas; **T019** en paralelo con T018.
- US3: **T021, T022** en paralelo; **T024** en paralelo con T023.
- Polish: **T027, T028** en paralelo.

---

## Parallel Example: User Story 1

```bash
# Primero los tests de la historia (deben fallar):
Task: "T006 Unit ServicioPanelCobranzaTest — grupo morosos"
Task: "T007 Unit ServicioJerarquiaLocacionesTest"
Task: "T013 Feature PanelInicioTest — grupo morosos"

# Luego, en paralelo (archivos distintos):
Task: "T008 ServicioJerarquiaLocaciones::idsDeRama()"
Task: "T011 resources/views/panel/partials/morosos.blade.php"
```

---

## Implementation Strategy

### MVP First (US1 + US4)

1. Fase 1: Setup.
2. Fase 2: Foundational (ruta + controlador + servicio base + vista shell).
3. Fase 3: US1 (morosos con filtros) → validar Escenarios 3–5 de `quickstart.md`.
4. Fase 4: US4 (acceso de ambos perfiles + solo lectura) → validar Escenarios 1–2.
5. **PARAR y VALIDAR**: el pedido central —"al iniciar sesión veo quién está moroso"— está entregado.
   Desplegable como MVP.

### Incremental Delivery

1. Setup + Foundational → shell del panel.
2. US1 + US4 → morosos + pantalla de inicio → demo (MVP).
3. US2 → próximos vencimientos → demo.
4. US3 → indicadores + contratos por vencer → demo.

### Parallel Team Strategy

Tras Foundational: Dev A → US1, Dev B → US4 (coordina el reetiquetado del sidebar y el ajuste de
`LocacionControllerTest`), Dev C → US2, Dev D → US3. El único punto de contención de código es
`ServicioPanelCobranza.php` / `ControladorPanelInicio.php` (métodos aditivos por historia).

---

## Notes

- `[P]` = archivos distintos, sin dependencia mutua.
- Ninguna tarea crea migraciones, rutas de escritura, modelos ni Form Requests.
- Verificar que los tests de cada historia fallan antes de implementarla.
- Hacer commit tras cada tarea o grupo lógico.
