# Quickstart: Jerarquía de Locaciones Alquilables

**Feature**: `001-jerarquia-locaciones` | **Date**: 2026-08-20

Guía de validación end-to-end de la funcionalidad, una vez implementada. No incluye código de implementación (ver `data-model.md` y `contracts/rutas-locacion.md` para el detalle técnico, y `tasks.md` para las tareas de construcción).

## Prerrequisitos

- PHP 8.3+, Composer, PostgreSQL corriendo localmente.
- Proyecto Laravel instalado con `.env` apuntando a una base de datos PostgreSQL de desarrollo.
- Migraciones ya ejecutadas (`php artisan migrate`); la tabla `locaciones` ya existe desde el prerrequisito de `specs/002-gestion-contratos`.
- Usuario autenticado en el sistema (login vía la pantalla de autenticación estándar del proyecto).

## Escenario 1 — Visualización accesible de jerarquía (US1)

1. Crear vía seeder/factory la cadena "Galería El Sol" (no alquilable) → "Piso 1" (no alquilable) → "Local A" (alquilable).
2. Navegar a `/locaciones/{local_a}`.
3. **Resultado esperado**: se muestra la cadena completa "Galería El Sol > Piso 1 > Local A" con tipografía ≥18px y contraste adecuado, sin menús desplegables.
4. Navegar a `/locaciones` (listado).
5. **Resultado esperado**: solo aparecen locaciones con `es_alquilable = true`, cada una mostrando su locación contenedora como contexto estático.

## Escenario 2 — Configuración de la estructura (US2)

1. Navegar a `/locaciones/crear`.
2. Registrar "Piso 2" con padre "Galería Central", tamaño "120.00", ubicación "Sector Norte", descripción "Primer nivel de la galería", marcado como "No Alquilable".
3. **Resultado esperado**: guardado exitoso, relación de clave foránea correcta en la base de datos.
4. Repetir el registro dejando el campo "tamaño" vacío.
5. **Resultado esperado**: el sistema detiene el proceso y muestra un mensaje de error explícito y persistente.

## Escenario 3 — Prevención de jerarquías cíclicas (US3)

1. Con "Piso 1" teniendo como padre a "Galería El Sol", editar "Galería El Sol" e intentar asignarle como padre a "Piso 1".
2. **Resultado esperado**: el sistema bloquea la acción, muestra "No se puede asignar una locación hija como padre" y no guarda los cambios.

## Escenario 4 — Bloqueo de eliminación con sub-locaciones (Edge Case)

1. Intentar eliminar "Galería El Sol" mientras tiene "Piso 1" asociado como hija.
2. **Resultado esperado**: el sistema impide la eliminación y muestra un mensaje explícito indicando que debe eliminar o reasignar las locaciones hijas primero.

## Escenario 5 — Truncamiento de jerarquía profunda (Edge Case)

1. Crear una cadena de 5 niveles de profundidad, con la última locación marcada como alquilable.
2. Navegar al detalle de la última locación.
3. **Resultado esperado**: el breadcrumb muestra únicamente los últimos 3 niveles con un indicador de omisión (ej. "... > Piso 1 > Local 10"), sin desbordamiento ni scroll horizontal.

## Validación automatizada (referencia)

Ejecutar la suite de pruebas Pest de esta feature:

```bash
php artisan test --filter=Locacion
```

**Cobertura esperada** (Principio IV de la Constitución): modelo `Locacion` (relación reflexiva, scope `alquilables`, helpers de ancestros y truncamiento), `LocacionController` (happy path, validación, detección de ciclos con código 422, bloqueo de eliminación con hijas asociadas), `ServicioValidacionJerarquiaLocacion` (casos unitarios de ciclos directos e indirectos).
