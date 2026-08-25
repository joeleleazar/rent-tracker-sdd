# Implementation Plan: Árbol Jerárquico Horizontal de Locaciones

**Branch**: `013-arbol-jerarquico-locaciones` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/013-arbol-jerarquico-locaciones/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Reemplazar las dos vistas planas actuales (`GET /dashboard` con el listado general de locaciones, y `GET /locaciones` con el listado de locaciones alquilables y su ruta de texto truncada) por una única vista de **tabla jerárquica indentada** en `GET /locaciones`, que muestra todas las locaciones (alquilables y contenedoras) como filas indentadas según su profundidad respecto a `locacion_padre_id`, con columnas Nombre/Locación, Estado, Tipo y Acciones. Cada fila permite Editar (enlaza a `locaciones.show`, que ya tiene Editar/Ver Contratos) y una acción rápida "Agregar" que navega a `locaciones.create` con el padre preseleccionado (US2). Las filas con hijas son colapsables/expandibles mediante el componente `collapse` nativo de Bootstrap 5 ya empaquetado (sin nueva dependencia JS). La tabla se construye con una única consulta de todas las locaciones agrupadas en memoria por `locacion_padre_id` (evita N+1 en jerarquías de hasta 1,000 locaciones, reutilizando `ServicioConstruccionArbolLocaciones` ya implementado), y se renderiza mediante un componente Blade recursivo con layout CSS Grid/Flexbox (columnas alineadas, indentación vía `padding-left` proporcional a la profundidad). `GET /dashboard` sigue redirigiendo a `GET /locaciones`.

**Revisión 2026-08-23**: agrega el campo `tipo` a `Locacion` (lista fija: Galería, Piso, Sector, Pasillo, Local, con ícono `bi-*` por tipo) y reemplaza el estilo visual de tarjetas horizontales conectadas por líneas (implementación anterior) por esta tabla indentada, por pedido explícito del usuario tras ver la primera iteración.

## Technical Context

**Language/Version**: PHP 8.3+ (`composer.json`)

**Primary Dependencies**: Laravel 13.x, Blade Components (recursión de componentes anónimos/de clase), Bootstrap 5.3 `collapse` (ya empaquetado vía `resources/js/bootstrap.js`), Pest 4. Sin nuevas dependencias de JavaScript (ninguna librería de diagramación/árbol de terceros).

**Storage**: PostgreSQL; `locaciones` (`specs/001-jerarquia-locaciones`) gana una columna nueva: `tipo` (`enum` vía `CHECK` constraint, mismo patrón que `contratos.estado`; valores `galeria`/`piso`/`sector`/`pasillo`/`local`; nullable, sin backfill de datos existentes — mismo precedente que `inquilinos.apellidos`/`nombres` en `specs/003`, ver research.md §4). Sin cambios en la relación `locacion_padre_id`; se reutiliza la misma consulta agrupada en memoria (una única carga completa en vez de recorrido recursivo `locacionesHijas()` por fila).

**Testing**: Pest, `RefreshDatabase`; feature tests sobre `LocacionController@index` (estructura de la tabla jerárquica en la vista, filas contenedoras y alquilables presentes, badges e íconos de tipo correctos, indentación por profundidad) y sobre la redirección de `/dashboard`; feature test sobre la acción rápida "Agregar" (parámetro de padre preseleccionado en `locaciones.create`); unit test sobre el servicio/builder de árbol ya existente (sin cambios en esta revisión) y sobre la validación del nuevo campo `tipo`.

**Target Platform**: Servidor Linux de shared hosting, consistente con `specs/002-gestion-contratos/research.md` §2

**Project Type**: Aplicación web monolítica (single project)

**Performance Goals**: Renderizar el árbol completo (hasta 1,000 locaciones, Asunción A-002 de `specs/001`) en una única consulta SQL (`SELECT * FROM locaciones`) sin consultas N+1 por nodo, consistente con SC-003.

**Constraints**: Sin scroll horizontal a nivel de página (FR-007); contraste WCAG AA 4.5:1 en filas, íconos y badges (FR-009); límite defensivo de profundidad al recorrer el árbol (FR-008, mismo patrón que `Locacion::ancestros()`); espaciado suficiente entre filas para legibilidad (pedido explícito del usuario); `tipo` restringido a la lista fija predefinida (FR-010, Assumption A-005).

