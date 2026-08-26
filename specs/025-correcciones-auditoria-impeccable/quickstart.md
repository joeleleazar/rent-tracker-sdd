# Quickstart: Validar Correcciones de Auditoría Impeccable

**Feature**: `025-correcciones-auditoria-impeccable` | **Date**: 2026-08-26

## 1. Verificar el fix de "tipo" (FR-001/FR-002/FR-003, SC-001/SC-002)

```bash
php artisan test --filter=LocacionControllerTest
```

Casos a confirmar manualmente si se desea (ya cubiertos por los tests nuevos de `tasks.md`):
- Editar `Galería Los Pinos` (u otra locación sembrada sin tipo) cambiando solo el nombre, sin tocar "Tipo" → debe guardar sin error.
- Editar la misma locación seleccionando un tipo válido → debe guardar con ese tipo.
- Editar una locación que ya tiene tipo, dejando "Tipo" en blanco → debe seguir rechazando con el mismo mensaje de error.
- Crear una locación nueva sin seleccionar tipo → debe seguir rechazando, sin cambios.

## 2. Verificar la consolidación del sidebar (FR-004/FR-005/FR-006, SC-003, SC-005)

```bash
grep -n "sidebar-principal" resources/views/components/layouts/app-bootstrap.blade.php resources/css/bootstrap.scss
```

Confirmar que `app-bootstrap.blade.php` ya no define `background-color`/`width`/`min-height` para `.sidebar-principal`, y que `bootstrap.scss` sí los define usando `$dark` (no un hex literal).

Visualmente: abrir cualquier pantalla autenticada en desktop y en un viewport móvil (<768px) y confirmar que el sidebar se ve exactamente igual que antes (franja oscura, ancho 280px en desktop, franja horizontal en móvil).

## 3. Verificar el cierre de la revisión de diseño (FR-007, SC-004)

Ejecutar `/impeccable polish` (o `audit`) sobre `app-bootstrap.blade.php`, `error-modal-recibo.blade.php` y `estado-recibo-locacion.blade.php`, y confirmar que el resultado queda documentado en `DESIGN.md` o su sidecar.

## 4. Regresión general

```bash
php artisan test
```

Ninguna aserción existente debe cambiar de resultado esperado (SC-005 del feature 018 sigue vigente como referencia general del proyecto).
