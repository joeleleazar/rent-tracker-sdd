# Research: Jerarquía de Locaciones Alquilables

**Feature**: `001-jerarquia-locaciones` | **Date**: 2026-08-20

Este documento resuelve las decisiones técnicas necesarias antes del diseño (Fase 1), considerando que el modelo `Locacion` y su migración ya fueron creados como prerrequisito mínimo al planificar `specs/002-gestion-contratos` (ver comentario en la migración `2026_08_20_031146_create_locaciones_table.php`), por lo que esta feature completa la lógica de negocio, controlador, vistas y pruebas que la especificación 001 exige, sin volver a crear el esquema base.

## 1. Discrepancia de versión de Laravel respecto a la Constitución

**Decision**: Documentar la versión realmente instalada (Laravel 13.x, según `composer.json: "laravel/framework": "^13.17"`) como el Technical Context de esta feature, en vez de forzar "Laravel 11.x" como indica la Constitución vigente (`.specify/memory/constitution.md`, sección "Restricciones Técnicas").

**Rationale**: La Constitución fue redactada fijando Laravel 11.x como restricción explícita, pero el proyecto ya fue inicializado con Laravel 13.x antes de que existiera esta feature (evidencia: `composer.json` en el repositorio, commit inicial). Esta feature no introduce esa discrepancia ni puede resolverla unilateralmente: cambiar la versión del framework instalado está fuera del alcance de "Jerarquía de Locaciones", y forzar el plan a "Laravel 11.x" documentaría una versión que no es la real. Se documenta como observación para que el usuario decida si actualiza la Constitución (PATCH/MINOR de versión) o si degrada el framework — ninguna de las dos acciones corresponde a esta feature.

**Alternatives considered**:
- Ignorar la discrepancia y escribir "Laravel 11.x" en el plan como hace `specs/002-gestion-contratos/plan.md`: rechazado, perpetuaría información incorrecta sobre el stack real.
- Bloquear el plan hasta que se resuelva la discrepancia: rechazado, no es una violación introducida por esta feature ni impide implementar la jerarquía de locaciones.

## 2. Detección de dependencias circulares en la jerarquía (FR-003, US3)

**Decision**: Validación en `ServicioValidacionJerarquiaLocacion`, ejecutada dentro de `DB::transaction` antes de guardar: al asignar/editar `locacion_padre_id`, el servicio recorre la cadena de ancestros del padre propuesto (vía consultas iterativas simples sobre `locacion_padre_id`, sin CTE recursiva nativa) hasta la raíz o hasta 1,000 saltos (límite de seguridad acorde a la Asunción A-002), y rechaza la operación si la propia locación aparece entre esos ancestros.

**Rationale**: PostgreSQL soporta CTEs recursivas (`WITH RECURSIVE`) sin necesitar extensiones adicionales, por lo que técnicamente podría usarse una única consulta recursiva; sin embargo, dado el volumen acotado (≤1,000 locaciones, Asunción A-002), una validación iterativa en la capa de aplicación es igual de eficiente, más simple de testear unitariamente con Pest, y evita acoplar la regla de negocio a sintaxis SQL específica de PostgreSQL en el Service. Se mantiene dentro de una transacción para evitar condiciones de carrera si dos ediciones concurrentes modifican la misma cadena de padres.

**Alternatives considered**:
- CTE recursiva (`WITH RECURSIVE`) directamente en el Service vía consulta cruda: rechazado como mecanismo único porque complica las pruebas unitarias y el Principio I de la Constitución desalienta el SQL crudo sin justificación técnica clara; se documenta como optimización futura si el volumen de locaciones creciera muy por encima de 1,000.
- Validar solo en el Form Request (nivel de presentación): rechazado, no previene condiciones de carrera ni cubre ediciones vía otros puntos de entrada (Principio V).

## 3. Bloqueo de eliminación de locaciones con sub-locaciones (Edge Case, FR-007)

**Decision**: La migración existente ya define `locacion_padre_id` con `nullOnDelete()` a nivel de base de datos (lo que dejaría huérfanas a las hijas con padre `null` en vez de bloquear el borrado). Esta feature añade una verificación previa en `ServicioValidacionJerarquiaLocacion`/`LocacionController@destroy`: antes de intentar eliminar, se comprueba `locacionesHijas()->exists()` y, si es verdadero, se rechaza la operación con un mensaje explícito (FR-007), sin llegar a ejecutar el `DELETE`.

**Rationale**: El comportamiento `nullOnDelete()` de la migración es una salvaguarda de integridad referencial a nivel de base de datos (evita un error de FK), pero la especificación exige explícitamente **bloquear** la eliminación, no reasignar los hijos a `null` silenciosamente. La verificación de aplicación debe ejecutarse primero para cumplir el requisito funcional; `nullOnDelete()` queda como red de seguridad de última instancia si algún proceso externo (ej. un comando artisan futuro) eliminara una fila sin pasar por el Service.

**Alternatives considered**:
- Cambiar la migración a `restrictOnDelete()` en vez de `nullOnDelete()`: viable como refuerzo a nivel de base de datos, pero requeriría una nueva migración de alteración de columna; se documenta como mejora opcional en `tasks.md`, no como bloqueante de esta feature, dado que la migración ya está aplicada y usada por `specs/002`.
- Confiar únicamente en la restricción de base de datos y capturar la excepción SQL: rechazado, el mensaje de error resultante no sería el mensaje explícito y persistente que exige el Principio III.

## 4. Truncamiento de la jerarquía a 3 niveles en la interfaz (FR-004)

**Decision**: El breadcrumb se calcula en el backend (helper en el modelo `Locacion`, ej. `rutaJerarquiaTruncada(): array`) recorriendo los ancestros y devolviendo únicamente los últimos 3 niveles con un indicador de omisión (`"..."`) si la cadena real es más larga; el componente Blade `ruta-jerarquia-locacion.blade.php` solo se encarga de renderizar el array ya truncado, sin lógica de truncamiento en la vista.

**Rationale**: Mantener la lógica de truncamiento en el modelo/backend permite testearla unitariamente con Pest (Principio IV) de forma aislada de la vista, y evita duplicar la regla de negocio si el breadcrumb se necesita en más de una vista (listado y detalle).

**Alternatives considered**:
- Truncar en la vista Blade con un `@foreach` condicional: rechazado, mezcla lógica de negocio con presentación y dificulta la prueba automatizada exhaustiva exigida por la Constitución.

## 5. Framework de pruebas

**Decision**: Pest (sobre PHPUnit), consistente con `specs/002-gestion-contratos/research.md` §5 y con las dependencias ya presentes en `composer.json` (`pestphp/pest ^4.7`).

**Rationale**: Ya es el framework adoptado por el resto del proyecto; no hay razón para introducir inconsistencia.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
