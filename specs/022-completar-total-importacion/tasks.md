---

description: "Task list for 022-completar-total-importacion"
---

# Tasks: Completar Total en Importación Histórica y Seeder

**Input**: Design documents from `/specs/022-completar-total-importacion/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/calculo-total.md, quickstart.md

**Nota de proceso**: esta feature se documenta retroactivamente — el código ya estaba implementado y
verificado antes de escribir spec.md/plan.md/tasks.md (ver "Nota de Proceso" en spec.md). Todas las tareas
se marcan `[X]` porque describen trabajo ya hecho, no trabajo pendiente. Se mantiene el mismo nivel de
detalle que el resto de las specs del proyecto para dejar un historial consistente.

**Tests**: sin tareas de test dedicadas — ni `ImportarLecturasMedidorHistoricas` ni `DatabaseSeeder` tienen
suite Pest propia (plan.md, Constitution Check, Principio IV); la verificación fue manual/directa
(quickstart.md) más la suite completa existente, que no tiene tests propios de estos dos archivos pero sí
cubre que no hay regresión en los dos flujos hermanos (`LecturaMedidorController`,
`RegistroMasivoLecturasController`).

**Organization**: dos historias de usuario (US1 comando de importación, US2 seeder) — independientes entre
sí (archivos distintos, sin dependencia de código uno sobre otro).

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest` en esta máquina.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar 256/256 en verde antes de tocar ningún archivo (heredado de la verificación de cierre de specs/021, sin regresión previa).

## Phase 2: Foundational

No hay tareas fundacionales separadas — ambas historias solo necesitan que `total` ya sea `NOT NULL`
(specs/019) y que exista `LecturaMedidorController::calcularTotal()` como referencia de criterio
(research.md Decisión 1), ambos ya vigentes antes de esta feature.

**Checkpoint**: sin bloqueos — US1 y US2 son independientes y paralelizables entre sí.

---

## Phase 3: User Story 1 - Importar el histórico de lecturas sin que la importación falle (Priority: P1)

**Goal**: `php artisan medidores:importar-historico` completa sin error de base de datos, con `total`
calculado en cada lectura importada.

**Independent Test**: correr el comando contra un `extracted.json` de ejemplo y confirmar 0 lecturas con
`total` nulo entre las creadas (quickstart.md Escenario 2).

### Implementation for User Story 1

- [X] T002 [P] [US1] En `app/Console/Commands/ImportarLecturasMedidorHistoricas.php`: importar `App\Models\ConfiguracionGeneral`; leer `$tarifa = (float) ConfiguracionGeneral::actual()->tarifa_luz_por_unidad` una sola vez antes del bucle principal; agregar `'total' => round($consumo * $tarifa, 2)` al `LecturaMedidor::create()` existente (research.md Decisión 2; contracts/calculo-total.md Contrato 1).
- [X] T003 [US1] Verificar manualmente (sin ejecutar el comando real, que requiere un archivo de origen no versionado) que la lógica agregada es correcta por revisión de código y que no se rompió ninguna otra parte del comando (creación de locaciones/zonas, mapeo de reporte, transacción). Depende de T002.

**Checkpoint**: US1 completa — el comando ya no fallará por `total` nulo la próxima vez que se ejecute.

---

## Phase 4: User Story 2 - Poblar un entorno de desarrollo nuevo sin que el seeder falle (Priority: P1)

**Goal**: `php artisan db:seed` completa sin error de base de datos, con `total` calculado en las 3 lecturas
de ejemplo del Local 101.

**Independent Test**: correr el seeder sobre una base de datos vacía y confirmar que las 3 lecturas de
ejemplo quedan con `total` no nulo, coherente con la tarifa `0.85` (quickstart.md Escenario 1).

### Implementation for User Story 2

- [X] T004 [P] [US2] En `database/seeders/DatabaseSeeder.php`: agregar `'total' => round($consumo * 0.85, 2)` al `LecturaMedidor::create()` del bloque de lecturas del Local 101 (línea ~103-116), calculando `$consumo` igual que ya se calculaba antes (research.md Decisión 3; contracts/calculo-total.md Contrato 2).
- [X] T005 [US2] Correr `php artisan migrate:fresh --seed` (binario Herd) sobre la base de datos de desarrollo y confirmar que termina en éxito, sin `SQLSTATE[23502]`. Depende de T004.

**Checkpoint**: US2 completa — el seeder ya no fallará por `total` nulo la próxima vez que se ejecute.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T006 Verificar que no había datos irregulares que corregir en la base de datos de desarrollo: 0 lecturas con `total` nulo, 0 con `total = 0`, 0 locaciones `%Historico Medidores%` (comando nunca ejecutado) — confirma que esta feature no necesita backfill (research.md Decisión 4; quickstart.md "Verificación ya realizada").
- [X] T007 Correr `php artisan test` completo (binario Herd) una vez más y confirmar 0 regresiones sobre toda la suite (256/256), en particular sobre `LecturaMedidorControllerTest` y `RegistroMasivoLecturasControllerTest`, que ejercitan el mismo criterio de cálculo de `total` reutilizado aquí.
- [X] T008 Documentar el fix retroactivamente: nota "Actualización (specs/021)" y "Decisión 10" agregadas a `specs/019-total-editable-recibos/research.md`, y esta spec completa (specs/022) creada a pedido explícito del usuario para mantener un historial de specs limpio y completo.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: vacía.
- **User Story 1 (Phase 3)** y **User Story 2 (Phase 4)**: ambas dependen solo de T001; son independientes
  entre sí (archivos distintos, sin dependencia de código compartido más allá del criterio ya existente en
  `LecturaMedidorController::calcularTotal()`, que ninguna de las dos modifica).
- **Polish (Phase 5)**: depende de que US1 y US2 estén completas.

### Parallel Opportunities

- T002 (US1) y T004 (US2) en paralelo entre sí (archivos distintos).

---

## Implementation Strategy

### MVP First

Ambas historias tienen prioridad P1 y son igual de urgentes (cualquiera de los dos procesos falla apenas se
ejecute) — no hay un MVP parcial razonable menor a completar ambas.

### Incremental Delivery (tal como ocurrió realmente)

1. T001 (línea base en verde).
2. US1 y US2 implementadas juntas, en la misma sesión de trabajo ("regulariza todo ahora").
3. T005-T007 (validación: `migrate:fresh --seed` en éxito, suite completa en verde).
4. T008 (esta spec — documentación retroactiva, Phase 5, posterior a la implementación real).

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2] = trazabilidad a las dos historias de usuario de `spec.md`.
- Ningún archivo de rutas, controladores HTTP ni vistas Blade se toca en esta feature — el cambio es
  exclusivo a un comando Artisan y un seeder.
- A diferencia del resto de las specs del proyecto, aquí el orden real fue implementación → documentación
  (ver "Nota de Proceso" en spec.md), no al revés — por eso todas las tareas nacen ya marcadas `[X]`.
