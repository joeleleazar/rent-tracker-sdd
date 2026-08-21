---

description: "Task list template for feature implementation"
---

# Tasks: Estado de Recibos y Envío por WhatsApp o Impresión

**Input**: Design documents from `/specs/007-estado-envio-recibo/`

**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: Incluidas — el Principio IV de la Constitución exige pruebas automatizadas exhaustivas para toda funcionalidad.

**Organization**: Las tareas están agrupadas por historia de usuario para permitir implementación y prueba independiente de cada una.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias pendientes)
- **[Story]**: Historia de usuario a la que pertenece la tarea (US1, US2, US3)
- Se incluyen rutas de archivo exactas en cada descripción

## Path Conventions

Aplicación Laravel monolítica única — rutas relativas a la raíz del repositorio: `app/`, `database/`, `resources/`, `routes/`, `tests/`, según `plan.md` → Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Ya inicializado por `specs/002-gestion-contratos` (Laravel, PostgreSQL, Pest, Vite)

- [X] T001 Proyecto ya inicializado — sin tareas de setup adicionales para esta feature
- [X] T002 Agregar dependencia `html2canvas` a `package.json` (`devDependencies` o `dependencies`, empaquetada por Vite) y ejecutar `npm install`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Esquema de base de datos y Service base que todas las historias de usuario de esta feature requieren

**⚠️ CRITICAL**: Ninguna historia de usuario puede comenzar hasta completar esta fase

- [X] T003 Crear migración de alteración de `recibos` (agrega `estado` enum `pendiente`/`pagado`/`anulado` default `pendiente`, `fecha_pago` timestamp nullable, `fecha_anulacion` timestamp nullable) en `database/migrations/` (depende de la migración de `recibos` de `specs/004`, alterada en `005`)
- [X] T004 Agregar `estado`/`fecha_pago`/`fecha_anulacion` a `$fillable`/`casts()` (`datetime` para las fechas) de `Recibo` en `app/Models/Recibo.php` (depende de T003)
- [X] T005 Implementar `ServicioCambioEstadoRecibo` (`cambiar()`: exige confirmación hacia/desde `anulado`, limpia/asigna `fecha_pago`/`fecha_anulacion`, `DB::transaction`) en `app/Services/ServicioCambioEstadoRecibo.php` (depende de T004)

**Checkpoint**: Fundamento listo — las historias de usuario pueden comenzar

---

## Phase 3: User Story 1 - Marcar el Estado de Pago de un Recibo (Priority: P1) 🎯 MVP

**Goal**: Marcar cada recibo como pendiente, pagado o anulado, con confirmación explícita hacia/desde anulado.

**Independent Test**: Emitir un recibo (inicia en "Pendiente"), marcarlo como "Pagado", luego "Anular" con confirmación, y "Revertir Anulación" con confirmación, comprobando el estado y las fechas asociadas en cada paso.

### Tests for User Story 1 ⚠️

- [X] T006 [P] [US1] Prueba unitaria de `Recibo`/`ServicioCambioEstadoRecibo` (transiciones libres, limpieza de fechas, exigencia de confirmación hacia/desde anulado) en `tests/Unit/ReciboTest.php` y `tests/Unit/ServicioCambioEstadoReciboTest.php`
- [X] T007 [P] [US1] Prueba de feature de `ReciboController@actualizarEstado` (happy path pendiente→pagado, 422 al anular sin confirmar, éxito al anular confirmando, reversión con confirmación) en `tests/Feature/ReciboControllerTest.php`

### Implementation for User Story 1

- [X] T008 [P] [US1] Crear `SolicitudActualizarEstadoRecibo` (`nuevo_estado` in:pendiente,pagado,anulado; `confirmado` boolean) en `app/Http/Requests/SolicitudActualizarEstadoRecibo.php` (depende de T004)
- [X] T009 [US1] Implementar `ReciboController@actualizarEstado` (usa `ServicioCambioEstadoRecibo`, traduce excepción de confirmación faltante a 422) en `app/Http/Controllers/ReciboController.php` (depende de T005, T008)
- [X] T010 [US1] Registrar ruta `PATCH /recibos/{recibo}/estado` en `routes/web.php` (depende de T009)
- [X] T011 [US1] Agregar botones "Marcar como Pagado"/"Anular Recibo"/"Revertir Anulación" (≥48x48px) y modal de confirmación Senior-First a `resources/views/locaciones/recibos/show.blade.php` (de `specs/004`/`005`) (depende de T010)

