# Contrato de Interfaz: Rutas web de Locaciones

**Feature**: `001-jerarquia-locaciones` | **Date**: 2026-08-20

Aplicación monolítica Laravel con vistas Blade server-rendered (sin API JSON pública), consistente con `specs/002-gestion-contratos/contracts/rutas-contrato.md`. Las siguientes rutas web (`routes/web.php`), protegidas por sesión autenticada (`middleware(['auth'])`), son el contrato entre `LocacionController` y las vistas/tests. Todas las rutas mutantes exigen token CSRF (comportamiento por defecto de Laravel).

## Locaciones

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/locaciones` | `LocacionController@index` | Listado filtrable de locaciones alquilables (FR-005), con contexto jerárquico truncado (FR-004) | 200, vista con listado |
| GET | `/locaciones/crear` | `LocacionController@create` | Formulario de nueva locación (selector de padre opcional) | 200, formulario Senior-First |
| POST | `/locaciones` | `LocacionController@store` | Crea una locación (US2) | 302 redirect a `show` en éxito; 422 + errores persistentes si hay ciclo (FR-003) o validación fallida |
| GET | `/locaciones/{locacion}` | `LocacionController@show` | Detalle de la locación + breadcrumb completo truncado (US1) | 200 |
| GET | `/locaciones/{locacion}/editar` | `LocacionController@edit` | Formulario de edición | 200 |
| PUT/PATCH | `/locaciones/{locacion}` | `LocacionController@update` | Actualiza datos/padre/es_alquilable de la locación | 302 en éxito; 422 en validación/ciclo/cambio de es_alquilable bloqueado |
| DELETE | `/locaciones/{locacion}` | `LocacionController@destroy` | Elimina la locación (US3, Edge Case) | 302 en éxito; 422 con mensaje explícito si tiene sub-locaciones asociadas (FR-007) |

**Validación de ciclos (FR-003)**: `store`/`update` delegan en `ServicioValidacionJerarquiaLocacion` dentro de `DB::transaction`; en caso de ciclo detectado, la respuesta 422 MUST incluir el mensaje "No se puede asignar una locación hija como padre" (consistente con el Acceptance Scenario de US3).

**Bloqueo de eliminación (FR-007)**: `destroy` MUST verificar `locacionesHijas()->exists()` antes de intentar el borrado; si existen hijas, responde 422 con un mensaje explícito y persistente, sin ejecutar el `DELETE`.

## Form Requests (validación de entrada)

- `SolicitudGuardarLocacion` (`store`/`update` de `LocacionController`): valida `nombre` (string, obligatorio), `tamano` (numérico, obligatorio, > 0), `ubicacion_fisica` (string, obligatorio), `descripcion` (string, obligatorio), `locacion_padre_id` (entero, nullable, debe existir en `locaciones`), `es_alquilable` (boolean).

## Errores y mensajes (Senior-First)

- Todo error de validación se muestra junto al campo y en un resumen superior persistente (no desaparece automáticamente), con tipografía ≥18px y contraste WCAG AA/AAA, conforme al Principio III de la Constitución.
- El bloqueo de eliminación con sub-locaciones asociadas MUST mostrarse en una pantalla/modal de confirmación con lenguaje claro, antes incluso de intentar la acción, conforme al Principio III ("Prevención de Errores y Confirmaciones Explícitas").
