# Contrato de Interfaz: Rutas web de Representantes

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20

Aplicación monolítica Laravel con vistas Blade server-rendered, consistente con `specs/001` y `specs/002`. Rutas protegidas por `middleware(['auth'])`; todas las rutas mutantes exigen CSRF.

## Directorio global de representantes

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| GET | `/representantes/buscar` | `RepresentanteController@buscar` | Busca representantes existentes por DNI o apellidos (FR-007), para autocompletar/seleccionar desde el formulario de contrato | 200 con resultados (fragmento parcial o JSON interno de la vista, no API pública) |
| POST | `/representantes` | `RepresentanteController@store` | Registra un nuevo representante en el directorio global | 302/200 según contexto de invocación (embebido en formulario de contrato); 422 si DNI duplicado, menor de edad, o campos faltantes |

## Asociación de representantes a un contrato

| Método | Ruta | Controlador@acción | Descripción | Respuesta esperada |
|---|---|---|---|---|
| POST | `/contratos/{contrato}/representantes` | `ContratoController@agregarRepresentante` | Asocia un representante (existente o recién creado) a un contrato, opcionalmente marcándolo Principal | 302 en éxito; 422 si viola "exactamente un Principal" |
| DELETE | `/contratos/{contrato}/representantes/{representante}` | `ContratoController@quitarRepresentante` | Desasocia un representante de un contrato (requiere confirmación explícita en la UI, FR-005) | 302 en éxito; 422 si es el único representante del contrato (FR-004) |

**Validación al guardar el contrato (FR-003)**: `ContratoController@store`/`@update` (de `specs/002`) delegan en `ServicioAsociacionRepresentantesContrato::sincronizar()` dentro de la misma `DB::transaction` que ya usa `ServicioValidacionSolapamientoContrato`; si no hay representantes o no hay exactamente un Principal, la respuesta 422 MUST mostrar "Debe asociar por lo menos un representante al contrato antes de guardar" o el mensaje correspondiente de Principal, de forma persistente y con contraste alto.

## Form Requests (validación de entrada)

- `SolicitudGuardarRepresentante` (`store` de `RepresentanteController`): valida `apellidos`/`nombres` (string, obligatorio), `dni` (string, 8 dígitos, `unique:representantes,dni` salvo que se esté reutilizando el mismo registro), `fecha_nacimiento` (date, `before_or_equal` a hoy menos 18 años).

## Errores y mensajes (Senior-First)

- Todo error de validación se muestra junto al campo y en un resumen superior persistente, tipografía ≥18px, contraste WCAG AA/AAA (Principio III).
- Quitar un representante MUST mostrar un modal de confirmación con botones "Sí, quitar representante" / "No, cancelar" (Acceptance Scenario de US2), antes de invocar la ruta DELETE.
