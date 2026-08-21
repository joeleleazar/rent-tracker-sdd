# Research: Representantes de Contrato

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20

## 1. Reconciliación entre `Contrato.inquilino_id` (specs/002) y `Representante` (specs/003)

**Decision**: `Representante` y la tabla pivote `contrato_representante` se implementan como una relación **adicional** a `Contrato`, coexistiendo con la columna `inquilino_id` ya existente y en uso por `specs/002-gestion-contratos`. No se elimina ni se deprecia `inquilino_id` en esta feature.

**Rationale**: `specs/002-gestion-contratos/research.md` §6 dejó pendiente esta reconciliación exactamente para el momento de planificar 003. `Inquilino` (nombre único, sin DNI ni fecha de nacimiento) y `Representante` (apellidos, nombres, DNI único, fecha de nacimiento, con rol legal explícito y designación de "Principal") modelan conceptos distintos: `Inquilino` es quien ocupa/paga el alquiler, mientras que `Representante` es quien firma legalmente en nombre propio o de terceros y debe ser mayor de edad verificable. La propia especificación 003 no menciona reemplazar `inquilino_id`, y `Contrato` ya está en uso por 002 con esa columna como `NOT NULL`; eliminarla ahora rompería datos y pruebas ya existentes de 002 sin que la especificación 003 lo exija. Se opta por la alternativa menos disruptiva y más fiel a ambas especificaciones: coexistencia.

**Alternatives considered**:
- Reemplazar `inquilino_id` por la relación de representantes: rechazado, ninguna especificación lo pide explícitamente y rompería la feature 002 ya implementada.
- Fusionar `Inquilino` y `Representante` en una sola entidad: rechazado, tienen campos y reglas de validación distintas (DNI único + mayoría de edad no aplican a `Inquilino` tal como está especificado en 002); fusionar exigiría reabrir y reescribir la especificación 002, fuera del alcance de planificar 003.

## 2. Directorio global reutilizable de representantes (Edge Case, FR-007)

**Decision**: Tabla `representantes` independiente con `dni` `UNIQUE`; búsqueda por DNI o apellidos vía `LIKE`/`ILIKE` (PostgreSQL) en `RepresentanteController@buscar`, devolviendo resultados para que el administrador seleccione uno existente o registre uno nuevo desde el mismo formulario.

**Rationale**: La especificación exige explícitamente un "Directorio Reutilizable (Catálogo Global)" para evitar duplicidad de datos de una misma persona across múltiples contratos. `ILIKE` de PostgreSQL permite búsqueda case-insensitive sin requerir extensiones adicionales (`pg_trgm` sería una mejora futura de rendimiento con muchos registros, no necesaria al volumen esperado de cientos/pocos miles).

**Alternatives considered**:
- Registrar representantes de forma aislada por contrato (sin catálogo): rechazado explícitamente por la especificación (Edge Case resuelto a favor del catálogo global).
- Búsqueda con `pg_trgm`/similaridad difusa: rechazado como dependencia dura por no estar garantizada en shared hosting; se documenta como mejora opcional si el volumen crece.

## 3. Regla "al menos uno" y "exactamente un Principal" (FR-003, FR-004, Edge Cases)

**Decision**: `ServicioAsociacionRepresentantesContrato::sincronizar(Contrato $contrato, array $representantes)` ejecuta dentro de `DB::transaction`: (a) rechaza si `count($representantes) === 0`; (b) rechaza si el conteo de `es_principal = true` en el arreglo no es exactamente 1; (c) al remover un representante individual (`quitar`), rechaza si es el único asociado al contrato. Esta validación se ejecuta tanto en la creación/edición completa del contrato como en la acción puntual de quitar un representante.

**Rationale**: La especificación exige ambas reglas como gates de guardado (FR-003) y de eliminación individual (FR-004, Edge Case "Eliminación del Último Representante"). Centralizar la lógica en un Service evita duplicar la validación entre el flujo de creación de contrato y el flujo de edición/quitar un representante desde la vista de detalle.

**Alternatives considered**:
- Validar solo en el Form Request: rechazado, la acción de "quitar representante" (US2) no pasa por el mismo Form Request que crea/edita el contrato completo, y debe validarse igualmente (Principio V, transacciones atómicas).

## 4. Validación de mayoría de edad y formato de DNI (FR-002, US3, Asunción A-001)

**Decision**: Regla de validación en `SolicitudGuardarRepresentante`: `dni` como cadena numérica de longitud fija (8 dígitos, formato DNI peruano estándar, ajustable si el usuario aclara otro formato), `fecha_nacimiento` con regla `before_or_equal` a la fecha actual menos 18 años. Complementado con una restricción `CHECK` a nivel de base de datos para la mayoría de edad no es viable de forma portátil (requiere comparar contra `CURRENT_DATE`, que cambia con el tiempo), por lo que esta regla se mantiene únicamente en la capa de aplicación (Form Request + Service), no en una migración.

**Rationale**: PostgreSQL permite `CHECK (fecha_nacimiento <= CURRENT_DATE - INTERVAL '18 years')`, pero un `CHECK` de este tipo evalúa contra la fecha de inserción/actualización, no se re-valida automáticamente con el paso del tiempo para filas ya existentes — el registro seguiría siendo válido siempre que se compare en el momento de escritura, así que en principio sí podría añadirse. Sin embargo, para mantener la regla de negocio testeable y consistente con el resto del proyecto (Principio IV: pruebas en modelo/controlador, no en constraints de base de datos), se implementa en la capa de aplicación como fuente única de verdad, evitando duplicar la lógica de fechas en dos lugares (SQL y PHP) que podrían divergir.

**Alternatives considered**:
- `CHECK` de base de datos como mecanismo único: rechazado, dificulta generar mensajes de error Senior-First descriptivos (Principio III) y complica las pruebas Pest.
- DNI con longitud variable sin patrón fijo: rechazado por defecto — se asume formato de 8 dígitos numéricos (DNI peruano) dado el contexto en español del proyecto; si el usuario opera en otro país, este dato deberá aclararse antes de implementar (marcar como NEEDS CLARIFICATION en `tasks.md` si el administrador lo señala durante la implementación).

## 5. Framework de pruebas

**Decision**: Pest, consistente con `specs/001` y `specs/002`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
