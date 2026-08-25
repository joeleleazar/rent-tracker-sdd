# Contratos de Interfaz: Rutas web de Contratos y Documentos

**Feature**: `002-gestion-contratos` | **Date**: 2026-08-19

Aplicación monolítica Laravel con vistas Blade server-rendered (sin API JSON pública). Las siguientes rutas web (`routes/web.php`), protegidas por sesión autenticada de Administrador (`middleware(['auth'])`), son el contrato entre los controladores y las vistas/tests. Todas las rutas mutantes exigen token CSRF (comportamiento por defecto de Laravel).

## Contratos

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/locaciones/{locacion}/contratos` | `ContratoController@index` | Historial cronológico de contratos de una locación (US3) | 200, vista con listado, contrato activo destacado |
| GET | `/locaciones/{locacion}/contratos/crear` | `ContratoController@create` | Formulario de nuevo contrato | 200, formulario accesible |
| POST | `/locaciones/{locacion}/contratos` | `ContratoController@store` | Crea un contrato (US1) | 302 redirect a `show` en éxito; 422 + errores persistentes si hay solapamiento (FR-003) o validación fallida |
| GET | `/contratos/{contrato}` | `ContratoController@show` | Detalle del contrato + galería/preview de documentos | 200 |
| GET | `/contratos/{contrato}/editar` | `ContratoController@edit` | Formulario de edición (incluye cambio de estado a "rescindido") | 200 |
| PUT/PATCH | `/contratos/{contrato}` | `ContratoController@update` | Actualiza datos/estado del contrato | 302 en éxito; 422 en validación/solapamiento fallidos |

**Validación de solapamiento (FR-003)**: `store`/`update` delegan en `ServicioValidacionSolapamientoContrato` dentro de `DB::transaction`; en caso de conflicto, la respuesta 422 MUST incluir un mensaje explícito y persistente (no un toast que desaparece) identificando el contrato en conflicto.

## Documentos de Contrato

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| POST | `/contratos/{contrato}/documentos` | `DocumentoContratoController@store` | Sube PDF (máx. 15MB) o hasta 10 imágenes (máx. 5MB c/u) | 302 en éxito con indicador de éxito persistente; 422 si excede límites de tamaño/cantidad o mezcla tipos |
| GET | `/contratos/{contrato}/documentos/{documento}` | `DocumentoContratoController@show` | Transmite el archivo (streaming autenticado, sin URL pública) para previsualización/descarga | 200 con `Content-Type` apropiado; 403 si el usuario no está autenticado como Administrador |
| DELETE | `/contratos/{contrato}/documentos/{documento}` | `DocumentoContratoController@destroy` | Elimina un documento (requiere confirmación explícita en la UI, FR-005) | 302 en éxito; borra archivo físico + registro en la misma transacción |

## Form Requests (validación de entrada)

- `SolicitudGuardarContrato` (`store`/`update` de `ContratoController`): valida `locacion_id`, `inquilino_id`, `fecha_inicio`, `fecha_fin` (`fecha_fin >= fecha_inicio`), `monto_renta` (numérico, > 0), `estado`.
- `SolicitudSubirDocumentoContrato` (`store` de `DocumentoContratoController`): valida `archivo_pdf` (mimes:pdf, max:15360) XOR `archivo_imagenes.*` (mimes:jpg,png, max:5120, max 10 elementos).

## Errores y mensajes

- Todo error de validación se muestra junto al campo y en un resumen superior persistente (no desaparece automáticamente), con tipografía ≥18px y contraste WCAG AA/AAA, conforme al Principio III de la Constitución.
