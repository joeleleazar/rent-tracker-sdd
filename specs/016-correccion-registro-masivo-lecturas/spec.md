# Feature Specification: Corrección de Lectura Previa y Autoguardado en Registro Masivo

**Feature Branch**: `016-correccion-registro-masivo-lecturas`

**Created**: 2026-08-25

**Status**: Superseded parcialmente por specs/020-correccion-exportar-periodo-icono (ver nota debajo)

**Input**: User description: "Corregir dos funciones defectuosas en la pantalla de registro masivo de lecturas de luz (specs/015-registro-masivo-lecturas, ya implementada): (1) la función que obtiene y muestra la lectura del periodo anterior de cada locación no está funcionando correctamente; (2) el guardado automático del borrador cada 2 minutos no está funcionando correctamente. Ambos comportamientos ya están definidos como requisitos en el spec existente (FR-006 para la lectura anterior, FR-010/FR-011 para el autoguardado y su restauración) — el pedido es que esa funcionalidad ya especificada vuelva a comportarse como se especificó, no un cambio de alcance ni de comportamiento esperado."

## Nota de Estado (2026-08-25)

Esta spec llegó hasta `/speckit-plan` (spec.md + plan.md + research.md + contracts/ + quickstart.md)
pero nunca tuvo su propio `tasks.md` ni implementación bajo el nombre `016`. Se retoma y se cierra
aquí retroactivamente para no dejarla colgando sin resolución documentada:

- **User Story 1** (lectura del periodo anterior incorrecta al cambiar de periodo): el defecto era
  real. Se identificó y corrigió más tarde bajo `specs/020-correccion-exportar-periodo-icono` (su
  User Story 1, que cita explícitamente esta historia) — causa raíz: el botón "Cambiar Periodo" no
  declaraba `type="submit"`, por lo que el formulario nunca se reenviaba con el nuevo periodo y la
  columna "Lectura Periodo Anterior" seguía calculada contra el periodo viejo. Ver
  `specs/020-correccion-exportar-periodo-icono/tasks.md`. **Resuelta.**
- **User Story 2** (autoguardado cada 120s no confiable): la investigación de esta misma spec
  (`research.md`, Hallazgo H2) concluyó que el código de producción (`hx-trigger="every 120s"`,
  `hx-include`) ya coincidía exactamente con lo especificado en specs/015 — no se encontró ninguna
  discrepancia real, solo que ningún test de servidor podía detectar una regresión futura en esos
  atributos (Pest no ejecuta htmx/JS de navegador). La prueba de "contrato HTML" propuesta como
  mitigación (H2) **no llegó a implementarse bajo ninguna spec** — sigue siendo un hueco de
  cobertura de test legítimo, no un defecto de comportamiento confirmado. Ver `tasks.md` de esta
  misma spec para el detalle.

Esta spec se enmendó también (ver `contracts/lectura-anterior-y-autoguardado.md` y `quickstart.md`)
para reflejar el criterio "0" en vez de "Sin lectura previa registrada" adoptado después por
specs/019/021 — ver `specs/006-historial-lectura-medidor/spec.md`, "Actualización (2026-08-25)".

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver la lectura del periodo anterior correcta al completar el registro masivo (Priority: P1)

Un usuario que está completando el registro masivo de lecturas necesita ver, junto al campo de cada locación, el valor real de su lectura del periodo inmediatamente anterior (o una indicación clara de que no existe ninguna), para poder detectar un error de tipeo antes de guardar. Hoy ese valor no se muestra de forma confiable — en ciertos casos aparece incorrecto, vacío cuando sí existe una lectura anterior, o desalineado del periodo/locación que corresponde — lo que le hace perder la única referencia visual que tiene contra errores mientras recorre varias locaciones seguidas.

**Why this priority**: Es la funcionalidad que sostiene la verificación visual ya prometida por la User Story 2 de specs/015-registro-masivo-lecturas; si el valor mostrado no es confiable, el usuario pierde esa salvaguarda en cada fila de cada visita a la pantalla, no solo en un caso aislado.

