# Quickstart: Inquilinos de Contrato (Inquilino Principal)

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20 | **Revisado**: 2026-08-23

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-inquilino.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001` y `specs/002` ya ejecutadas (tablas `locaciones`, `inquilinos`, `contratos`).
- Migraciones de consolidación de esta feature ejecutadas: `inquilinos` extendida (`apellidos`, `nombres`, `dni`, `fecha_nacimiento`), tabla `contrato_inquilino` creada, datos migrados desde `representantes`/`contrato_representante`/`contratos.inquilino_id`, y esas tres columnas/tablas antiguas eliminadas (ver `research.md` §1).
- Usuario autenticado.

## Escenario 1 — Bloqueo por falta de inquilino (US1)

1. Ir al formulario de creación de contrato (`/locaciones/{locacion}/contratos/crear`) y completar todos los datos del contrato sin agregar ningún inquilino.
2. Intentar guardar.
3. **Resultado esperado**: el sistema bloquea el guardado y muestra "Debe asociar por lo menos un inquilino al contrato antes de guardar" de forma persistente y con alto contraste.
4. Agregar un inquilino (apellidos "Pérez Gómez", nombres "Juan Carlos", DNI "12345678", fecha nacimiento "1960-05-15") y guardar de nuevo.
5. **Resultado esperado**: el contrato se guarda exitosamente con el inquilino asociado y marcado como Principal (único inquilino).

## Escenario 2 — Múltiples inquilinos y remoción (US2)

1. Sobre un contrato con un inquilino ya asignado (Principal), presionar "Agregar Otro Inquilino" y registrar un segundo inquilino.
2. **Resultado esperado**: ambos inquilinos aparecen listados; el Principal existente conserva su designación.
3. Presionar "Quitar Inquilino" sobre el inquilino no-Principal.
4. **Resultado esperado**: aparece un modal de confirmación explícita ("Sí, quitar inquilino" / "No, cancelar"); al confirmar, el inquilino se remueve de la vista y de la base de datos de forma atómica.
5. Intentar quitar al único inquilino restante (el Principal).
6. **Resultado esperado**: el sistema bloquea la acción (FR-004), indicando que el contrato debe mantener al menos un inquilino.
7. Con dos inquilinos nuevamente asociados, intentar quitar al Principal sin designar antes un reemplazo.
8. **Resultado esperado**: el sistema bloquea la acción (FR-009), indicando que debe designarse un nuevo Principal antes de remover al actual.

## Escenario 3 — Validación de datos personales (US3)

1. En el formulario de registro de inquilino, ingresar un DNI con formato inválido (ej. letras o menos de 8 dígitos).
2. **Resultado esperado**: mensaje "El DNI debe tener formato válido" en alto contraste.
3. Ingresar una fecha de nacimiento que indique menor de edad (ej. hace 10 años).
4. **Resultado esperado**: mensaje "El inquilino debe ser mayor de edad" en alto contraste, sin persistir el registro.

## Escenario 4 — Reutilización desde el directorio global (Edge Case, FR-007)

1. Buscar por DNI "12345678" (ya registrado en el Escenario 1) desde el formulario de un contrato distinto.
2. **Resultado esperado**: el sistema encuentra y sugiere al inquilino existente sin permitir crear un duplicado con el mismo DNI.

## Validación automatizada (referencia)

```bash
php artisan test --filter=Inquilino
```

**Cobertura esperada** (Principio IV): modelo `Inquilino` (unicidad de DNI, mayoría de edad), `ServicioAsociacionInquilinosContrato` (mínimo uno, exactamente un Principal, bloqueo de remoción del último y del Principal sin reemplazo), `InquilinoController` (búsqueda, creación, validación 422), y la migración de datos de consolidación (representantes → inquilinos).
