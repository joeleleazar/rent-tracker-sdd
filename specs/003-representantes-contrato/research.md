# Research: Inquilinos de Contrato (Inquilino Principal)

**Feature**: `003-representantes-contrato` | **Date**: 2026-08-20 | **Revisado**: 2026-08-23

## 1. Unificación de `Representante` e `Inquilino` (corrección del usuario, reemplaza la decisión anterior de coexistencia)

**Decision**: `Representante` deja de existir como entidad independiente. El `Inquilino` (ya existente desde `specs/002-gestion-contratos`, hoy con solo el campo `nombre` y relación 1:1 `Contrato.inquilino_id`) se **extiende** con los campos `apellidos`, `nombres`, `dni` (único) y `fecha_nacimiento` que antes vivían en `Representante`, y la relación con `Contrato` pasa de `belongsTo` 1:1 a `belongsToMany` muchos-a-muchos a través de una tabla pivote `contrato_inquilino` con columna `es_principal`. Un contrato exige como mínimo un inquilino y exactamente uno marcado como Principal. Se eliminan por completo: la tabla `representantes`, la tabla pivote `contrato_representante`, el modelo `Representante`, `RepresentanteController`, `ServicioAsociacionRepresentantesContrato`, las excepciones `ContratoSinRepresentantesException`/`RepresentantePrincipalInvalidoException`/`UltimoRepresentanteException`, y la columna `contratos.inquilino_id` (reemplazada por la fila del pivote con `es_principal = true`).

**Rationale**: El usuario corrigió explícitamente la especificación original: *"cuando me referia a representante era el inquilino, no hace falta tenerlo por separado"*. Esto anula la decisión previa de este mismo `research.md` (coexistencia de ambas entidades), que se basaba en una lectura de la spec 003 anterior a la corrección. Mantener dos tablas para la misma persona (inquilino que ocupa el local y "representante" que firma) introduciría duplicidad de datos y confusión conceptual que el propio usuario pidió evitar. La extensión de `Inquilino` (en vez de eliminar `Inquilino` y quedarse con `Representante`) se elige porque `inquilino_id` en `contratos` ya es la columna `NOT NULL` en producción/uso activo desde `specs/002`, y las vistas/controladores ya referencian `$contrato->inquilino->nombre` (ver `app/Http/Controllers/ContratoController.php`); es menos disruptivo enriquecer el modelo ya integrado que reemplazar sus referencias por un modelo nuevo.

**Alternatives considered**:
- Mantener ambas entidades como en la decisión previa de este documento: rechazado explícitamente por la corrección del usuario.
- Eliminar `Inquilino` y renombrar `Representante` a `Inquilino`: rechazado; requeriría reescribir todas las referencias ya integradas a `Contrato.inquilino`/`inquilino_id` de `specs/002`, mientras que extender el modelo existente preserva esas integraciones.
- Mantener `contratos.inquilino_id` además del pivote (como "inquilino de referencia rápida" desnormalizado): rechazado, generaría una segunda fuente de verdad sobre quién es el inquilino principal, contradiciendo la regla de "exactamente un Principal" que ya vive en el pivote.

**Migración de datos**: Dado que `representantes`, `contrato_representante`, `inquilinos` y `contratos.inquilino_id` ya existen como migraciones ejecutadas (feature 002 y una versión anterior de esta feature 003), la consolidación se implementa con **migraciones nuevas y aditivas** (nunca editar migraciones ya aplicadas, Principio I): (a) agregar `apellidos`, `nombres`, `dni` (nullable primero, `UNIQUE`), `fecha_nacimiento` a `inquilinos`; (b) crear `contrato_inquilino` (mismo shape que `contrato_representante`: `contrato_id`, `inquilino_id`, `es_principal`, único compuesto); (c) script/seeder de migración de datos: para cada `Contrato` existente, insertar una fila en `contrato_inquilino` con `inquilino_id = contratos.inquilino_id` y `es_principal = true`; para cada fila de `representantes`/`contrato_representante` sin un `inquilino` equivalente por DNI, crear o completar el `Inquilino` correspondiente y su fila de pivote (`es_principal` solo si no hay ya un principal para ese contrato); (d) eliminar `contratos.inquilino_id`, y las tablas `representantes`/`contrato_representante`, en una migración posterior una vez verificada la copia de datos.

