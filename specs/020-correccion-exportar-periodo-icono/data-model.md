# Data Model: Corrección de Exportación, Cambio de Periodo e Ícono de Edición en Registro Masivo

**Feature**: `020-correccion-exportar-periodo-icono` | **Date**: 2026-08-25

Esta corrección **no agrega, modifica ni elimina ninguna columna, tabla ni relación**. Los tres
defectos son de marcado (atributos `hx-boost`/`type`) y de comportamiento de JavaScript de
terceros (tooltips de Bootstrap) sobre datos ya existentes de specs/005/015-019 — ningún dato
nuevo se persiste ni se deja de persistir como resultado de esta feature.

## Entidades involucradas (sin cambios)

- **Lectura de Medidor**: su valor de `lectura_actual`/`total`/`consumo_calculado` es lo que se
  exporta (FR-002/FR-003) y lo que el ícono/botón de esta corrección muestra o permite editar
  (FR-004/FR-005) — ninguno de sus campos ni su ciclo de vida cambian.
- **Configuración General / Locación**: sin cambios; el periodo seleccionado (FR-001) sigue siendo
  un parámetro de consulta (`?periodo=YYYY-MM`) ya resuelto por
  `RegistroMasivoLecturasController::resolverPeriodo()`, sin cambios en esa lógica.
