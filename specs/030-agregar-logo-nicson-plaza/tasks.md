---

description: "Task list for 030-agregar-logo-nicson-plaza"
---

# Tasks: Incorporar el Logo de Nicson Plaza a la Interfaz

**Input**: Design documents from `/specs/030-agregar-logo-nicson-plaza/`

**Prerequisites**: plan.md, spec.md, research.md, quickstart.md

**Tests**: incluidas — Principio IV de la constitución.

**Organization**: 4 historias de usuario en orden de prioridad (US1 P1, US2 P1, US3 P2, US4 P3), más una
fase Foundational mínima (copiar el archivo de imagen, compartido por las 4 historias).

**Nota de entorno**: usar el binario de PHP de Herd (`C:\Users\joel5\.config\herd\bin\php.bat`) para
`artisan`/`pest` en esta máquina. Sin herramientas de conversión de imágenes disponibles (research.md
Decisión 1) — ninguna tarea debe depender de `magick`/`convert`/`cwebp`/`ffmpeg`.

## Phase 1: Setup

- [X] T001 Confirmar la línea base: correr `php artisan test` completo (binario Herd) y verificar que todo sigue en verde antes de tocar ningún archivo.

## Phase 2: Foundational — el archivo de logo

**Propósito**: el archivo de imagen que las 4 historias referencian. Bloquea a las 4.

- [X] T002 Copiar `c:\Users\joel5\Downloads\Gemini_Generated_Image_dfdhedfdhedfdhed.jpg` a `public/images/logo-nicson-plaza.png` (research.md Decisión 1 — sin recortar, redimensionar ni convertir de formato).

**Checkpoint**: el archivo existe en `public/images/logo-nicson-plaza.png` — las 4 historias pueden empezar.

---

## Phase 3: User Story 1 - Reconocer la marca al iniciar sesión (Priority: P1)

**Goal**: la pantalla de login muestra el logo de Nicson Plaza en vez del ícono genérico de plantilla.

**Independent Test**: abrir `/login` sin autenticar y verificar que el logo aparece donde antes estaba el
ícono genérico (quickstart.md Escenario 1).

### Tests for User Story 1 ⚠️

- [X] T003 [P] [US1] Feature test (nuevo `tests/Feature/LogoInstitucionalTest.php`): `GET /login` responde con una etiqueta `<img>` cuyo `src` apunta a `asset('images/logo-nicson-plaza.png')`.

### Implementation for User Story 1

- [X] T004 [US1] En `resources/views/components/layouts/guest-bootstrap.blade.php`, reemplazar `<x-application-logo>` por un `<img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" ...>` con ancho/alto acotados (ej. ~5rem, mismo tamaño que el ícono que reemplaza), manteniendo el `<a href="/">` que lo envuelve.

**Checkpoint**: el login muestra el logo real.

---

## Phase 4: User Story 2 - Ver la marca en toda pantalla de trabajo (Priority: P1)

**Goal**: el encabezado de la barra de navegación (compartido por toda pantalla autenticada) muestra el
logo en vez del texto "Rent Tracker", legible sobre el fondo oscuro del sidebar.

**Independent Test**: iniciar sesión y visitar varias pantallas, verificando que el logo aparece siempre
en el mismo lugar del encabezado y sigue enlazando al inicio (quickstart.md Escenario 2).

### Tests for User Story 2 ⚠️

- [X] T005 [P] [US2] Extender `tests/Feature/LogoInstitucionalTest.php`: una pantalla autenticada cualquiera (ej. `GET /locaciones`, autenticado) responde con una etiqueta `<img>` cuyo `src` apunta a `asset('images/logo-nicson-plaza.png')`, envuelta en un `<a href="http://localhost/">` (o la URL base configurada).

### Implementation for User Story 2