**Checkpoint**: User Story 1 funcional y comprobable de forma independiente (MVP)

---

## Phase 4: User Story 2 - Envío del Recibo como Imagen por WhatsApp (Priority: P2)

**Goal**: Generar una imagen legible del recibo y habilitar su envío por WhatsApp mediante el mecanismo nativo de compartir del navegador.

**Independent Test**: Abrir el comprobante de un recibo emitido, presionar "Enviar por WhatsApp" y comprobar que se genere una imagen legible y se habilite el selector nativo de compartir.

### Tests for User Story 2 ⚠️

- [X] T012 [P] [US2] Prueba de feature de `ReciboController@comprobante` (HTML incluye todos los conceptos/montos/periodo/estado; marca ANULADO presente si `estado = anulado`) en `tests/Feature/ReciboControllerTest.php`

### Implementation for User Story 2

- [X] T013 [US2] Implementar `ReciboController@comprobante` en `app/Http/Controllers/ReciboController.php` (depende de T004)
- [X] T014 [US2] Registrar ruta `GET /recibos/{recibo}/comprobante` en `routes/web.php` (depende de T013)
- [X] T015 [US2] Crear vista `locaciones/recibos/comprobante.blade.php` (conceptos, montos, periodo, estado, marca "ANULADO" condicional de alto contraste) en `resources/views/locaciones/recibos/comprobante.blade.php` (depende de T014)
- [X] T016 [US2] Crear `resources/js/recibo-comprobante.js` (captura `html2canvas`, `canvas.toBlob`, `navigator.share({ files })`, fallback de descarga si falla o no está disponible) e importarlo en la vista de comprobante (depende de T002, T015)
- [X] T017 [US2] Agregar botón "Enviar por WhatsApp" (≥48x48px) e indicador de éxito persistente a `resources/views/locaciones/recibos/comprobante.blade.php` (depende de T016)

**Checkpoint**: User Story 1 y 2 funcionan de forma independiente

---

## Phase 5: User Story 3 - Impresión del Recibo (Priority: P2)

**Goal**: Imprimir un recibo ya emitido con una vista de impresión legible.

**Independent Test**: Abrir el comprobante de un recibo emitido, presionar "Imprimir Recibo" y comprobar que se muestre una vista de impresión clara con todos los datos.

### Tests for User Story 3 ⚠️

- [X] T018 [P] [US3] Prueba de feature confirmando que la vista de comprobante incluye la hoja de estilos `@media print` y oculta controles no imprimibles en `tests/Feature/ReciboControllerTest.php` (depende de T012)

### Implementation for User Story 3

- [X] T019 [US3] Agregar hoja de estilos `@media print` (oculta botones/navegación, tipografía ≥18px para papel) a `resources/views/locaciones/recibos/comprobante.blade.php` (depende de T015)
- [X] T020 [US3] Agregar botón "Imprimir Recibo" (≥48x48px, invoca `window.print()`) a `resources/views/locaciones/recibos/comprobante.blade.php` (depende de T019)

**Checkpoint**: Las 3 historias de usuario funcionan de forma independiente

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Mejoras que afectan a todas las historias de usuario

