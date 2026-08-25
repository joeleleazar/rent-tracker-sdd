# Contrato de Interfaz: Vista de Árbol Jerárquico de Locaciones

**Feature**: `013-arbol-jerarquico-locaciones` | **Date**: 2026-08-23 | **Revisado**: 2026-08-23 (tabla indentada + campo Tipo)

Aplicación monolítica Laravel con vistas Blade server-rendered. Ruta protegida por `middleware(['auth'])`.

## Rutas

| Método | Ruta | Controlador@acción | Descripción | Cambio |
|---|---|---|---|---|
| GET | `/locaciones` | `LocacionController@index` | Renderiza la tabla jerárquica indentada completa (todas las locaciones, alquilables y contenedoras) | Sin cambios en esta revisión respecto a la iteración anterior (solo cambia la vista renderizada, no la ruta ni el controlador) |
| GET | `/dashboard` | Closure en `routes/web.php` | Redirige a `route('locaciones.index')` | Sin cambios en esta revisión |
| GET | `/locaciones/crear` | `LocacionController@create` | Formulario de creación; **acepta el query param opcional `locacion_padre_id`** para preseleccionar la locación padre | **Modificada**: antes no leía ningún query param |

Sin cambios en el resto de rutas de `locaciones.*` (`store`, `show`, `edit`, `update`, `destroy`).

## Contrato del servicio de construcción del árbol

`ServicioConstruccionArbolLocaciones::construir(): array<int, NodoArbol>` — **sin cambios** respecto a la iteración anterior (ver `data-model.md`).

## Contrato del componente Blade recursivo

`resources/views/locaciones/partials/fila-arbol-locacion.blade.php` (reemplaza a `nodo-arbol-locacion.blade.php`)

**Datos esperados** (pasados vía `@include` recursivo):

| Variable | Tipo | Descripción |
|---|---|---|
| `$locacion` | `App\Models\Locacion` | La locación representada por esta fila |
| `$hijos` | `array<int, NodoArbol>` | Sub-árbol de locaciones hijas ya construido (posiblemente vacío) |
| `$profundidad` | `int` | Nivel de anidamiento (0 para raíces), usado para calcular la indentación (`padding-left`) |

**Salida renderizada** (una fila de tabla vía CSS Grid, columnas Nombre/Locación · Estado · Tipo · Acciones):
- **Columna Nombre/Locación**: control de expandir/contraer (`bi-chevron-right`/`bi-chevron-down`, solo si `$hijos` no está vacío) + ícono de `Locacion::TIPOS[$locacion->tipo]['icono']` (o un ícono neutro si `tipo` es `null`) + nombre de la locación (texto, no enlace — la fila ya no navega directamente; ver Acciones).
- **Columna Estado**: `badge` `text-bg-success` ("Alquilable") si `$locacion->es_alquilable`, o `text-bg-secondary` ("No Alquilable") en caso contrario (FR-003).
- **Columna Tipo**: etiqueta de `Locacion::TIPOS[$locacion->tipo]['etiqueta']`, o "Sin tipo" si `tipo` es `null` (Edge Case de spec.md).
- **Columna Acciones**: botón "+" (`route('locaciones.create', ['locacion_padre_id' => $locacion->id])`, FR-011) y botón "Editar" (`route('locaciones.edit', $locacion)`).
- Si `$hijos` no está vacío: un contenedor `collapse` (`id="hijos-locacion-{id}"`) envuelve las filas hijas, cada una renderizada recursivamente con `$profundidad + 1` (FR-006).
- La tabla completa vive dentro de un contenedor con `overflow-x: auto` propio (FR-007).

## Contrato del formulario de creación (`locaciones/create.blade.php`)

- Nuevo campo `<select name="tipo">` con las opciones de `Locacion::TIPOS`, `old('tipo')` para repoblar tras error de validación.
- Si la petición llegó con `?locacion_padre_id=X`, el `<select name="locacion_padre_id">` preselecciona esa opción por defecto (`old('locacion_padre_id', $locacionPadreId)`), sin alterar el resto del formulario ni su validación.

## Form Requests (validación de entrada)

- `SolicitudGuardarLocacion`: se agrega `'tipo' => ['required', 'in:galeria,piso,sector,pasillo,local']`.

## Errores y mensajes

- Sin cambios respecto a la iteración anterior: los mensajes de éxito/error ya existentes de `locaciones.store`/`update`/`destroy` se preservan.
