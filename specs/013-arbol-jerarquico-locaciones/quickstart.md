# Quickstart: Árbol Jerárquico Horizontal de Locaciones

**Feature**: `013-arbol-jerarquico-locaciones` | **Date**: 2026-08-23 | **Revisado**: 2026-08-23 (tabla indentada + campo Tipo)

Guía de validación end-to-end. Ver `data-model.md` y `contracts/vista-arbol-locaciones.md` para el detalle técnico, y `tasks.md` para las tareas de construcción.

## Prerrequisitos

- Migraciones de `specs/001-jerarquia-locaciones` y de esta feature (`tipo`) ya ejecutadas.
- Usuario autenticado.
- Datos de ejemplo: "Galería El Sol" (No Alquilable, Tipo Galería, raíz) → "Piso 1" (No Alquilable, Tipo Piso) → "Local A" (Alquilable, Tipo Local) y "Local B" (Alquilable, Tipo Local); y una segunda raíz independiente "Local Suelto" (Alquilable, Tipo Local, sin padre).

## Escenario 1 — Vista unificada como tabla jerárquica (US1)

1. Ir a `/locaciones` (o `/dashboard`, que ahora redirige ahí).
2. **Resultado esperado**: se muestra una tabla con columnas Nombre/Locación, Estado, Tipo y Acciones. "Galería El Sol" aparece primero, con "Piso 1" indentado debajo, y "Local A"/"Local B" indentados un nivel más debajo de "Piso 1"; "Local Suelto" aparece como una fila independiente sin indentación. Cada fila muestra el ícono correspondiente a su Tipo. No existe ninguna otra pantalla con un listado plano de locaciones.

## Escenario 2 — Gestión directa desde la fila (US2)

1. Sobre la tabla visible, presionar "Editar" en la fila de "Piso 1" (No Alquilable).
2. **Resultado esperado**: se navega a la edición de "Piso 1", donde están disponibles los campos de edición y (desde el detalle) Eliminar (sin "Ver Contratos", por no ser alquilable).
3. Volver a la tabla y presionar "Editar" en la fila de "Local A" (Alquilable).
4. **Resultado esperado**: se navega a la edición de "Local A".
5. Volver a la tabla y presionar el botón "+" en la fila de "Piso 1".
6. **Resultado esperado**: se navega al formulario de creación de una nueva locación, con "Piso 1" ya preseleccionada como locación padre.

## Escenario 3 — Colapsar y expandir filas (US3)

1. Sobre la tabla visible, presionar el ícono de colapsar sobre la fila de "Piso 1".
2. **Resultado esperado**: las filas de "Local A" y "Local B" dejan de mostrarse; el ícono cambia para indicar que la fila está contraída.
3. Presionar nuevamente el mismo control.
4. **Resultado esperado**: "Local A" y "Local B" vuelven a mostrarse en su posición original, con la indentación correcta.

## Escenario 4 — Espaciado y ausencia de scroll de página (Edge Case, FR-007)

1. Con la tabla de ejemplo visible, verificar visualmente que existe separación clara entre filas y que la indentación por nivel es consistente, sin elementos superpuestos o apretados.
2. Reducir el ancho de la ventana del navegador o simular una jerarquía con muchos niveles de profundidad.
3. **Resultado esperado**: la tabla se desplaza horizontalmente dentro de su propio contenedor si la indentación acumulada lo requiere; la página completa nunca presenta scroll horizontal. Una locación con muchas hijas directas simplemente agrega más filas verticalmente, sin afectar el ancho.

## Escenario 5 — Locación sin Tipo asignado (Edge Case)

1. Con una locación registrada antes de esta revisión (sin valor de `tipo`), visualizarla en la tabla.
2. **Resultado esperado**: la columna Tipo muestra "Sin tipo" con un ícono neutro, sin error ni fila vacía.

## Validación automatizada (referencia)

```bash
php artisan test --filter=Locacion
```

**Cobertura esperada** (Principio IV): `ServicioConstruccionArbolLocaciones` (sin cambios, ya cubierto), `LocacionController@index` (todas las locaciones presentes como filas, indentación e íconos de tipo correctos), `LocacionController@create` (preselección de `locacion_padre_id` desde query string), validación de `tipo` en `SolicitudGuardarLocacion`, redirección de `/dashboard` a `/locaciones`.