- [X] T006 [US2] En `resources/views/components/layouts/app-bootstrap.blade.php`, dentro del `<a href="{{ url('/') }}">` que hoy muestra `{{ config('app.name', 'Rent Tracker') }}`, reemplazar ese texto por el `<img>` del logo envuelto en una tarjeta blanca pequeña de esquinas redondeadas (research.md Decisión 2 — mismo lenguaje visual que `card`, Principio VI), dimensionada para verse bien tanto en el sidebar vertical de escritorio como en la franja horizontal de pantallas angostas.

**Checkpoint**: el logo es visible en el 100% de las pantallas autenticadas, sin romper el layout responsive.

---

## Phase 5: User Story 3 - Ver la marca en los recibos que salen del sistema (Priority: P2)

**Goal**: el comprobante de recibo (documento imprimible/compartible) incluye el logo en su encabezado,
sin superponerse con la marca de "Anulado".

**Independent Test**: abrir el comprobante de un recibo y verificar que el logo aparece, tanto al imprimir
como al capturarlo como imagen para compartir (quickstart.md Escenario 3).

### Tests for User Story 3 ⚠️

- [X] T007 [P] [US3] Extender `tests/Feature/LogoInstitucionalTest.php`: `GET recibos.comprobante` de un recibo responde con una etiqueta `<img>` cuyo `src` apunta a `asset('images/logo-nicson-plaza.png')`.

### Implementation for User Story 3

- [X] T008 [US3] En `resources/views/locaciones/recibos/comprobante.blade.php`, agregar el `<img>` del logo en una esquina superior del encabezado del documento (no centrado — la marca de "Anulado" es una franja diagonal centrada sobre todo `#comprobante-recibo`, `position: absolute; inset: 0`, research.md / spec.md FR-005), con un tamaño pequeño acorde al resto del documento (ej. ~3rem de alto).

**Checkpoint**: el comprobante incluye el logo, verificado también en su versión de captura de imagen (mismo mecanismo que ya usa el resto del documento).

---

## Phase 6: User Story 4 - Reconocer la pestaña del navegador (Priority: P3)

**Goal**: el ícono de pestaña del navegador refleja el logo de Nicson Plaza.

**Independent Test**: abrir cualquier pantalla del sistema y verificar el ícono de la pestaña (quickstart.md Escenario 4).

### Tests for User Story 4 ⚠️

- [X] T009 [P] [US4] Extender `tests/Feature/LogoInstitucionalTest.php`: tanto una pantalla no autenticada (`/login`) como una autenticada responden con `<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-nicson-plaza.png') }}">` en el `<head>`.

### Implementation for User Story 4

- [X] T010 [US4] Agregar `<link rel="icon" type="image/jpeg" href="{{ asset('images/logo-nicson-plaza.png') }}">` al `<head>` de `resources/views/components/layouts/guest-bootstrap.blade.php` y de `resources/views/components/layouts/app-bootstrap.blade.php` (research.md Decisión 3 — sin tocar `public/favicon.ico`).

**Checkpoint**: la pestaña del navegador muestra el logo en cualquier pantalla.

---

## Phase 7: Polish & Cross-Cutting Concerns

- [X] T011 Eliminar `resources/views/components/application-logo.blade.php` — queda sin ningún llamador tras T004 (confirmar con una búsqueda de `application-logo` en `resources/views` antes de borrar).
- [X] T012 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos.
- [X] T013 [P] Revisión de diseño con el skill `impeccable` sobre las 3 vistas modificadas (`guest-bootstrap.blade.php`, `app-bootstrap.blade.php`, `comprobante.blade.php`) — Principio VI de la constitución.
- [X] T014 Validar manualmente los 4 escenarios de `specs/030-agregar-logo-nicson-plaza/quickstart.md` contra la base de datos de desarrollo real, en navegador.

### Corrección posterior — el usuario reemplazó el archivo por una versión transparente y no cuadrada

