# Quickstart: Representantes de Contrato

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20

Guía de validación end-to-end. Ver `data-model.md` y `contracts/rutas-representante.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001` y `specs/002` ya ejecutadas (tablas `locaciones`, `inquilinos`, `contratos`).
- Migraciones de esta feature ejecutadas (`representantes`, `contrato_representante`).
- Usuario autenticado.

## Escenario 1 — Bloqueo por falta de representante (US1)

1. Ir al formulario de creación de contrato (`/locaciones/{locacion}/contratos/crear`) y completar todos los datos del contrato sin agregar ningún representante.
2. Intentar guardar.
3. **Resultado esperado**: el sistema bloquea el guardado y muestra "Debe asociar por lo menos un representante al contrato antes de guardar" con tipografía ≥18px y alto contraste.
4. Agregar un representante (apellidos "Pérez Gómez", nombres "Juan Carlos", DNI "12345678", fecha nacimiento "1960-05-15") y guardar de nuevo.
5. **Resultado esperado**: el contrato se guarda exitosamente con el representante asociado y marcado como Principal (único representante).

## Escenario 2 — Múltiples representantes y remoción (US2)

1. Sobre un contrato con un representante ya asignado, presionar "Agregar Otro Representante" y registrar un segundo representante.
2. **Resultado esperado**: ambos representantes aparecen listados; el sistema exige designar exactamente uno como Principal.
3. Presionar "Quitar Representante" sobre uno de ellos.
4. **Resultado esperado**: aparece un modal de confirmación Senior-First ("Sí, quitar representante" / "No, cancelar"); al confirmar, el representante se remueve de la vista y de la base de datos de forma atómica.
5. Intentar quitar al único representante restante.
6. **Resultado esperado**: el sistema bloquea la acción (FR-004), indicando que el contrato debe mantener al menos un representante.

## Escenario 3 — Validación de datos personales (US3)

1. En el formulario de registro de representante, ingresar un DNI con formato inválido (ej. letras o menos de 8 dígitos).
2. **Resultado esperado**: mensaje "El DNI debe tener formato válido" en alto contraste.
3. Ingresar una fecha de nacimiento que indique menor de edad (ej. hace 10 años).
4. **Resultado esperado**: mensaje "El representante debe ser mayor de edad" en alto contraste, sin persistir el registro.

## Escenario 4 — Reutilización desde el directorio global (Edge Case, FR-007)

1. Buscar por DNI "12345678" (ya registrado en el Escenario 1) desde el formulario de un contrato distinto.
2. **Resultado esperado**: el sistema encuentra y sugiere al representante existente sin permitir crear un duplicado con el mismo DNI.

## Validación automatizada (referencia)

```bash
php artisan test --filter=Representante
```

**Cobertura esperada** (Principio IV): modelo `Representante` (unicidad de DNI, mayoría de edad), `ServicioAsociacionRepresentantesContrato` (mínimo uno, exactamente un Principal, bloqueo de remoción del último), `RepresentanteController` (búsqueda, creación, validación 422).
