# Quickstart: Gestión de Contratos de Locación

**Feature**: `002-gestion-contratos` | **Date**: 2026-08-19

Guía de validación end-to-end de la funcionalidad, una vez implementada. No incluye código de implementación (ver `data-model.md` y `contracts/rutas-contrato.md` para el detalle técnico, y `tasks.md` para las tareas de construcción).

## Prerrequisitos

- PHP 8.3+, Composer, PostgreSQL 16+ corriendo localmente (ver `research.md` §1).
- Proyecto Laravel instalado con `.env` apuntando a una base de datos PostgreSQL de desarrollo.
- Migraciones de `specs/001-jerarquia-locaciones` ya ejecutadas (tabla `locaciones` existente), dado que `Contrato` depende de `Locacion`.
- Migraciones y seeders de esta feature ejecutados: `php artisan migrate --seed` (incluye al menos una `Locacion` con `es_alquilable = true` y un `Inquilino` de prueba).
- Usuario Administrador autenticado en el sistema (login vía la pantalla de autenticación estándar del proyecto).

## Escenario 1 — Registro de contrato y regla de no solapamiento (US1)

1. Navegar a `/locaciones/{locacion}/contratos/crear` para la locación de prueba.
2. Registrar un contrato con `fecha_inicio = 2026-01-01`, `fecha_fin = 2026-12-31`, `monto_renta = 1500.00`.
3. Confirmar guardado exitoso (redirección a `show`, contrato visible con estado `borrador` o `activo`).
4. Repetir el paso 2 para la misma locación con `fecha_inicio = 2026-06-01`, `fecha_fin = 2027-05-31` (rango solapado).
5. **Resultado esperado**: el sistema bloquea el segundo registro, muestra un mensaje explícito y persistente de conflicto, y no crea el segundo contrato en la base de datos.
6. Repetir el paso 2 con `fecha_inicio = 2027-01-01` (posterior al fin del primer contrato).
7. **Resultado esperado**: el sistema guarda el tercer contrato sin error, al no existir solapamiento.

## Escenario 2 — Carga de documentos del contrato (US2)

1. Desde el detalle de un contrato (`/contratos/{contrato}`), presionar "Seleccionar PDF del Contrato" y subir un PDF de prueba (< 15MB).
2. **Resultado esperado**: indicador de éxito persistente con el nombre del archivo; el archivo se guarda en `storage/app/private/contratos/{contrato}/` (verificar en el sistema de archivos, no en `public/`); la fila en `documentos_contrato.ruta_archivo` contiene solo la ruta relativa.
3. Abrir `/contratos/{contrato}/documentos/{documento}` autenticado como Administrador y confirmar que el PDF se transmite correctamente.
4. Cerrar sesión e intentar acceder a la misma URL directamente.
5. **Resultado esperado**: acceso denegado (403 o redirección a login) — el archivo no es accesible públicamente.

## Escenario 3 — Historial cronológico de contratos (US3)

1. Con al menos 3 contratos históricos registrados para una misma locación (uno vencido, uno activo, uno futuro), navegar a `/locaciones/{locacion}/contratos`.
2. **Resultado esperado**: los 3 contratos se listan en orden cronológico inverso, el contrato activo se destaca visualmente con una etiqueta de al menos 18px.

## Validación automatizada (referencia)

Ejecutar la suite de pruebas Pest de esta feature:

```bash
php artisan test --filter=Contrato
```

**Cobertura esperada** (Principio IV de la Constitución): modelo `Contrato` (relaciones, scope de solapamiento, casts de `monto_renta`), `ContratoController` (happy path, validación de solapamiento con código 422, autorización), `DocumentoContratoController` (subida válida/ inválida por tamaño o tipo, transmisión autenticada, borrado con confirmación).