- [X] T015 Hallazgo posterior a la implementación (research.md, notas "Hallazgo posterior a la implementación" en Decisiones 1-3): el usuario reemplazó manualmente `public/images/logo-nicson-plaza.png` (ahora `.png` real, 1769×962, RGBA con transparencia — antes `.jpg` 2048×2048 opaco) y editó a mano las referencias `.jpg`→`.png`, dejando dos defectos: `<link rel="icon" type="image/jpeg">` apuntando a un `.png`, y el `<img>` en 3 vistas encogido por seguir usando una caja cuadrada fija con `object-fit: contain` (pensada para la proporción 1:1 del archivo anterior).
- [X] T016 Corregir `type="image/jpeg"` → `type="image/png"` en el `<link rel="icon">` de `resources/views/components/layouts/guest-bootstrap.blade.php` y `app-bootstrap.blade.php`, y sus asserts en `tests/Feature/LogoInstitucionalTest.php` (US4).
- [X] T017 Reemplazar el patrón de caja cuadrada + `object-fit: contain` por `height: Xrem; width: auto;` en las 3 vistas que muestran el `<img>` del logo: `app-bootstrap.blade.php` (sidebar, dentro de la tarjeta blanca), `guest-bootstrap.blade.php` (login, sin envoltorio) y `resources/views/locaciones/recibos/comprobante.blade.php` (`.logo-comprobante`).
- [X] T018 Correr la suite completa (`php artisan test`, binario Herd) y confirmar 0 fallos; volver a validar en navegador los 4 escenarios de `quickstart.md` contra los datos reales de `rent-tracker-sdd.test`.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: sin dependencias.
- **Foundational (Phase 2)**: depende de T001. Bloquea las 4 historias.
- **User Story 1 (Phase 3)**: depende de Foundational (T002). Independiente de US2/US3/US4.
- **User Story 2 (Phase 4)**: depende de Foundational (T002). Independiente de US1/US3/US4.
- **User Story 3 (Phase 5)**: depende de Foundational (T002). Independiente de US1/US2/US4.
- **User Story 4 (Phase 6)**: depende de Foundational (T002) y de que existan los dos layouts que va a tocar (ya existen, sin dependencia real de US1/US2 más allá de tocar los mismos 2 archivos — ver nota abajo).
- **Polish (Phase 7)**: depende de que las 4 historias estén completas.

### Dentro de cada fase

- Los tests marcados ⚠️ se escriben/actualizan antes que su implementación y deben fallar primero
  (Principio IV).
- US1 (T004) y US4 (T010) tocan el mismo archivo (`guest-bootstrap.blade.php`); US2 (T006) y US4 (T010)
  tocan el mismo archivo (`app-bootstrap.blade.php`) — no hay conflicto real porque cada una edita una
  sección distinta del `<head>`/`<body>`, pero si se trabajan en paralelo deben aplicarse como ediciones
  separadas sobre el mismo archivo, no simultáneas.

### Parallel Opportunities

- T003, T005, T007, T009 (tests de las 4 historias) en paralelo entre sí — mismo archivo de test pero
  bloques de `test()` independientes.
- US1 y US3 (archivos completamente distintos) en paralelo si hay más de un desarrollador.

---

## Implementation Strategy

### MVP First (User Story 1 + User Story 2)

1. Setup (T001) → Foundational (T002).
2. US1 (T003-T004) y US2 (T005-T006): las dos de mayor prioridad, cubren login y el 100% de las pantallas
   autenticadas.
3. **Parar y validar**: quickstart.md Escenarios 1-2.

### Incremental Delivery

1. Setup → Foundational → listo para las 4 historias.
2. US1 → validar → demo.
3. US2 → validar → demo (MVP visual completo del pedido original).
4. US3 → validar → demo.
5. US4 → validar → demo.
6. Polish (T011-T014) cierra la feature.

---

## Notes

- [P] = archivos distintos (o bloques de test independientes en el mismo archivo), sin dependencia de
  código entre las tareas.
- [US1]/[US2]/[US3]/[US4] = trazabilidad a las historias de usuario de `spec.md`.
- T011 (eliminar el componente de logo genérico ya sin uso) existe para no dejar código muerto, siguiendo
  el mismo criterio ya aplicado en features anteriores de este proyecto.
