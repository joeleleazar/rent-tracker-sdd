# Implementation Plan: Corrección de Exportación, Cambio de Periodo e Ícono de Edición en Registro Masivo

**Branch**: `020-correccion-exportar-periodo-icono` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/020-correccion-exportar-periodo-icono/spec.md`

## Summary

Tres defectos del registro masivo de lecturas (specs/015-019), cada uno reproducido y confirmado en
el navegador durante esta planificación (ver research.md):

1. **Exportar a Excel/PDF no descarga nada**: el layout raíz tiene `hx-boost="true"` (specs/011) y
   los botones de exportar son enlaces `<a href>` normales sin excepción — htmx intercepta el clic,
   hace un GET por AJAX, y trata la respuesta binaria (`.xlsx`/`.pdf`) como si fuera HTML para
   reemplazar la página, dejando la pestaña congelada en vez de descargar el archivo.
2. **Cambiar de periodo no actualiza nada**: el botón "Cambiar Periodo" (`<x-secondary-button>`) es
   `type="button"` por defecto — nunca fue `type="submit"` — así que el clic no envía el formulario
   GET. El selector de mes cambia visualmente, pero la página nunca vuelve a pedirse; el usuario ve
   los mismos datos del periodo anterior creyendo que está en el nuevo.
3. **El tooltip queda atascado al editar**: `registro-masivo-lecturas.js` crea tooltips de
   Bootstrap sobre el ícono de "completada" pero nunca los destruye antes de que htmx reemplace esa
   celda (`hx-swap="outerHTML"`) al entrar en modo edición — el tooltip flotante queda huérfano en
   pantalla. Además, el propio ícono de "completada" es hoy el disparador de la edición, lo cual
   generó el problema; se separa en dos controles distintos (FR-004/FR-005 de spec.md).

El enfoque técnico es puntual para cada defecto — sin rediseñar nada de lo ya construido en
specs/015-019 — y no requiere cambios de esquema ni de controladores.

## Technical Context

**Language/Version**: PHP 8.2+ (usar `C:\Users\joel5\.config\herd\bin\php.bat` para
`artisan`/`pest` en esta máquina, igual que specs/016-019).

**Primary Dependencies**: Ninguna nueva — Blade, htmx (`hx-boost`, atributos declarativos) y
Bootstrap 5.3 (JS de tooltips) ya existentes.

**Storage**: Sin cambios — ningún defecto de esta spec involucra datos ni esquema.

**Testing**: Pest (Feature) para lo verificable por HTTP (contrato HTML: atributos `hx-boost`,
`type`, orden/separación de controles). El comportamiento real de htmx/Bootstrap en el navegador
(descarga efectiva, ausencia de tooltip huérfano, refresco visual al cambiar de periodo) no es
verificable con Pest (sin Dusk/Playwright, misma limitación ya documentada en specs/016/017/019) —
se valida manualmente vía `quickstart.md`, con los tres defectos ya reproducidos una vez en esta
fase de planificación.

**Target Platform**: Misma pantalla `/lecturas/registro-masivo` ya existente.

**Project Type**: Aplicación web Laravel (Blade + htmx), monolito.

**Performance Goals**: Sin impacto — son correcciones de marcado/atributos, sin consultas nuevas.

**Constraints**: Ninguna corrección debe alterar el contenido de los archivos exportados (specs/015
FR-016), el cálculo de "lectura anterior" ya corregido en specs/016, ni el comportamiento de
guardar/cancelar de la edición en línea (specs/015 FR-005/FR-017) — los tres defectos son de
disparo/marcado, no de lógica de negocio.

**Scale/Scope**: Mismo alcance que specs/015-019 — la pantalla de registro masivo completa.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack)**: Sin cambios de stack ni SQL. PASA.
- **Principio II (Español)**: Sin nombres nuevos fuera de español. PASA.
- **Principio III (Diseño Moderno)**: El nuevo control de edición reutiliza `bi-pencil-square`, el
  ícono ya estandarizado para "editar" en toda la app (Principio VI) — no introduce un patrón de
  interacción nuevo. PASA.
- **Principio IV (Pruebas Exhaustivas)**: Se agregan pruebas de contrato HTML (atributos
  `hx-boost="false"`, `type="submit"`, separación de los dos controles) donde Pest puede verificar
  algo real; se documenta explícitamente qué queda fuera de su alcance. PASA.
- **Principio V (Integridad de Datos)**: No aplica — ningún dato cambia. PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: Se modifican vistas Blade
  (`index.blade.php`, `campo-lectura-registro-masivo.blade.php`) y JS de interacción — DEBE pasar
  por revisión `impeccable` antes de cerrarse (Principio VI, igual que specs/016/017/019). El ícono
  de editar reutiliza la convención de iconografía ya documentada (`bi-pencil-square` = editar,
  siempre), no la contradice. PASA condicionado a esa revisión en implementación.

Sin violaciones. No aplica Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/020-correccion-exportar-periodo-icono/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

Ningún archivo ni controlador nuevo: los tres defectos se corrigen dentro de archivos ya existentes
de specs/011/015.

```text
resources/
├── views/lecturas/registro-masivo/
│   ├── index.blade.php                                       # hx-boost="false" en los 2 <a> de exportar; type="submit" en Cambiar Periodo
│   └── partials/
│       └── campo-lectura-registro-masivo.blade.php             # separa el ícono de "completada" (informativo) del nuevo botón "editar" (bi-pencil-square)
└── js/registro-masivo-lecturas.js                             # dispose() de tooltips en htmx:beforeCleanupElement

tests/Feature/RegistroMasivoLecturasControllerTest.php         # pruebas de contrato HTML a ampliar
```

**Structure Decision**: Se reutiliza íntegramente la estructura de specs/011/015 — no hay capas
nuevas; cada defecto se corrige en el archivo donde ya vive el marcado o script responsable.