**Scale/Scope**: Hasta ~1,000 locaciones por cliente (Asunción A-002 de `specs/001-jerarquia-locaciones`).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principio | Cumplimiento en este plan |
|---|---|
| I. Stack Tecnológico Moderno (PHP/Laravel/PostgreSQL) | ✅ Migración aditiva con `enum` (`CHECK` constraint vía driver pgsql, mismo patrón que `contratos.estado`); Eloquent estándar, sin SQL crudo ni ORM bypass |
| II. Nomenclatura en Español | ✅ Columna `tipo`, mapa `Locacion::TIPOS` con valores/etiquetas en español (`galeria` → "Galería", etc., mismo patrón que el `enum` de `contratos.estado`); parcial `fila-arbol-locacion.blade.php` |
| III. Diseño Moderno e Intuitivo | ✅ Tabla indentada legible con columnas claras, íconos consistentes por tipo, badges semánticos ya establecidos; contraste WCAG AA; espaciado generoso entre filas |
| IV. Pruebas Automatizadas Exhaustivas | ✅ Pest cubre la validación del nuevo campo `tipo` (`SolicitudGuardarLocacion`), la vista de tabla (filas, indentación, íconos), la acción rápida "Agregar" con padre preseleccionado, y conserva la cobertura ya existente del servicio de construcción del árbol |
| V. Integridad de Datos y Seguridad Transaccional | N/A — la columna `tipo` se guarda dentro de la misma transacción ya existente de `ServicioValidacionJerarquiaLocacion::validarYEjecutar()`, sin lógica transaccional nueva |
| VI. Sistema de Componentes Visuales (Bootstrap 5) | ✅ `collapse` nativo de Bootstrap para expandir/contraer filas; iconografía `bi-*` consistente por tipo de locación; badges `text-bg-success`/`text-bg-secondary` para alquilable/contenedora; `form-select` para el nuevo campo `tipo` en los formularios de creación/edición |

**Resultado**: PASS. No se identifican violaciones que requieran justificación en `Complexity Tracking`.

## Project Structure

### Documentation (this feature)

```text
specs/013-arbol-jerarquico-locaciones/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/
│   └── vista-arbol-locaciones.md
└── tasks.md
```

### Source Code (repository root)

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── LocacionController.php         # @create/@edit pasan la lista fija de tipos; @create acepta ?locacion_padre_id= para preseleccionar
│   └── Requests/
│       └── SolicitudGuardarLocacion.php   # Se agrega la regla de 'tipo' (in: galeria,piso,sector,pasillo,local, nullable)
├── Services/
│   └── ServicioConstruccionArbolLocaciones.php   # Sin cambios en esta revisión (ya agrupa/limita profundidad)
└── Models/
    └── Locacion.php                        # Se agrega 'tipo' a $fillable y un mapa estático tipo → [ícono bi-*, etiqueta]

database/
└── migrations/
    └── xxxx_add_tipo_to_locaciones_table.php   # Nueva: columna 'tipo' enum nullable

resources/
├── views/
│   └── locaciones/
│       ├── index.blade.php                 # Reescrita: tabla con encabezado de columnas + filas indentadas
│       ├── create.blade.php                # Se agrega el select de 'tipo'; preselecciona locacion_padre_id desde query string
│       ├── edit.blade.php                  # Se agrega el select de 'tipo'
│       └── partials/
│           └── fila-arbol-locacion.blade.php   # Reemplaza a nodo-arbol-locacion.blade.php: fila de tabla indentada (nombre+ícono, estado, tipo, acciones), hijos colapsables
└── css/
    └── bootstrap.scss                      # Sección 5 reescrita: estilos de tabla indentada (encabezado, filas, indentación por profundidad) en vez de tarjetas conectadas

routes/
└── web.php                                 # Sin cambios adicionales en esta revisión (ya redirige /dashboard)

tests/
├── Feature/
│   └── LocacionControllerTest.php          # Casos actualizados: columnas/indentación de la tabla, ícono por tipo, acción rápida "Agregar" con padre preseleccionado
└── Unit/
    └── LocacionTest.php                    # Casos nuevos: mapa de ícono/etiqueta por tipo
```

**Structure Decision**: Aplicación Laravel monolítica única, sin subproyectos adicionales. Se reemplaza únicamente la capa de presentación (`nodo-arbol-locacion.blade.php` → `fila-arbol-locacion.blade.php`, estilos de `bootstrap.scss`) y se extiende el modelo de datos con `tipo`; `ServicioConstruccionArbolLocaciones` y la estructura de datos en memoria (`data-model.md`) se mantienen sin cambios, ya que el problema de agrupación/anidamiento ya estaba resuelto correctamente en la iteración anterior.

## Complexity Tracking

*No violations identified — table intentionally left empty.*
