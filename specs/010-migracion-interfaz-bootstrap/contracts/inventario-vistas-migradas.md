# Contrato de Interfaz: Inventario de Vistas a Migrar

**Feature**: `010-migracion-interfaz-bootstrap` | **Date**: 2026-08-21

Este documento es el contrato de cobertura de la migración: enumera cada vista/componente Blade actualmente en Tailwind/Alpine, su bloque de prioridad, y el componente Bootstrap 5 equivalente que la reemplaza. Ninguna ruta, nombre de campo (`name`) de formulario, ni acción de controlador cambia — solo el marcado y las clases.

## Componentes Blade compartidos (migran una sola vez, se usan en todas las prioridades)

| Componente actual (Tailwind/Alpine) | Reemplazo Bootstrap 5 | Prioridad |
|---|---|---|
| `x-app-layout` (`layouts/app.blade.php`) | `layouts/app-bootstrap.blade.php` con navbar Bootstrap (`navbar navbar-expand-lg`) | P1 (se crea antes de migrar la primera vista) |
| `x-mensaje-alerta` | `alert alert-success` / `alert alert-danger` de Bootstrap, con `role="alert"` | P1 |
| `x-modal` (Alpine `open-modal`/`close-modal`) | `modal fade` nativo de Bootstrap (`data-bs-toggle`/`data-bs-target`) | P1 |
| `x-primary-button` / `x-secondary-button` / `x-danger-button` | `btn btn-primary btn-lg` / `btn btn-outline-secondary btn-lg` / `btn btn-danger btn-lg` | P1 |
| `x-input-label` / `x-text-input` / `x-input-error` | `form-label fs-5 fw-bold` / `form-control form-control-lg` / `invalid-feedback` (Bootstrap) | P1 |
| `x-ruta-jerarquia-locacion` (breadcrumb) | `breadcrumb` de Bootstrap (`<nav aria-label="breadcrumb"><ol class="breadcrumb">`), reutilizando el mismo helper `rutaJerarquiaTruncada()` del modelo `Locacion` (sin cambios de lógica) | P1 |

## P1 — Vistas de 001-jerarquia-locaciones, 002-gestion-contratos, 003-representantes-contrato, 004-condiciones-contrato-recibo

| Vista actual | Feature origen | Notas de migración |
|---|---|---|
| `resources/views/locaciones/index.blade.php` | 001 | Listado → `card`/tabla `table-responsive`; badge de "Alquilable" |
| `resources/views/locaciones/show.blade.php` | 001 | Detalle → `card` con `dl`/`dt`/`dd` dentro de `card-body`; botón eliminar dispara `modal` Bootstrap |
| `resources/views/locaciones/create.blade.php` | 001 | Formulario → `form-control form-control-lg`, `input-group` no aplica (sin montos) |
| `resources/views/locaciones/edit.blade.php` | 001 | Igual que create |
| `resources/views/contratos/index.blade.php` | 002 | Historial cronológico → `list-group` o tabla, badge de estado destacando el contrato activo |
| `resources/views/contratos/show.blade.php` | 002/004/009 | La vista más grande: costos, garantía, representantes, documentos — se migra como un único `card` con secciones (`hr`/`card` anidadas) |
| `resources/views/contratos/create.blade.php` | 002 | Formulario principal, incluye las 3 parciales de abajo |
| `resources/views/contratos/edit.blade.php` | 002 | Igual que create |
| `resources/views/contratos/partials/costos-fijos-contrato.blade.php` | 004 | `input-group` con prefijo `S/` de Bootstrap para cada costo |
| `resources/views/contratos/partials/garantia-contrato.blade.php` | 009 | `input-group` para montos, `form-select` para medio de entrega |
| `resources/views/contratos/partials/representantes-contrato.blade.php` | 003 | Tarjetas (`card`) por representante, `btn-group` para Principal/Quitar, modal de confirmación |
| `resources/views/contratos/partials/galeria-documentos.blade.php` | 002 | `card` con miniaturas en `row row-cols-*` |

## P2 — Vistas de 005-lecturas-medidor-recibo-periodo, 007-estado-envio-recibo, 009-garantia-contrato (resolución, ya cubierta en P1 vía `contratos/show`)

| Vista actual | Feature origen | Notas de migración |
|---|---|---|
| `resources/views/locaciones/lecturas/index.blade.php` | 005 | Tabla `table-responsive` de periodos con lectura anterior/actual/consumo |
| `resources/views/locaciones/lecturas/create.blade.php` | 005 | Formulario con lectura anterior informativa y actual editable |
| `resources/views/locaciones/lecturas/edit.blade.php` | 006 | Edición de lectura anterior trasladada (ver también P3) |
| `resources/views/locaciones/recibos/index.blade.php` | 005/007 | Listado filtrable por estado, badges `bg-warning`/`bg-success`/`bg-danger` |
| `resources/views/locaciones/recibos/create.blade.php` | 005 | Generador de recibo con conceptos editables (`input-group` S/) |
| `resources/views/locaciones/recibos/edit.blade.php` | 005/008 | Igual, incluye sugerencia de prorrateo |
| `resources/views/locaciones/recibos/show.blade.php` | 007 | Detalle con `btn-group`/`btn-check` para cambiar estado, botones WhatsApp/Imprimir |
| `resources/views/locaciones/recibos/comprobante.blade.php` | 007 | Vista de impresión/imagen (html2canvas) — conserva su CSS de impresión propio, ver `research.md` de 007 |

## P3 — Vistas de 006-historial-lectura-medidor (+ gráfico nuevo) y 008-prorrateo-alertas-pago

| Vista actual | Feature origen | Notas de migración |
|---|---|---|
| (tabla de historial, hoy parte de `locaciones/lecturas/index.blade.php`) | 006 | Se separa o se extiende con un `<canvas>` Chart.js arriba de la tabla histórica (FR-005) |
| `resources/views/configuracion/edit.blade.php` | 004/008 | Formulario de configuración general (tarifa luz, alerta de pago) → `form-control`/`input-group` |

## Vistas fuera de alcance (no pertenecen a las features 001-009, no se tocan en esta migración)

- `resources/views/auth/*` (scaffolding de autenticación de Laravel Breeze)
- `resources/views/profile/*`
- `resources/views/welcome.blade.php`, `resources/views/dashboard.blade.php`
- `resources/views/emails/*` (plantillas de correo, tienen su propio CSS inline por convención de email)