**Independent Test**: Para una locación con una lectura registrada en el periodo inmediatamente anterior al seleccionado, abrir la pantalla de registro masivo y verificar que el valor mostrado junto a su campo coincide exactamente con el valor real de esa lectura; repetir para una locación sin ninguna lectura previa y verificar que se indica claramente la ausencia, nunca un valor vacío ambiguo ni el de otra locación o periodo.

**Acceptance Scenarios**:

1. **Given** una locación con una lectura registrada en el periodo inmediatamente anterior al periodo seleccionado, **When** el usuario abre la pantalla de registro masivo, **Then** el valor mostrado como "lectura del periodo anterior" de esa locación es exactamente el valor de esa lectura registrada.
2. **Given** una locación sin ninguna lectura registrada en ningún periodo anterior, **When** el usuario ve su fila, **Then** se indica claramente que no hay lectura previa, nunca un valor vacío ambiguo ni un valor incorrecto.
3. **Given** una locación con lecturas registradas en varios periodos anteriores distintos, **When** el usuario ve su fila, **Then** el valor mostrado corresponde al periodo más reciente entre los anteriores al seleccionado, no a un periodo más antiguo ni a uno posterior.
4. **Given** el usuario cambia el periodo seleccionado a otro mes, **When** la pantalla se recarga con las locaciones de ese nuevo periodo, **Then** la lectura del periodo anterior mostrada para cada locación se recalcula en relación con el nuevo periodo seleccionado, no con el periodo que estaba seleccionado antes del cambio.

---

### User Story 2 - Confiar en que el autoguardado cada 2 minutos protege el trabajo en curso (Priority: P2)

Un usuario que deja la pantalla de registro masivo abierta mientras completa varias locaciones necesita que sus valores se guarden automáticamente como borrador cada 2 minutos, tal como ya se especificó en specs/015-registro-masivo-lecturas (User Story 3), para no perder su trabajo si se interrumpe. Hoy ese autoguardado no se comporta de forma confiable, por lo que el usuario no puede confiar en que su trabajo esté protegido mientras la pantalla permanece abierta.

**Why this priority**: Protege contra la pérdida de trabajo ya tipeado, pero la pantalla de registro masivo (corregida por la User Story 1) ya entrega valor por sí sola sin este resguardo — de ahí su prioridad menor a P1, consistente con la prioridad ya asignada a esta misma protección en specs/015.

**Independent Test**: Completar algunas filas sin guardar, dejar la pantalla abierta durante varios ciclos de 2 minutos, cerrar la sesión o el navegador sin guardar el lote, y verificar que al volver a abrir la pantalla para el mismo periodo los valores ya ingresados están presentes.

**Acceptance Scenarios**:

1. **Given** el usuario tiene la pantalla de registro masivo abierta con algunas filas completadas sin guardar, **When** transcurren 2 minutos, **Then** esos valores quedan efectivamente guardados como borrador en el servidor, verificable consultando el borrador asociado a ese usuario y periodo.
2. **Given** la pantalla permanece abierta más de 2 minutos con cambios adicionales entre un ciclo y otro, **When** transcurre cada ciclo sucesivo de 2 minutos, **Then** el borrador se actualiza con los valores más recientes en cada ciclo, no solo en el primero.
3. **Given** un borrador guardado correctamente para un periodo, **When** el usuario vuelve a abrir la pantalla de registro masivo para ese mismo periodo, **Then** los valores del borrador se restauran automáticamente en los campos correspondientes, sin pasos adicionales.

---

### Edge Cases

