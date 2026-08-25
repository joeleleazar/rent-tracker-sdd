---

description: "Task list for 017-columna-consumo-lecturas"
---

# Tasks: Columna de Consumo y Alineación del Ícono de Completado en Registro Masivo

**Input**: Design documents from `/specs/017-columna-consumo-lecturas/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/columna-consumo-y-icono.md, quickstart.md

**Tests**: incluidas — el Principio IV de la constitución exige cobertura obligatoria en
controladores/vistas, y `research.md` identificó explícitamente la brecha de pruebas a cerrar
(orden de encabezado, presencia/contenido inicial de la celda nueva, orden ícono/valor).

**Organization**: tareas agrupadas por historia de usuario (spec.md), en orden de prioridad.

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`)
para `artisan`/`pest` en esta máquina — el PHP 8.0.30 de PATH no cumple el mínimo del proyecto.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test --filter=RegistroMasivoLecturasControllerTest` (binario Herd) y verificar que las 18 pruebas existentes siguen en verde antes de tocar ningún archivo.

## Phase 2: Foundational

No hay tareas fundacionales para esta feature: US1 y US2 tocan archivos distintos (US1: SCSS +
`index.blade.php` + `fila-registro-masivo.blade.php` + JS; US2: solo
`campo-lectura-registro-masivo.blade.php`), no hay rutas, migraciones ni servicios nuevos, y
ninguna historia bloquea a la otra (research.md, plan.md).

**Checkpoint**: sin bloqueos — se puede empezar directamente por la Historia 1 (P1).

---

## Phase 3: User Story 1 - Ver el consumo de cada locación sin calcularlo a mano (Priority: P1) 🎯 MVP

**Goal**: agregar la columna "Consumo" al registro masivo, con el mismo valor, formato y orden que
ya expone la exportación a Excel/PDF de la misma pantalla (contracts/columna-consumo-y-icono.md,
Contrato 1).

**Independent Test**: abrir `/lecturas/registro-masivo` con una locación que tenga lectura anterior
y lectura actual guardadas; verificar que aparece la columna "Consumo" entre "Lectura Actual" y
"Total" con el valor `lectura_actual − lectura_anterior`, igual al de la exportación.

### Tests for User Story 1 ⚠️

> Escribir estas pruebas primero y confirmar que fallan antes de implementar (Principio IV).
> Las cuatro se agregan al mismo archivo existente, en orden — no son paralelizables entre sí.

- [X] T002 [US1] Prueba: el encabezado de `lecturas.registroMasivo.index` incluye la celda "Consumo" ubicada entre "Lectura Actual" y "Total" (orden en el HTML, no solo presencia) en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T003 [US1] Prueba: para una locación con lectura ya guardada del periodo, la respuesta incluye `<div id="consumo-fila-{id}">` con marcador inicial `—` y el atributo `data-consumo` de su celda de lectura igual a `consumo_calculado`, en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T004 [US1] Prueba: para una locación con lectura anterior pero sin lectura guardada del periodo, la respuesta incluye `<div id="consumo-fila-{id}">` con marcador inicial `—` y `data-lectura-anterior` igual al valor de la lectura anterior, en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.
- [X] T005 [US1] Prueba de regresión de alineación: una locación no alquilable y la fila de "Total general" conservan la misma cantidad de celdas que las filas de locaciones alquilables (una celda más que antes de esta feature), en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.

### Implementation for User Story 1

- [X] T006 [P] [US1] Agregar el quinto track de la columna "Consumo" a `$registro-masivo-columnas` (mismo ancho que "Total", `8rem`) y ajustar el `min-width` del contenedor en `resources/css/bootstrap.scss` (research.md Decisión 3).
- [X] T007 [P] [US1] Agregar la celda de encabezado "Consumo" (entre "Lectura Actual" y "Total") y la celda vacía correspondiente en la fila de total general, en `resources/views/lecturas/registro-masivo/index.blade.php`.
- [X] T008 [P] [US1] Agregar `<div id="consumo-fila-{{ $locacion->id }}" class="cifra fila-registro-masivo__consumo" aria-label="Consumo de {{ $locacion->nombre }}">—</div>` en la rama alquilable, y una celda vacía adicional en la rama no alquilable, de `resources/views/lecturas/registro-masivo/partials/fila-registro-masivo.blade.php` (contracts/columna-consumo-y-icono.md, Contrato 1).
- [X] T009 [US1] Extender `recalcularTotales()` en `resources/js/registro-masivo-lecturas.js` para escribir en `#consumo-fila-{locacionId}` el valor de `consumo` (2 decimales) o `—` si es `null`, reutilizando la misma variable `consumo` ya calculada para "Total" — sin una segunda función de cómputo (research.md Decisión 1). Depende de T008 (el id de la celda debe existir).

**Checkpoint**: la columna "Consumo" funciona de forma independiente y es verificable con
`quickstart.md` Escenarios 1-3.

---

## Phase 4: User Story 2 - Ver el ícono de lectura completada alineado con el valor (Priority: P2)

**Goal**: mover el ícono verde de "lectura completada" para que aparezca a la izquierda del valor,
sin alterar su comportamiento de edición ni su accesibilidad (contracts/columna-consumo-y-icono.md,
Contrato 2).