- [X] T021 [P] Auditoría de accesibilidad (contraste, tipografía ≥18px, botones ≥48x48px) en `resources/views/locaciones/recibos/comprobante.blade.php` y `show.blade.php`
- [X] T022 [P] Revisión de seguridad: CSRF en `actualizarEstado`, verificación de que ningún dato de contacto/WhatsApp se envíe o almacene en el servidor
- [X] T023 Ejecutar la validación completa de `quickstart.md` (Escenarios 1 a 5) de extremo a extremo
- [X] T024 [P] Ejecutar `php artisan test --filter=Recibo` y confirmar que toda la suite pasa (incluidas las pruebas heredadas de `specs/004`/`005`)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: Ya completado salvo T002 (dependencia npm nueva)
- **Foundational (Phase 2)**: BLOQUEA todas las historias de usuario
- **User Stories (Phase 3-5)**: Todas dependen de Foundational; US2 y US3 comparten la misma vista de comprobante (`comprobante.blade.php`) y pueden coordinarse en paralelo sobre ese archivo
- **Polish (Phase 6)**: Depende de que las historias deseadas estén completas

### User Story Dependencies

- **User Story 1 (P1)**: Puede iniciar tras Foundational — sin dependencias de otras historias
- **User Story 2 (P2)**: Puede iniciar tras Foundational — introduce la vista de comprobante que también usará US3
- **User Story 3 (P2)**: Reutiliza la vista de comprobante creada en US2 (T015); si se implementa antes, puede crear su propia versión mínima de la vista y fusionarla luego

### Within Each User Story

- Las pruebas se escriben antes de la implementación y deben fallar inicialmente
- Migración/modelo antes que Service; Service antes que Form Request/controlador; controlador antes que rutas; rutas antes que vistas

### Parallel Opportunities

- T002 y T003 de Setup/Foundational son independientes entre sí
- Una vez completado Foundational, US1 puede avanzar en paralelo con US2/US3 (archivos distintos: `show.blade.php` vs. `comprobante.blade.php`)

---

## Parallel Example: User Story 1

```bash
Task: "Prueba unitaria de ServicioCambioEstadoRecibo en tests/Unit/ServicioCambioEstadoReciboTest.php"
Task: "Prueba de feature de ReciboController@actualizarEstado en tests/Feature/ReciboControllerTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Completar Phase 2: Foundational (CRÍTICO)
2. Completar Phase 3: User Story 1
3. **DETENERSE Y VALIDAR**: Escenario 1 de `quickstart.md`
4. Desplegar/demostrar si está listo

### Incremental Delivery

1. Foundational → base lista
2. User Story 1 → probar → demo (MVP)
3. User Story 2 → probar → demo
4. User Story 3 → probar → demo

---

## Notes

- **Hallazgo no anticipado por `research.md` (importante para specs futuras que capturen HTML como imagen)**: `html2canvas` 1.4.x clona el **documento completo** al capturar cualquier elemento y falla con `"Attempting to parse an unsupported color function oklch"` si CUALQUIER elemento de la página (no solo el capturado) tiene un color resuelto vía `oklch()` — que es exactamente lo que genera Tailwind v4 por defecto para todas sus clases de color. Esto significa que `resources/views/locaciones/recibos/comprobante.blade.php` **no puede** cargar `resources/css/app.css` (Tailwind) en absoluto, ni siquiera fuera del `#comprobante-recibo` capturado. Se resolvió reescribiendo esta única vista con CSS propio (colores hexadecimales, sin Tailwind), manteniendo los mismos criterios Senior-First (tipografía ≥18px, alto contraste, controles ≥48x48px) mediante clases `.btn`/`.btn-primario`/`.btn-secundario` locales a esta vista. El resto del proyecto sigue usando Tailwind sin cambios.
- `resources/js/recibo-comprobante.js` se agregó como entrada adicional de Vite (`vite.config.js`) y se importa solo desde `comprobante.blade.php`, no desde `app.js` global.
- El botón "Marcar como Pendiente" (revertir pagado→pendiente) se agregó en `show.blade.php` además de los botones nombrados explícitamente en la especificación, ya que FR-005 exige transiciones libres en cualquier momento; no requiere confirmación por no involucrar "anulado".
- [P] = archivos distintos, sin dependencias pendientes
- Verificar que las pruebas fallan antes de implementar
- Hacer commit tras cada tarea o grupo lógico de tareas
- Evitar: tareas vagas, conflictos de archivo simultáneos, dependencias que rompan la independencia entre historias
