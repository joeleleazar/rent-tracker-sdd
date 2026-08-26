# Research: Correcciones de Auditoría Impeccable

**Feature**: `025-correcciones-auditoria-impeccable` | **Date**: 2026-08-26

## R1 — Regla condicional de "tipo" en `SolicitudGuardarLocacion` (FR-001/FR-002/FR-003)

**Decision**: En `rules()`, calcular `$permitirTipoVacio = $locacion !== null && $locacion->tipo === null` (donde `$locacion = $this->route('locacion')`) y usar `$permitirTipoVacio ? 'nullable' : 'required'` como primera regla de `tipo`, manteniendo `Rule::in(array_keys(Locacion::TIPOS))` en ambos casos.

**Rationale**: `$this->route('locacion')` es `null` en la ruta de creación (no hay un `{locacion}` en la URL de `POST /locaciones`) y es la instancia ya existente (con su valor de `tipo` ANTES de este request) en la ruta de edición (`PUT /locaciones/{locacion}`), porque Laravel resuelve el route-model-binding antes de que el Form Request aplique los datos nuevos — es decir, `$locacion->tipo` en `rules()` sigue siendo el valor previo a la edición, exactamente lo que FR-001/FR-002 necesitan distinguir. El mismo patrón `nullable` + `Rule::in`/`Rule::exists` para un `<select>` opcional ya existe en este mismo Form Request para `locacion_padre_id` (con su opción vacía `"Ninguna (locación raíz)"`), confirmando que Laravel trata el valor `""` enviado por un `<select>` sin selección como "vacío" y omite las reglas de formato (`Rule::in`) cuando el campo es `nullable`, sin necesitar lógica adicional en el controlador ni en la vista.

**Alternatives considered**:
- *Quitar `required` de `tipo` incondicionalmente (siempre `nullable`)*: descartado — violaría FR-003 (crear una locación nueva debe seguir exigiendo tipo).
- *Permitir tipo vacío solo si la request es de edición, sin mirar el valor previo*: descartado — permitiría "vaciar" el tipo de una locación que ya lo tenía asignado, violando FR-002 y cambiando comportamiento no solicitado por la auditoría.
- *Agregar una migración para backfillear `tipo` en las locaciones existentes en vez de tocar la validación*: descartado — la nulabilidad de `tipo` fue una decisión deliberada y documentada (`2026_08_23_020000_add_tipo_to_locaciones_table.php`) para no forzar un valor arbitrario sobre datos ya existentes; forzar un backfill contradice esa decisión previa y no es lo que reportó la auditoría.

## R2 — Consolidación del estilo de `.sidebar-principal` (FR-004/FR-005/FR-006)

**Decision**: Mover `background-color: $dark;`, `width: 100%;` y el bloque `@media (min-width: 768px)` (`width: 280px; min-height: 100vh;`) desde el `<style>` embebido de `app-bootstrap.blade.php` hacia `resources/css/bootstrap.scss`, como reglas del mismo selector `.sidebar-principal` que ya tiene su sección dedicada (línea ~207, junto a `.nav-link:hover`/`.active`). Se conserva la regla `body { font-family: ... }` en el `<style>` embebido (no está duplicada en ningún lado y no fue parte del hallazgo de la auditoría).

**Rationale**: `bootstrap.scss` ya define `$dark: #111827` y ya tiene una sección documentada para `.sidebar-principal` — mover las reglas base ahí es el cambio de menor superficie que elimina la duplicación exacta señalada por la auditoría (FR-004/FR-005), sin tocar ninguna otra regla de esa sección. El resultado visual es idéntico (FR-006) porque el valor hexadecimal y los breakpoints no cambian, solo su ubicación y su referencia al token `$dark` en vez del hex literal.

**Alternatives considered**:
- *Reemplazar `.sidebar-principal` por clases utilitarias de Bootstrap (`bg-dark`, `w-100`, etc.) directamente en el HTML*: descartado — `bg-dark` en Bootstrap usa la variable `$dark` de todas formas, pero el ancho responsivo (`280px` en desktop) no tiene una utilidad nativa equivalente; mantener la clase con nombre propio (`sidebar-principal`) preserva la legibilidad ya establecida y evita tocar el marcado HTML de la vista más de lo necesario.

## R3 — Cierre de la revisión `impeccable` pendiente (FR-007)

**Decision**: Tras aplicar R1 y R2, ejecutar `/impeccable polish` (o `audit`, según el resultado) sobre las 3 vistas señaladas originalmente y documentar el resultado en `DESIGN.md`/su sidecar, como última tarea del feature.

**Rationale**: Es exactamente el paso que la auditoría original identificó como pendiente (Principio VI de la constitución); ejecutarlo al final, después de las correcciones de código, asegura que la revisión documentada refleje el estado ya corregido y no el estado con el bug P0 y la duplicación de estilos todavía presentes.

**Alternatives considered**: Documentar la revisión antes de corregir el código — descartado, dejaría registrada una revisión sobre un estado que ya se sabe defectuoso, contradiciendo el propósito de la revisión.
