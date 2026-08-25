# Quickstart: Migración de la Interfaz a Bootstrap 5

**Feature**: `010-migracion-interfaz-bootstrap` | **Date**: 2026-08-21

Guía de validación end-to-end de la migración, una vez implementada por bloque de prioridad. Ver `contracts/inventario-vistas-migradas.md` para el detalle vista por vista y `research.md` para las decisiones técnicas.

## Prerrequisitos

- Proyecto con las 9 features (001-009) ya implementadas y con su suite de 191 pruebas pasando (línea base antes de empezar la migración).
- `npm install` ejecutado tras agregar `bootstrap`, `bootstrap-icons`, `chart.js` y `sass` a `package.json`.
- `npm run build` genera ambas entradas Vite (Tailwind y Bootstrap) sin errores durante la migración incremental.

## Escenario 1 — Migración P1 sin romper el núcleo del negocio (US1)

1. Migrar el layout base (`app-bootstrap.blade.php`) y los componentes compartidos (alertas, modal, botones, breadcrumb).
2. Migrar las vistas de `locaciones/` y `contratos/` (incluidas sus 3 parciales: costos, garantía, representantes).
3. Navegar el flujo completo: crear una locación con jerarquía, crear un contrato con representante/costos/garantía, intentar un solapamiento de fechas, intentar eliminar una locación con hijas.
4. **Resultado esperado**: cada pantalla se ve con componentes Bootstrap 5 (cards, tablas responsive, modales, `input-group` con S/), pero el comportamiento (mensajes de error, bloqueos, persistencia) es idéntico al de antes de migrar.
5. Ejecutar `php artisan test` completo.
6. **Resultado esperado**: 191/191 pruebas siguen pasando.

## Escenario 2 — Migración P2 del flujo de facturación (US2)

1. Migrar las vistas de `locaciones/lecturas/` y `locaciones/recibos/`.
2. Registrar una lectura de medidor, generar un recibo con prorrateo, cambiar su estado a "Pagado", generar la imagen para WhatsApp.
3. Registrar la resolución de una garantía desde el detalle de contrato (ya migrado en P1).
4. **Resultado esperado**: el selector de estado usa `btn-group`/`btn-check` de Bootstrap con el mismo comportamiento; el flujo de WhatsApp/impresión no se ve afectado (su lógica de `html2canvas` es independiente del framework CSS).
5. Ejecutar `php artisan test` completo — 191/191 deben seguir pasando.

## Escenario 3 — Migración P3 y gráfico de consumo (US3)

1. Migrar/extender la vista de historial de lecturas con un `<canvas>` Chart.js mostrando el consumo por periodo, y la vista de configuración general.
2. Navegar al historial de una locación con 6+ periodos registrados.
3. **Resultado esperado**: se ve el gráfico de consumo además de la tabla histórica ya migrada, ambos con tipografía legible y alto contraste.
4. Ejecutar `php artisan test` completo — 191/191 deben seguir pasando.

## Escenario 4 — Retiro del sistema de estilos anterior (cierre de la migración)

1. Confirmar, contra `contracts/inventario-vistas-migradas.md`, que las ~30 vistas listadas ya usan `layouts/app-bootstrap.blade.php` y componentes Bootstrap.
2. Eliminar `resources/css/app.css` (Tailwind), la entrada Tailwind de `vite.config.js`, `tailwindcss`/`@tailwindcss/vite`/`alpinejs` de `package.json`, y los componentes Blade Tailwind ya sin uso (`x-app-layout` original, `btn-senior-*`/`campo-senior`/`etiqueta-senior`).
3. Ejecutar `npm run build` y `php artisan test` una última vez.
4. **Resultado esperado**: el build compila sin referencias rotas y la suite completa sigue en 191/191 (o más, si se agregaron pruebas nuevas de accesibilidad).

## Validación automatizada (referencia)

```bash
php artisan test
```

**Cobertura esperada** (Principio IV de la Constitución): la suite completa existente (191 pruebas de 001-009) actúa como gate de no-regresión en cada uno de los 3 escenarios anteriores; no se espera necesariamente sumar pruebas nuevas, salvo que se detecte una aserción dependiente de marcado que deba actualizarse.