**Independent Test**: ver una fila con lectura ya guardada y confirmar que el ícono aparece antes
que el valor en el marcado; hacer clic para editar y confirmar que el comportamiento no cambió.

### Tests for User Story 2 ⚠️

- [X] T010 [US2] Prueba: para una locación con lectura ya guardada y en modo de solo lectura, el `<button>` del ícono `bi-check-circle-fill` precede al `<span class="cifra">` del valor en el HTML de la respuesta (orden de nodos), en `tests/Feature/RegistroMasivoLecturasControllerTest.php`.

### Implementation for User Story 2

- [X] T011 [US2] En `resources/views/lecturas/registro-masivo/partials/campo-lectura-registro-masivo.blade.php`, invertir el orden del `<button>` (ícono) y el `<span class="cifra">` (valor) dentro de la rama `$lecturaDelPeriodo !== null && ! $modoEdicion`, y cambiar la clase de margen del botón de `ms-2` a `me-2` (research.md Decisión 4). No modificar `aria-label`, `title` ni `hx-get`.

**Checkpoint**: ambas historias funcionan de forma independiente y en conjunto — `quickstart.md`
Escenario 4.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T012 [P] Correr `php artisan test --filter=RegistroMasivoLecturasControllerTest` (binario Herd) y confirmar que las pruebas de specs/015/016 más las nuevas T002-T005/T010 pasan en verde.
- [X] T013 Revisión de diseño con el skill `impeccable` (`/impeccable polish` o `audit`) sobre los cuatro archivos modificados (`index.blade.php`, `fila-registro-masivo.blade.php`, `campo-lectura-registro-masivo.blade.php`, `bootstrap.scss`), documentando el resultado en `DESIGN.md` si corresponde (Principio VI, obligatorio antes de cerrar la tarea).
- [X] T014 Ejecutar manualmente los 4 escenarios de `specs/017-columna-consumo-lecturas/quickstart.md` en el navegador — en particular el Escenario 2 (recálculo en vivo, FR-003), no verificable por Pest.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: vacía — no bloquea nada.
- **User Story 1 (Phase 3)**: puede empezar apenas termina T001. Sin dependencia de US2.
- **User Story 2 (Phase 4)**: puede empezar apenas termina T001, en paralelo con US1 si hay más de
  una persona — no depende de ningún archivo que toque US1.
- **Polish (Phase 5)**: depende de que ambas historias que se vayan a entregar estén completas.

### Dentro de cada historia

- Las pruebas (T002-T005, T010) se escriben antes que su implementación correspondiente y deben
  fallar primero (Principio IV).
- T009 (JS) depende de T008 (el id `consumo-fila-{locacionId}` debe existir en el HTML).
- T006, T007 y T008 son paralelizables entre sí (archivos distintos, sin dependencia de código
  entre ellos para poder editarlos).

### Parallel Opportunities

- T006, T007, T008 (US1) en paralelo.
- US1 completa (Phase 3) y US2 completa (Phase 4) son independientes entre sí y paralelizables con
  más de una persona trabajando.

---

## Parallel Example: User Story 1

```bash
# Tras T001, lanzar en paralelo (archivos distintos):
Task: "Agregar 5to track a $registro-masivo-columnas en resources/css/bootstrap.scss"
Task: "Agregar celda de encabezado 'Consumo' en resources/views/lecturas/registro-masivo/index.blade.php"
Task: "Agregar celda #consumo-fila-{id} en resources/views/lecturas/registro-masivo/partials/fila-registro-masivo.blade.php"
# T009 (JS) se hace después, una vez que T008 definió el id de la celda.
```

---

## Implementation Strategy

### MVP First (User Story 1 solamente)

1. T001 (Setup).
2. Phase 2 no tiene tareas — pasar directo a Phase 3.
3. Completar Phase 3 (US1): la columna "Consumo" ya entrega el valor central pedido por el usuario.
4. **Parar y validar**: correr T012 y el Escenario 1-3 de `quickstart.md` de forma independiente.
5. La US1 sola ya es un incremento entregable — el reacomodo del ícono (US2) es una mejora visual
   aparte que no depende de ella.

### Incremental Delivery

1. Setup → Foundational (vacía) → listo para empezar.
2. Agregar US1 (columna "Consumo") → validar independientemente → esa es la entrega mínima (MVP).
3. Agregar US2 (ícono alineado) → validar independientemente → entrega completa de la feature.
4. Phase 5 (Polish: pruebas completas, revisión `impeccable`, validación manual) cierra la feature.

---

## Notes

- [P] = archivos distintos, sin dependencia de código entre las tareas.
- [US1]/[US2] = trazabilidad a la historia de usuario correspondiente de `spec.md`.
- Ningún archivo backend (controlador, servicio, ruta, migración) se toca en esta feature — todo el
  cambio vive en `resources/css/`, `resources/views/lecturas/registro-masivo/` y
  `resources/js/registro-masivo-lecturas.js` (plan.md, Project Structure).
- Confirmar que las pruebas fallan antes de implementar cada tarea de implementación asociada.
