---

description: "Task list for 016-correccion-registro-masivo-lecturas"
---

# Tasks: Corrección de Lectura Previa y Autoguardado en Registro Masivo

**Input**: Design documents from `/specs/016-correccion-registro-masivo-lecturas/`

**Prerequisites**: plan.md, spec.md, research.md, contracts/lectura-anterior-y-autoguardado.md, quickstart.md

**Nota de proceso (2026-08-25)**: este `tasks.md` se escribe retroactivamente, mucho después de que esta
spec llegara solo hasta `/speckit-plan`. Nunca tuvo tareas propias ni implementación bajo el nombre `016` —
ver "Nota de Estado" en `spec.md` para el detalle completo de qué pasó con cada historia. Este archivo existe
para que la spec deje de verse como una promesa abandonada sin resolución documentada, no porque haya trabajo
de implementación pendiente por hacer aquí.

## Historia 1 — Lectura del periodo anterior incorrecta al cambiar de periodo

- [X] T001 [US1] Diagnosticar la causa raíz (protocolo de reproducción de `research.md`/`quickstart.md`) — **hecho bajo `specs/020-correccion-exportar-periodo-icono`**, no bajo esta spec: el botón "Cambiar Periodo" no declaraba `type="submit"` (el componente `<x-secondary-button>` por defecto es `type="button"`), por lo que el formulario nunca se reenviaba con el periodo nuevo.
- [X] T002 [US1] Corregir el botón "Cambiar Periodo" para declarar `type="submit"` explícitamente — implementado en `specs/020-correccion-exportar-periodo-icono/tasks.md` (su User Story 1), no en esta spec.
- [X] T003 [US1] Verificar en navegador que cambiar de periodo actualiza correctamente la columna "Lectura Periodo Anterior" — verificado como parte del cierre de specs/020.

**Estado**: Resuelta, bajo specs/020. Esta spec no requiere ninguna acción adicional sobre esta historia.

## Historia 2 — Autoguardado cada 120s no confiable

- [X] T004 [US2] Revisar el código de producción (`index.blade.php`, `hx-trigger="every 120s"`, `hx-include="#formulario-registro-masivo"`) contra lo especificado en specs/015/research.md — hecho como parte de esta misma spec (`research.md`, Hallazgo H2): **no se encontró ninguna discrepancia real**, el código ya coincidía con lo documentado.
- [ ] T005 [US2] Agregar la prueba de "contrato HTML" propuesta en Hallazgo H2 (Pest, sin navegador: falla si `index.blade.php` deja de emitir `hx-trigger="every 120s"` o `hx-include="#formulario-registro-masivo"` exactamente) — **pendiente, sin implementar bajo ninguna spec**. No es un defecto de comportamiento confirmado (T004 no encontró ninguno), es un hueco de cobertura de test: hoy nada impide que una edición futura rompa ese cableado sin que ningún test lo note.

**Estado**: Sin defecto de comportamiento confirmado. T005 queda como mejora de cobertura de test
pendiente, sin urgencia (no bloquea ningún flujo actual) — retomar solo si se repite un defecto similar en
esta pantalla o como parte de un futuro endurecimiento de tests del módulo de registro masivo.

---

## Notas

- Esta spec no requiere una fase de implementación propia adicional: Historia 1 quedó completamente resuelta
  (bajo specs/020) y Historia 2 no tiene defecto de comportamiento pendiente, solo una tarea de test opcional
  (T005) sin implementar.
- Si en el futuro se decide implementar T005, hacerlo como una tarea nueva bajo una spec que la referencie
  explícitamente (o retomando esta), no silenciosamente sin dejar rastro — el objetivo de esta nota es
  justamente evitar que vuelva a pasar lo que motivó specs/016 en primer lugar (un cableado HTML sin ningún
  test que lo proteja).
