# Implementation Plan: Elevación de Diseño, Login como Entrada y Escritura Asíncrona

**Branch**: `011-elevacion-diseno-async` | **Date**: 2026-08-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/011-elevacion-diseno-async/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Tres mejoras sobre la interfaz Bootstrap 5 ya migrada (spec 010): (1) refinar variables Sass y clases de utilidad para dar más jerarquía visual, profundidad y carácter sin sacrificar contraste ni legibilidad; (2) cambiar la ruta raíz para que redirija a login o al panel según haya sesión, retirando `welcome.blade.php` del flujo; (3) adoptar **htmx** con `hx-boost` como capa de progressive enhancement para que la navegación y los formularios de escritura se sientan asíncronos, sin exigir ningún cambio en controladores, rutas ni Form Requests existentes, y sin romper el funcionamiento clásico (full-page) si JavaScript no está disponible.

## Technical Context

**Language/Version**: PHP 8.3+ (sin cambios)

**Primary Dependencies**: htmx 2.x (vía npm o vendorizado, ver `research.md` §3), sin nuevas dependencias de backend. Bootstrap 5.3, Bootstrap Icons, Chart.js, sass — sin cambios respecto a `specs/010`.

**Storage**: Sin cambios — PostgreSQL, mismas tablas de 001-010. Esta feature no crea, altera ni elimina ninguna tabla.

**Testing**: Pest (sin cambios). La suite completa existente (191 pruebas) se ejecuta como gate de no-regresión tras cada historia de usuario; se espera que seguir pasando sin modificaciones, ya que htmx solo agrega la cabecera `HX-Request` a sus peticiones — el resto de método/ruta/cuerpo/respuesta es idéntico a una petición de formulario clásica, y las pruebas actuales no envían esa cabecera (ejercitan exactamente el camino de degradación clásico exigido por FR-007).

**Target Platform**: Servidor Linux de shared hosting, sin cambios; htmx es una librería puramente de cliente (un único archivo JS servido por Vite), no requiere ningún proceso adicional en el servidor.

**Project Type**: Aplicación web monolítica (single project), sin cambios de estructura.

**Performance Goals**: Las transiciones de página boosteadas por htmx no deben mostrar un parpadeo de recarga completa perceptible (SC-005); el archivo htmx agrega ~14KB comprimido al bundle, insignificante frente al bundle Bootstrap ya existente.

**Constraints**: Ningún controlador, Form Request, modelo, servicio, migración o ruta existente cambia de comportamiento (FR-006); la degradación sin JavaScript MUST producir el mismo resultado final que hoy (FR-007), ya que ese es literalmente el comportamiento actual sin modificar.

**Scale/Scope**: Aplica `hx-boost` a nivel de layout compartido (un solo lugar) más ajustes puntuales en los formularios con validación por Form Request/Service ya existentes (para indicador de carga y bloqueo de doble envío); refinamiento visual sobre las mismas ~35 vistas ya migradas en `specs/010`; un solo cambio de ruta (`/`) para el login-first.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Sin cambios en PHP/Laravel/PostgreSQL; htmx es una librería de cliente que no altera el patrón "Blade Templates o componentes desacoplados" ya permitido explícitamente por la Constitución — el servidor sigue rindiendo HTML server-side, no una API JSON |
| II. Nomenclatura en Español | ✅ Cualquier JS nuevo (manejo de doble envío, mensajes de error de red) se escribe con nombres de función/variable en español; los atributos `hx-*`/`data-*` son parte de la sintaxis del framework, igual criterio que las clases `btn`/`card` de Bootstrap |
| III. Diseño Moderno e Intuitivo | ✅ Objetivo explícito (FR-001, FR-008): el contraste se preserva; la degradación sin JS (FR-007) es en sí misma un requisito de accesibilidad para usuarios con tecnología asistiva o navegadores desactualizados (ver spec Edge Case) |
| IV. Pruebas Automatizadas Exhaustivas | ✅ No se agrega lógica de negocio nueva, pero la suite completa (191 pruebas) actúa como gate de no-regresión tras cada historia; se agregan pruebas Feature puntuales solo para el nuevo comportamiento de enrutamiento de US2 (`/` redirige según sesión) |
| V. Integridad de Datos y Seguridad Transaccional | ✅ Sin cambios: htmx no altera ninguna transacción de base de datos; las mismas validaciones y `DB::transaction` ya existentes se ejecutan idénticamente sin importar el canal de la petición |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/011-elevacion-diseno-async/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── convenciones-htmx.md
└── tasks.md
```

### Source Code (repository root)

```text
resources/
├── css/
│   └── bootstrap.scss                 # Se amplían las variables (sombras, escala de espaciado, paleta) — ver research.md §1
├── js/
│   ├── htmx.js                        # Nuevo: import de htmx + configuración de indicador de carga y bloqueo de doble envío
│   └── bootstrap.js                   # Sin cambios de contenido; se agrega la carga de htmx.js en el layout
└── views/
    └── components/layouts/
        └── app-bootstrap.blade.php    # Se agrega hx-boost="true" al contenedor principal y el <script> de htmx

routes/
└── web.php                            # Ruta '/' cambia de `view('welcome')` a redirect condicional (login o dashboard)

app/Http/Controllers/
└── (sin cambios de código; ver research.md §2 — htmx boost no requiere lógica adicional en el servidor)

tests/Feature/
└── RutaRaizTest.php                   # Nuevo: prueba de US2 (redirección de '/' según sesión)
```

**Structure Decision**: No se reorganiza el árbol del proyecto. El cambio de mayor alcance (US3) se concentra en un único punto de integración (el layout compartido + un archivo JS de configuración de htmx), en vez de tocar cada controlador o vista individualmente — es la ventaja central de adoptar `hx-boost` en vez de reescribir cada formulario a mano.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
