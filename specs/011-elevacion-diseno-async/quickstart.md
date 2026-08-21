# Quickstart: Elevación de Diseño, Login como Entrada y Escritura Asíncrona

**Feature**: `011-elevacion-diseno-async` | **Date**: 2026-08-21

Guía de validación end-to-end. Ver `research.md` para las decisiones técnicas y `contracts/convenciones-htmx.md` para el detalle de integración.

## Prerrequisitos

- Proyecto con `specs/001` a `specs/010` implementadas y su suite de 191 pruebas pasando (línea base).
- `npm install` tras agregar `htmx.org` a `package.json`; `npm run build` sin errores.

## Escenario 1 — Diseño visual elevado (US1)

1. Navegar cualquier pantalla ya migrada (ej. detalle de un contrato con costos/garantía/representantes).
2. **Resultado esperado**: las tarjetas muestran una sombra sutil que las separa visualmente del fondo, el espaciado entre secciones es perceptiblemente más generoso que antes, y los íconos de acciones (editar, eliminar, pagar) son consistentes en toda la app.
3. Verificar con las herramientas de desarrollador que ningún texto visible baja de 18px y que los botones principales miden al menos 48x48px.
4. Ejecutar `php artisan test` completo — 191/191 deben seguir pasando (no se tocó lógica de negocio).

## Escenario 2 — Login como vista de entrada (US2)

1. Cerrar sesión y navegar a la dirección raíz del sistema.
2. **Resultado esperado**: se muestra la pantalla de login directamente, no la página de bienvenida.
3. Sin iniciar sesión, navegar directamente a `/locaciones`.
4. **Resultado esperado**: se redirige a login; tras autenticarse, se es llevado a `/locaciones` (la página originalmente solicitada), no al panel genérico.
5. Con sesión iniciada, navegar a la dirección raíz.
6. **Resultado esperado**: se es llevado directamente al panel principal (listado de locaciones), sin ver el login ni la bienvenida.
7. Con sesión iniciada, navegar directamente a `/login`.
8. **Resultado esperado**: se redirige al panel principal, sin mostrar el formulario de login.

## Escenario 3 — Escritura asíncrona con JavaScript habilitado (US3)

1. Con sesión iniciada y JavaScript habilitado, crear un contrato con datos válidos.
2. **Resultado esperado**: no hay parpadeo de recarga completa de página (verificable observando que los recursos CSS/JS no se vuelven a solicitar en la pestaña de Red del navegador); la pantalla de detalle del contrato recién creado aparece con el mensaje de éxito.
3. Intentar crear un segundo contrato que se solape en fechas con uno existente.
4. **Resultado esperado**: aparece el mismo mensaje de error explícito y persistente de siempre, sin recarga completa.
5. Hacer doble clic rápido sobre el botón "Guardar Contrato" de un formulario válido.
6. **Resultado esperado**: solo se crea un contrato (no dos), el botón se deshabilita brevemente durante el envío.
7. Eliminar una locación sin sub-locaciones desde su modal de confirmación.
8. **Resultado esperado**: la locación desaparece de la vista sin recarga completa.

## Escenario 4 — Degradación elegante sin JavaScript (US3)

1. Deshabilitar JavaScript en el navegador (o simular la ausencia de htmx).
2. Repetir la creación de un contrato con datos válidos.
3. **Resultado esperado**: el formulario se envía de forma clásica (con recarga completa de página, como antes de esta feature) y el contrato se crea exitosamente — mismo resultado final que en el Escenario 3, paso 2.
4. Repetir el intento de contrato solapado.
5. **Resultado esperado**: mismo mensaje de error, mostrado tras una recarga completa de página.

## Validación automatizada (referencia)

```bash
php artisan test
```

**Cobertura esperada** (Principio IV): la suite completa existente (191 pruebas de 001-010) actúa como gate de no-regresión para US1 y US3 (que no introducen lógica de servidor nueva); `tests/Feature/RutaRaizTest.php` (nuevo) cubre específicamente US2.
