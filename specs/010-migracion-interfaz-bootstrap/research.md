# Research: Migración de la Interfaz a Bootstrap 5

**Feature**: `010-migracion-interfaz-bootstrap` | **Date**: 2026-08-21

## 1. Instalación y personalización del sistema visual de Bootstrap 5

**Decision** (histórico; ver `resources/css/bootstrap.scss` actual y Constitución v2.0.0 para los valores vigentes): Instalar Bootstrap 5.3.3 vía npm (`bootstrap`, con su JS bundle que incluye Popper) y compilarlo desde su fuente SCSS (no el CSS precompilado), en un nuevo archivo `resources/css/bootstrap.scss` que sobreescribe las variables Sass de Bootstrap ANTES de importarlo, fijando la paleta de `$primary`/`$success`/`$danger`/`$warning` a los mismos tonos ya validados con contraste WCAG AA en `resources/css/app.css` actual (azul #1e40af-equivalente, verde/rojo de alto contraste). Se agrega `sass` (dart-sass) como devDependency de Vite para compilar este SCSS.

**Rationale**: Bootstrap expone explícitamente sus variables de diseño en Sass para este propósito exacto — evita tener que sobreescribir clase por clase como haría el CSS precompilado. Usar el bundle JS con Popper incluido cubre modales, dropdowns y tooltips sin depender de una librería adicional.

**Alternatives considered**:
- CDN de Bootstrap (CSS/JS precompilados sin personalización de variables): rechazado, obligaría a sobreescribir con CSS custom encima (`!important` o especificidad manual) la tipografía base y el tamaño de botones, en vez de definir la fuente de verdad una sola vez en las variables Sass.
- Mantener Tailwind como capa de utilidades por encima de Bootstrap (framework híbrido permanente): rechazado explícitamente por la especificación (FR-007: Tailwind se retira al completar la migración), y duplicaría el peso del bundle final sin necesidad una vez completada.

## 2. Reemplazo de Alpine.js por el JS nativo de Bootstrap

**Decision**: Los modales de confirmación (`x-modal` + `$dispatch('open-modal'/'close-modal')` de Alpine) se reemplazan por el componente `Modal` nativo de Bootstrap 5 (`data-bs-toggle="modal"` / `data-bs-target="#id"`, o su API JS `bootstrap.Modal`), y los formularios secundarios inline (ej. "Agregar Otro Representante") por el componente `Collapse` de Bootstrap. Alpine.js se retira del `package.json` y de `resources/js/app.js` al completar la migración (FR-007), no antes.

**Rationale**: La Asunción A-001 de la especificación ya identifica que Bootstrap 5 cubre nativamente esta interactividad sin necesitar Alpine. Mantener Alpine.js activo solo hasta que la última vista que lo usa (Tailwind) se migre evita romper esas vistas pendientes a mitad de camino (Edge Case de convivencia).

**Alternatives considered**:
- Mantener Alpine.js indefinidamente junto a Bootstrap para la interactividad: rechazado, la especificación (FR-007) exige el retiro completo del stack anterior al finalizar, y mantener dos librerías de interactividad para el mismo propósito es complejidad innecesaria (principio general del proyecto de evitar dependencias no justificadas).

## 3. Convivencia temporal de Tailwind y Bootstrap durante la migración incremental

**Decision**: Se crea un layout Blade paralelo `resources/views/layouts/app-bootstrap.blade.php` (carga `resources/css/bootstrap.scss` + el bundle JS de Bootstrap) que las vistas migradas adoptan una por una, reemplazando su `<x-app-layout>` (Tailwind) actual. Ambos layouts y ambas hojas de estilo se compilan como entradas Vite independientes (`vite.config.js` con dos entradas CSS) y nunca se cargan juntos en la misma página, evitando colisión de clases utilitarias con el mismo nombre corto (ej. `.card` no existe en el Tailwind actual, pero se verifica caso por caso durante la migración de cada vista).

**Rationale**: El Edge Case de convivencia exige que ninguna hoja de estilo interfiera con la otra visualmente. Cargarlas como entradas Vite separadas, una por layout, garantiza que una página en migración use exclusivamente el CSS del framework al que ya fue migrada, sin necesidad de "namespacing" manual de clases.

**Alternatives considered**:
- Un único layout que cargue ambos frameworks simultáneamente durante toda la migración: rechazado, aumenta el riesgo real de colisión de nombres de clase (Bootstrap y Tailwind comparten nombres cortos como `.container`, `.hidden` con semánticas distintas) y duplica el peso de cada página sin necesidad.
- Migrar todo el proyecto de una sola vez (sin fases): rechazado explícitamente por la especificación, que exige 3 historias de usuario independientemente entregables (P1/P2/P3).

## 4. Verificación de no-regresión contra la suite de pruebas existente

**Decision**: Tras migrar cada bloque de prioridad, se ejecuta la suite completa de Pest (`php artisan test`) sin modificar ninguna aserción de negocio existente. Las aserciones basadas en texto visible (`assertSee('Contrato registrado correctamente.')`, `assertSessionHasErrors('locacion_padre_id')`) no dependen de clases CSS ni de la estructura de tags HTML, por lo que en la gran mayoría de los casos deberían seguir pasando sin cambios; solo se ajustan aserciones que dependieran literalmente de un `id`/`name` de campo si la migración lo renombrara (no debería ocurrir, ya que los `name` de los inputs son parte del contrato con el backend, no de la presentación).

**Rationale**: Confirma FR-004/SC-002. La razón por la que la suite es mayormente inmune al cambio de framework CSS es que las aserciones de Pest de este proyecto ya se centran en contenido/comportamiento (Principio IV: pruebas de reglas de negocio), no en snapshots de marcado HTML — una decisión de diseño de tests que esta migración confirma retroactivamente como acertada.

**Alternatives considered**:
- Tests de snapshot visual (screenshot diffing): rechazado, no es el enfoque de pruebas ya establecido en el proyecto (Pest/PHPUnit sobre HTTP, no testing visual), y agregar esa infraestructura está fuera del alcance de una migración de presentación.

## 5. Gráfico de consumo histórico (FR-005, feature 006)

**Decision**: Chart.js 4.x, un único `<canvas>` por vista de historial, alimentado con un arreglo JSON de `{periodo, consumo}` ya calculado en el controlador/vista existente de `specs/006-historial-lectura-medidor` (reutilizando el dato que la tabla histórica ya muestra, sin nueva lógica de cálculo) e inicializado desde `resources/js/historial-consumo-medidor.js`.

**Rationale**: Chart.js es la librería explícitamente sugerida en los documentos de referencia de UI del usuario y es compatible con Bootstrap (no depende de jQuery ni conflictúa con el bundle de Bootstrap). Un gráfico de líneas simple (consumo por periodo) satisface el Acceptance Scenario de US3 sin requerir backend nuevo: los datos ya existen en la consulta que alimenta la tabla histórica de 006.

**Alternatives considered**:
- ApexCharts o D3.js: rechazados, mayor peso/complejidad que Chart.js para un gráfico de líneas simple; Chart.js ya es el estándar de facto sugerido y suficiente para el caso de uso.
- Renderizar el gráfico en el servidor (imagen estática): rechazado, pierde interactividad básica (tooltips por punto) sin ninguna ventaja real dado que no hay restricción de shared hosting que lo justifique (es JS de cliente, no requiere Imagick/GD del servidor).

## 6. Orden de migración y verificación de accesibilidad

**Decision**: Cada vista migrada se verifica individualmente contra el contraste ≥4.5:1 y la confirmación explícita en acciones destructivas antes de darse por completa, en el mismo orden P1→P2→P3 ya fijado por la especificación, reutilizando el inventario de `contracts/inventario-vistas-migradas.md` como checklist de cobertura.

**Rationale**: Evita descubrir al final de la migración que una vista temprana (P1) no cumple el estándar, lo que obligaría a revisar retroactivamente vistas ya dadas por completas.

**Alternatives considered**:
- Verificar accesibilidad una sola vez al final de las 3 historias: rechazado, dificulta aislar en qué vista se introdujo un defecto de contraste o tamaño si se detecta tarde.
