# Contrato de Interfaz: Rutas web de Inquilinos de Contrato

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20 | **Revisado**: 2026-08-23

Aplicación monolítica Laravel con vistas Blade server-rendered, consistente con `specs/001` y `specs/002`. Rutas protegidas por `middleware(['auth'])`; todas las rutas mutantes exigen CSRF.

**Nota**: Este contrato reemplaza a `rutas-representante.md` (eliminado). No existe un concepto de "representante" separado del inquilino; todas las rutas y controladores operan sobre `Inquilino`.

## Directorio global de inquilinos

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/inquilinos/buscar` | `InquilinoController@buscar` | Busca inquilinos existentes por DNI o apellidos (FR-007), para autocompletar/seleccionar desde el formulario de contrato | 200 con resultados (fragmento parcial htmx, no API pública) |
| POST | `/inquilinos` | `InquilinoController@store` | Registra un nuevo inquilino en el directorio global | 302/200 según contexto de invocación (embebido en formulario de contrato); 422 si DNI duplicado, menor de edad, o campos faltantes |

## Asociación de inquilinos a un contrato

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| POST | `/contratos/{contrato}/inquilinos` | `ContratoController@agregarInquilino` | Asocia un inquilino (existente o recién creado) a un contrato, opcionalmente marcándolo Principal | 302 en éxito; 422 si viola "exactamente un Principal" |
| DELETE | `/contratos/{contrato}/inquilinos/{inquilino}` | `ContratoController@quitarInquilino` | Desasocia un inquilino de un contrato (requiere confirmación explícita en la UI, FR-005) | 302 en éxito; 422 si es el único inquilino del contrato (FR-004) o si es el Principal y no se designó un reemplazo (FR-009) |

**Validación al guardar el contrato (FR-003)**: `ContratoController@store`/`@update` (de `specs/002`, ya sin el campo `inquilino_id`) delegan en `ServicioAsociacionInquilinosContrato::sincronizar()` dentro de la misma `DB::transaction` que ya usa `ServicioValidacionSolapamientoContrato`; si no hay inquilinos o no hay exactamente un Principal, la respuesta 422 MUST mostrar "Debe asociar por lo menos un inquilino al contrato antes de guardar" o el mensaje correspondiente de Principal, de forma persistente y con contraste alto.

## Form Requests (validación de entrada)

- `SolicitudGuardarInquilino` (`store` de `InquilinoController`, reemplaza a `SolicitudGuardarRepresentante`): valida `apellidos`/`nombres` (string, obligatorio), `dni` (string, 8 dígitos, `unique:inquilinos,dni` salvo que se esté reutilizando el mismo registro), `fecha_nacimiento` (date, `before_or_equal` a hoy menos 18 años).
- `SolicitudGuardarContrato` (de `specs/002`): se elimina la regla `inquilino_id` (`required|integer|exists:inquilinos,id`); la validación de inquilinos asociados pasa a `ServicioAsociacionInquilinosContrato`.

## Errores y mensajes

- Todo error de validación se muestra junto al campo y en un resumen superior persistente, con contraste WCAG AA (Principio III).
- Quitar un inquilino MUST mostrar un modal de confirmación con botones "Sí, quitar inquilino" / "No, cancelar" (Acceptance Scenario de US2), antes de invocar la ruta DELETE.

## Rutas y componentes eliminados por esta corrección

- `GET /representantes/buscar`, `POST /representantes`, `POST /contratos/{contrato}/representantes`, `DELETE /contratos/{contrato}/representantes/{representante}`.
- `RepresentanteController`, `SolicitudGuardarRepresentante`, `SolicitudAsociarRepresentante`, `ServicioAsociacionRepresentantesContrato`.
