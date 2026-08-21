# Research: Registro de Garantía Entregada por Contrato

**Feature**: `009-garantia-contrato` | **Date**: 2026-08-20

## 1. Columnas 1-a-1 en `contratos` vs. tabla `garantias` separada

**Decision**: Los 8 campos de garantía y su resolución (`monto_garantia`, `fecha_entrega_garantia`, `medio_entrega_garantia`, `estado_garantia`, `monto_devuelto_garantia`, `monto_retenido_garantia`, `motivo_retencion_garantia`, `fecha_resolucion_garantia`) se agregan como columnas nuevas directamente en `contratos` mediante migración de alteración, no como una tabla `garantias` separada.

**Rationale**: La Asunción A-001 de la especificación es explícita: "un contrato tiene, como máximo, un único registro de garantía... no se contempla en esta iteración el registro de garantías entregadas en múltiples partes o cuotas", y A-003 descarta un historial de auditoría de cambios. Es exactamente el mismo patrón de relación 1-a-1 sin historial ya resuelto y justificado en `specs/004-condiciones-contrato-recibo/research.md` §1 para los costos fijos del contrato: normalizar en una tabla aparte solo tendría sentido si existiera una relación 1-a-N (cuotas, historial), que la especificación descarta explícitamente.

**Alternatives considered**:
- Tabla `garantias` 1-a-1: rechazada por las mismas razones que en `specs/004` §1 — sobre-normaliza una relación que la propia especificación fija como uno-a-uno y sin historial.
- Tabla `garantias` con historial de cambios (auditoría): rechazada explícitamente por la Asunción A-003 ("no se mantiene un historial de auditoría de cambios... en esta iteración").

## 2. Validación de cuadre exacto (FR-008) y motivo obligatorio (FR-007)

**Decision**: `ServicioResolucionGarantiaContrato::registrar(Contrato $contrato, float $montoDevuelto, float $montoRetenido, ?string $motivo): void`, ejecutado dentro de `DB::transaction`: (a) rechaza si `bccomp($montoDevuelto + $montoRetenido, $contrato->monto_garantia, 2) !== 0` (comparación exacta con 2 decimales, evitando errores de coma flotante), lanzando una excepción de dominio que el controlador traduce a 422 con el mensaje de discrepancia; (b) rechaza si `$montoRetenido > 0` y `$motivo` está vacío; (c) si ambas validaciones pasan, persiste los montos, el motivo, marca `estado_garantia = 'resuelta'` y asigna `fecha_resolucion_garantia = now()`.

**Rationale**: Centralizar ambas reglas en un Service (no solo en el Form Request) es necesario porque la validación de cuadre depende de un valor ya persistido (`Contrato.monto_garantia`), no solo de los campos del formulario de resolución — el Form Request puede validar formato y presencia, pero la comparación aritmética exacta contra el monto de garantía ya guardado requiere cargar el modelo, consistente con el patrón ya usado en `ServicioValidacionSolapamientoContrato` (specs/002) y `ServicioCambioEstadoRecibo` (specs/007). Se usa `bccomp()` (o `Number::compare` equivalente) en vez de `===` sobre floats para evitar falsos negativos por representación binaria de decimales (ej. `0.1 + 0.2 !== 0.3` en PHP puro), reforzando el Principio V (precisión numérica exacta).

**Alternatives considered**:
- Validar el cuadre únicamente en el Form Request con una regla `after()` que compare contra el modelo cargado: viable parcialmente, pero se prefiere el Service porque la misma regla debe aplicarse también si la resolución se registra por otra vía en el futuro (ej. una API interna), evitando duplicar la comparación aritmética en dos capas.
- Comparar montos con `==` sobre floats de PHP sin `bccomp`/`Number`: rechazado, riesgo de falsos negativos por precisión de punto flotante en sumas de decimales (Principio V exige tipos exactos).

## 3. Confirmación explícita antes de corregir una resolución ya registrada (FR-010)

**Decision**: `ContratoController@registrarResolucionGarantia` acepta un parámetro `confirmado` (booleano) en el body; si `Contrato.estado_garantia === 'resuelta'` (es decir, ya existe una resolución previa) y `confirmado !== true`, la acción rechaza con 422 sin persistir nada, y la vista muestra el modal de confirmación Senior-First antes de reintentar la petición con `confirmado = true`. Este es el mismo patrón ya usado en `ServicioCambioEstadoRecibo` (`specs/007`) para las transiciones hacia/desde "anulado".

**Rationale**: Reutilizar el patrón de "confirmación explícita vía parámetro booleano validado en el Service/controlador" mantiene consistencia arquitectónica con `specs/007`, en vez de introducir un mecanismo distinto (ej. un token de confirmación de un solo uso) para un caso de uso equivalente en espíritu (una acción de re-edición sensible que requiere alta visibilidad, Principio III).

**Alternatives considered**:
- Bloquear permanentemente la edición de una resolución ya registrada (sin posibilidad de corrección): rechazado, la especificación exige explícitamente permitir la corrección tras confirmación (US3, Acceptance Scenario 4), no un bloqueo total.

## 4. Ausencia de roles/permisos

**Decision**: Consistente con `specs/001-008`, solo `middleware(['auth'])`.

**Rationale**: Ver `specs/004/research.md` §5.

**Alternatives considered**: Ninguna nueva.

## 5. Framework de pruebas

**Decision**: Pest, consistente con `specs/001-008`.

**Rationale**: Ya adoptado por el proyecto.

**Alternatives considered**: Ninguna — decisión ya tomada a nivel de proyecto.
