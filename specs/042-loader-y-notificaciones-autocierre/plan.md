# Implementation Plan: Loader de Carga de Página y Notificaciones de Respuesta con Autocierre

**Branch**: `042-loader-y-notificaciones-autocierre` | **Date**: 2026-08-30 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/042-loader-y-notificaciones-autocierre/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Dos ajustes de presentación en el cliente, sin tocar rutas, controladores ni datos:

1. **Notificaciones de respuesta efímeras.** El componente Blade `x-mensaje-alerta` (usado en 23 vistas) pasa
   a ser un `alert` de Bootstrap *dismissible* con transición `fade`. Una capa JS le pone un temporizador de
   8 s que se detiene mientras el puntero o el foco de teclado están sobre la notificación y se reinicia a la
   duración completa al salir. Se aplica por igual a las notificaciones de éxito y de error (decisión Q1).
   Sin JavaScript, la notificación se comporta como hoy (persistente), cumpliendo la degradación elegante.

2. **Barra de progreso de navegación.** Una barra fina fija en el borde superior de la ventana (decisión Q3),
   construida con `progress` / `progress-bar` de Bootstrap y los tokens de color del proyecto, que aparece
   cuando una **navegación** boosteada por `hx-boost` (petición GET) tarda más que un umbral anti-parpadeo de
   ~150 ms y se retira al completarse, fallar o abortarse la navegación. Los **envíos de formulario** (POST)
   no la disparan: conservan la retroalimentación de botón «Guardando…» que ya implementa
   `resources/js/htmx.js` (decisión Q2). La primera carga dura de página usa el indicador nativo del
   navegador, sin barra propia.

La lógica JS se injerta en los dos archivos que el layout ya carga —`resources/js/bootstrap.js`
(autocierre, mismo patrón de escaneo `DOMContentLoaded` + `htmx:afterSettle` que el autoabrir de modales) y
`resources/js/htmx.js` (barra, misma capa que ya escucha el ciclo de vida de htmx)— para no tocar
`vite.config.js` ni el arreglo `@vite([...])` del layout.

**Excepción constitucional confirmada**: la Constitución (sección "Mensajes de Estado y Feedback") y
`DESIGN.md` (sección "Mensaje / Alert") exigen hoy notificaciones **persistentes, sin autocierre**. El
usuario pidió explícitamente el autocierre; esta feature incluye la enmienda constitucional (MINOR) y la
actualización de `DESIGN.md` como entregables, de forma análoga a la excepción documentada de `specs/041`.

## Technical Context

**Language/Version**: PHP 8.3 / Laravel 13.x en el servidor (sin cambios); JavaScript ES módulos + SCSS en el
cliente, compilados por Vite 8.

**Primary Dependencies**: Ninguna nueva. Bootstrap 5.3.3 (`Alert`, `progress`/`progress-bar`, clases `fade`),
Bootstrap Icons 1.11, htmx 2.0.10 (`hx-boost` y sus eventos de ciclo de vida). Prohibido Alpine.js y
cualquier librería de "top loading bar" de terceros (FR-015, Constitución Principio VI).

**Storage**: N/A — la feature no persiste nada. Los mensajes flash de sesión existentes no cambian de forma
ni de disparador.

**Testing**: Pest 4.7 (`pest-plugin-laravel`). La suite actual (433 pruebas) debe seguir en verde
(FR-017 / SC-007). Se agrega una prueba Feature ligera que verifica el contrato del componente
`x-mensaje-alerta` renderizado (rol `alert`, botón de cierre, clases `fade show`). El comportamiento
temporizado y la pausa por hover se validan manualmente en el navegador vía `quickstart.md` — el proyecto no
tiene configurado el navegador de Pest 4 y esta feature no lo introduce (mismo criterio de verificación
manual que `specs/041`).

**Target Platform**: Aplicación web Laravel servida por Herd; navegadores de escritorio y móviles modernos.

**Project Type**: Aplicación web monolítica existente — solo capa de presentación (Blade + JS + SCSS).

**Performance Goals**:
- La barra aparece dentro de 1 s de iniciada una navegación lenta (SC-004) y nunca queda visible tras
  finalizar (SC-005).
- Umbral anti-parpadeo ~150 ms: navegaciones más rápidas no muestran la barra de forma perceptible (FR-011).
- El temporizador de autocierre no corre mientras hay hover/foco (FR-002); se reinicia entero al salir
  (FR-003).