## 2. Directorio global reutilizable de inquilinos (Edge Case, FR-007)

**Decision**: La tabla `inquilinos` (ya existente, ahora extendida) actúa como el directorio global; búsqueda por DNI o apellidos vía `LIKE`/`ILIKE` (PostgreSQL) en `InquilinoController@buscar`, devolviendo resultados para que el administrador seleccione uno existente o registre uno nuevo desde el mismo formulario de contrato.

**Rationale**: La especificación exige un "Directorio Reutilizable (Catálogo Global)" para evitar duplicidad de datos de una misma persona a través de múltiples contratos. Al unificar en `Inquilino`, este directorio ya no requiere una tabla nueva (`representantes`); reutiliza la tabla `inquilinos` existente. `ILIKE` de PostgreSQL permite búsqueda case-insensitive sin extensiones adicionales (`pg_trgm` queda como mejora futura opcional de rendimiento).

**Alternatives considered**:
- Registrar inquilinos de forma aislada por contrato (sin catálogo): rechazado explícitamente por la especificación.
- Búsqueda con `pg_trgm`/similaridad difusa: rechazado como dependencia dura no garantizada en shared hosting; mejora opcional futura si el volumen crece.

## 3. Regla "al menos uno" y "exactamente un Principal" (FR-003, FR-004, FR-009, Edge Cases)

**Decision**: `ServicioAsociacionInquilinosContrato::sincronizar(Contrato $contrato, array $inquilinos)` (reemplaza a `ServicioAsociacionRepresentantesContrato`) ejecuta dentro de `DB::transaction`: (a) rechaza si `count($inquilinos) === 0`; (b) rechaza si el conteo de `es_principal = true` en el arreglo no es exactamente 1; (c) al remover un inquilino individual (`quitar`), rechaza si es el único asociado al contrato; (d) rechaza remover al inquilino marcado Principal si existen otros inquilinos y no se designó simultáneamente un nuevo Principal (FR-009, nueva regla de la corrección). Esta validación se ejecuta tanto en la creación/edición completa del contrato como en la acción puntual de quitar un inquilino.

**Rationale**: La especificación corregida exige estas reglas como gates de guardado (FR-003) y de eliminación individual (FR-004, FR-009). Centralizar la lógica en un Service evita duplicar la validación entre el flujo de creación de contrato y el flujo de edición/quitar un inquilino desde la vista de detalle.

**Alternatives considered**:
- Validar solo en el Form Request: rechazado, la acción de "quitar inquilino" (US2) no pasa por el mismo Form Request que crea/edita el contrato completo, y debe validarse igualmente (Principio V, transacciones atómicas).

## 4. Validación de mayoría de edad y formato de DNI (FR-002, US3, Asunción A-001)

**Decision**: Regla de validación en `SolicitudGuardarInquilino` (reemplaza a `SolicitudGuardarRepresentante`): `dni` como cadena numérica de longitud fija (8 dígitos, formato DNI peruano estándar, ajustable si el usuario aclara otro formato), `fecha_nacimiento` con regla `before_or_equal` a la fecha actual menos 18 años. La regla de mayoría de edad se mantiene únicamente en la capa de aplicación (Form Request + Service), no como `CHECK` de base de datos, por las mismas razones de testabilidad documentadas originalmente.

**Rationale**: Sin cambios respecto a la decisión original de esta feature; solo cambia el nombre de la clase que la implementa (`Inquilino` en vez de `Representante`).

**Alternatives considered**: Ídem versión anterior de este documento (`CHECK` de base de datos único, DNI de longitud variable) — ambas rechazadas por las mismas razones.

## 5. Framework de pruebas

**Decision**: Pest, consistente con `specs/001` y `specs/002`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