- ¿Qué pasa si una locación tiene lecturas registradas en periodos no consecutivos (por ejemplo, con un mes salteado sin lectura)? La lectura previa mostrada debe seguir siendo la del periodo más reciente que exista antes del seleccionado, sin exigir que los periodos sean consecutivos.
- ¿Qué pasa si el cambio de periodo seleccionado cruza un límite de año (por ejemplo, de enero a diciembre del año anterior)? El cálculo de la lectura previa debe seguir identificando correctamente el periodo inmediatamente anterior sin importar el cambio de año.
- ¿Qué pasa si el autoguardado se dispara pero la escritura al servidor falla (por ejemplo, sin conexión en ese momento)? Debe reintentarse en el siguiente ciclo de 2 minutos sin interrumpir al usuario ni mostrar un error bloqueante, igual que ya lo exige el Edge Case correspondiente de specs/015.
- ¿Qué pasa si el usuario permanece en la pantalla más de 2 minutos sin escribir ningún valor nuevo? El autoguardado no necesita producir un borrador vacío ni sobrescribir un borrador existente con menos datos de los que ya tenía.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE mostrar, junto al campo de cada locación en la pantalla de registro masivo, el valor exacto de su lectura del periodo inmediatamente anterior al periodo seleccionado, corrigiendo cualquier caso en el que hoy se muestre un valor vacío, incorrecto, o correspondiente a otra locación o periodo (corrige FR-006 de specs/015-registro-masivo-lecturas).
- **FR-002**: Cuando una locación no tiene ninguna lectura registrada en ningún periodo anterior al seleccionado, el sistema DEBE indicarlo claramente en vez de mostrar un valor vacío ambiguo o incorrecto.
- **FR-003**: El sistema DEBE recalcular correctamente la lectura del periodo anterior de cada locación cada vez que el usuario cambia el periodo seleccionado, en relación con el periodo recién seleccionado.
- **FR-004**: El sistema DEBE guardar automáticamente, cada 2 minutos mientras la pantalla de registro masivo permanece abierta, los valores ya ingresados y aún no confirmados, como borrador persistido en el servidor asociado al usuario y al periodo seleccionado, corrigiendo cualquier caso en el que hoy ese guardado automático no se dispare, se dispare sin persistir el valor, o deje de repetirse en ciclos sucesivos (corrige FR-010 de specs/015-registro-masivo-lecturas).
- **FR-005**: Al reabrir la pantalla de registro masivo para un periodo con un borrador existente, el sistema DEBE restaurar automáticamente esos valores en los campos correspondientes, sin requerir una acción adicional del usuario (corrige FR-011 de specs/015-registro-masivo-lecturas).

### Key Entities *(include if feature involves data)*

- **Lectura de Medidor**: entidad ya existente (specs/005, specs/015); esta corrección no cambia su forma, solo la exactitud del cálculo que determina cuál lectura anterior se muestra.
- **Borrador de Registro Masivo**: entidad ya existente (specs/015); esta corrección no cambia su forma ni su ciclo de vida, solo la fiabilidad de cuándo y cómo se escribe.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las locaciones alquilables con una lectura registrada en un periodo anterior muestran, al abrir la pantalla de registro masivo, el valor exacto de esa lectura previa, verificable comparando contra el registro real.
- **SC-002**: Un usuario que deja la pantalla de registro masivo abierta al menos 4 minutos sin guardar (dos ciclos completos de autoguardado) recupera automáticamente sus valores ya ingresados al reabrir la pantalla para el mismo periodo, sin haber realizado ninguna acción manual de guardado.
- **SC-003**: Cero locaciones muestran, en una revisión de todas las locaciones alquilables del sistema, la lectura previa de otra locación o de un periodo distinto al que corresponde.

## Assumptions

- Esta especificación corrige exclusivamente el comportamiento ya definido en specs/015-registro-masivo-lecturas (FR-006 para la lectura anterior; FR-010/FR-011 para el autoguardado y su restauración); no cambia el alcance de la pantalla de registro masivo ni introduce comportamiento nuevo respecto de lo ya especificado.
- El diagnóstico de la causa raíz de cada defecto (por qué el comportamiento actual no cumple lo ya especificado) se determina durante la fase de planificación/implementación, no en esta especificación.
- Las pruebas automatizadas ya existentes de specs/015 (`tests/Feature/RegistroMasivoLecturasControllerTest.php`) se amplían, si hace falta, para cubrir explícitamente los escenarios defectuosos reportados que no estuvieran ya cubiertos.