**Constraints**:
- **Degradación elegante**: sin JS, la notificación se muestra persistente y la barra no aparece; ninguna
  vista se rompe (FR-007, coherente con `specs/011`).
- **`prefers-reduced-motion`**: la transición de cierre del alert y la animación de la barra degradan a un
  cambio no animado (FR-005, FR-014).
- **Accesibilidad**: el alert conserva `role="alert"` y no se retira del DOM antes de anunciarse; la barra no
  captura el foco ni bloquea la interacción (FR-014).
- **Distinción GET vs POST bajo `hx-boost`**: la barra se activa solo cuando el disparador es una navegación
  (petición GET / elemento `<a>`), no un envío de formulario (`<form>` / verbo POST). Ver research.md §2.
- **No tocar el arreglo `@vite`** del layout ni `vite.config.js`: el código nuevo se importa desde
  `bootstrap.js` y `htmx.js`, que el layout ya carga.
- Toda vista/componente modificado pasa por la revisión con el skill `impeccable` antes de cerrarse
  (Constitución, Principio VI).

**Scale/Scope**: 1 componente Blade modificado (`mensaje-alerta.blade.php`); 1 markup nuevo en el layout
(`app-bootstrap.blade.php`); 2 archivos JS modificados (`bootstrap.js`, `htmx.js`); 1 archivo SCSS modificado
(`bootstrap.scss`); 1 enmienda de `constitution.md`; 1 actualización de `DESIGN.md`; 1 archivo de prueba
Feature nuevo. 0 migraciones, 0 rutas, 0 controladores, 0 modelos.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)**: N/A al servidor — no hay migraciones,
  modelos, consultas ni lógica de negocio nueva. Cumple por no aplicar.
- **II. Nomenclatura y Código Estrictamente en Español**: Cumple — identificadores y comentarios nuevos en
  español: clases CSS `.barra-carga-navegacion`, funciones `iniciarAutocierreNotificaciones()`,
  `mostrarBarraCarga()` / `ocultarBarraCarga()`, constantes `MS_AUTOCIERRE`, `MS_UMBRAL_ANTIPARPADEO`.
  `role="alert"`, `data-bs-dismiss` y clases de Bootstrap se conservan tal cual (API del framework).
- **III. Diseño Moderno e Intuitivo**: Aplica — `alert-success` / `alert-danger` con ícono de apoyo se
  mantienen; se añade el `btn-close` estándar de Bootstrap. La barra usa el color `$primary` de la paleta del
  proyecto (contraste ya verificado). Componentes interactivos modernos permitidos por el principio.
- **IV. Pruebas Automatizadas Exhaustivas (Modelos y Controladores)**: Aplica parcialmente — no hay modelo ni
  controlador nuevo que cubrir. Se garantiza que las 433 pruebas actuales siguen verdes y se agrega una
  prueba Feature del contrato del componente. El comportamiento temporizado se valida en el navegador
  (quickstart), como en `specs/041`.
- **V. Integridad de Datos y Seguridad Transaccional**: N/A — sin transacciones, sin datos, sin cambios de
  autorización. La barra no expone información. Cumple por no aplicar.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple con una excepción documental — se usa
  `Alert` + `progress`/`progress-bar` + clases `fade` de Bootstrap y `bi-*`, sin Alpine ni librerías nuevas;
  `hx-boost` sigue siendo la capa asíncrona. Revisión con `impeccable` obligatoria y actualización de
  `DESIGN.md`.
- **Restricciones Técnicas y Estándares de Interfaz → "Mensajes de Estado y Feedback"**: ⚠️ **DESVIACIÓN
  CONFIRMADA**. La regla vigente exige "mensajes persistentes … el usuario la cierra actuando, no por un
  temporizador". El usuario pidió explícitamente el autocierre a 8 s con pausa por hover. Se resuelve
  enmendando esa sección de la Constitución (MINOR, 2.1.1 → 2.2.0) y `DESIGN.md` como parte de esta feature,
  no como violación silenciosa. Ver Complexity Tracking.

**Resultado del gate**: PASA con una desviación confirmada y con remediación incluida en el alcance (enmienda
constitucional + actualización de `DESIGN.md`). No se introducen entidades, dependencias ni patrones nuevos.

**Re-evaluación post-diseño (Phase 1)**: PASA sin cambios. `research.md`, `data-model.md`,
`contracts/comportamiento-ui.md` y `quickstart.md` no añadieron dependencias ni entidades; confirmaron que el
alcance es 1 componente + 1 layout + 2 JS + 1 SCSS + enmienda + doc + 1 test. La única entrada de Complexity
Tracking sigue siendo la desviación de "mensajes persistentes".

## Project Structure

### Documentation (this feature)

```text
specs/042-loader-y-notificaciones-autocierre/
├── spec.md                       # Especificación (entrada)
├── plan.md                       # Este archivo (/speckit-plan)
├── research.md                   # Fase 0 — decisiones: temporizador/pausa, GET vs POST bajo hx-boost, barra Bootstrap, reduced-motion, enmienda
├── data-model.md                 # Fase 1 — sin entidades persistidas; máquinas de estado de la notificación y de la barra
├── contracts/
│   └── comportamiento-ui.md      # Fase 1 — contrato del componente x-mensaje-alerta y del ciclo de vida de la barra frente a los eventos htmx
├── quickstart.md                 # Fase 1 — guía de verificación manual + suite + build
└── checklists/
    └── requirements.md           # Checklist de calidad de la spec (ya completo)
```

### Source Code (repository root)

```text
resources/
├── css/
│   └── bootstrap.scss                       # + .barra-carga-navegacion (posición fija, alto ~3px, color $primary);
│                                            #   + regla @media (prefers-reduced-motion) que anula la animación de la barra
│                                            #     y la transición .fade del alert
├── js/
│   ├── bootstrap.js                         # + iniciarAutocierreNotificaciones(): escanea .alert.alert-dismissible en
│   │                                        #   DOMContentLoaded y htmx:afterSettle; temporizador 8 s con pausa por
│   │                                        #   mouseenter/focusin y reinicio por mouseleave/focusout; cierre vía
│   │                                        #   bootstrap.Alert(...).close()
│   └── htmx.js                              # + barra de navegación: htmx:beforeRequest (solo verbo GET / disparador <a>) →
│                                            #   arma setTimeout(150ms) → muestra; htmx:beforeSwap/afterRequest/
│                                            #   sendError/responseError/htmx:abort → limpia timeout y oculta
└── views/
    └── components/
        ├── mensaje-alerta.blade.php         # alert-success/alert-danger  →  + "alert-dismissible fade show";
        │                                    #   + <button class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar">;
        │                                    #   comentario del encabezado reescrito (ya no "persistente")
        └── layouts/
            └── app-bootstrap.blade.php      # + markup de la barra al inicio de <body> (contenedor fijo + progress-bar),
                                             #   oculto por defecto

.specify/memory/constitution.md              # ENMIENDA: sección "Mensajes de Estado y Feedback" — de "persistentes"
                                             #   a "efímeras con autocierre ≤8 s y pausa por hover/foco; el error de
                                             #   validación de formulario sigue disponible en el formulario mismo".
                                             #   Sync Impact Report + bump 2.1.1 → 2.2.0
DESIGN.md                                    # sección "Mensaje / Alert": quitar "Persistent (no auto-dismiss)";
                                             # + subsección de la barra de carga de navegación (componente bespoke,
                                             #   precedente: "Estado Vacío")

tests/
└── Feature/
    └── ComponenteMensajeAlertaTest.php      # el componente renderiza role="alert", el btn-close y "fade show";
                                             # una vista real que muestra flash sigue conteniendo su texto (no-regresión)
```

**Structure Decision**: No hay cambio de estructura de proyecto. Todo el trabajo vive en `resources/`
(presentación) más la enmienda de `.specify/memory/constitution.md`, la actualización de `DESIGN.md` y un
archivo de prueba en `tests/Feature/`. Se reutilizan los dos archivos JS que el layout ya carga en vez de
crear entradas nuevas de Vite.

## Complexity Tracking

| Violación | Por qué se necesita | Alternativa más simple y por qué se descartó |
|-----------|---------------------|----------------------------------------------|
| Desviación de la regla constitucional "Mensajes de Estado y Feedback" (persistentes → efímeras con autocierre) | El usuario pidió explícitamente que las notificaciones no se queden siempre en pantalla y se autocierren a los 8 s con pausa por hover. Es el objetivo central de la User Story 1. | Mantener la regla y no hacer la feature: descartado, contradice el pedido. Aplicar el autocierre sin enmendar la Constitución/`DESIGN.md`: descartado, dejaría una contradicción normativa silenciosa (el mismo error que `specs/041` evitó consultando y documentando). La remediación elegida —enmienda MINOR acotada a esa sección, con Sync Impact Report— mantiene la trazabilidad. |
