# Feature Specification: Columna de Consumo y Alineación del Ícono de Completado en Registro Masivo

**Feature Branch**: `017-columna-consumo-lecturas`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "Agregar una columna al registro de lecturas que indique de cuanto fue el consumo para ese periodo, restando la lectura actual con la lectura anterior y el span del check verde aparezca a la izquierda de la lectura actual para que se vea alineado"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver el consumo de cada locación sin calcularlo a mano (Priority: P1)

Un usuario que revisa la pantalla de registro masivo de lecturas de luz necesita ver, junto a cada
locación, cuántas unidades se consumieron en el periodo (la diferencia entre la lectura actual y la
lectura del periodo anterior), sin tener que restar los dos valores mentalmente ni fila por fila.
Hoy ese número existe y ya se calcula (el sistema lo usa para el "Total" en soles y ya aparece en
las exportaciones a Excel/PDF de esta misma pantalla como columna "Consumo (kWh)"), pero no se
muestra en la pantalla interactiva — el usuario solo ve el resultado final en soles, no las
unidades consumidas.

**Why this priority**: Es la capacidad central pedida y corrige una inconsistencia ya existente:
la exportación de esta misma pantalla ya expone el consumo como columna propia, pero la pantalla
en vivo no, obligando al usuario a exportar solo para ver un dato que el sistema ya calculó.

**Independent Test**: Abrir el registro masivo para un periodo donde al menos una locación tenga
lectura anterior y lectura actual registradas, verificar que aparece una columna "Consumo" con el
valor exacto de esa diferencia, y confirmar que coincide con el valor ya presente en la columna
"Consumo (kWh)" de la exportación a Excel/PDF de la misma pantalla.

**Acceptance Scenarios**:

1. **Given** una locación con lectura del periodo anterior y lectura ya guardada del periodo
   seleccionado, **When** el usuario ve su fila, **Then** la columna "Consumo" muestra exactamente
   la diferencia entre ambas lecturas, igual al valor ya expuesto en la exportación de la misma
   pantalla.
2. **Given** una locación sin ninguna lectura de un periodo anterior, **When** el usuario ve su
   fila, **Then** la columna "Consumo" indica claramente que no se puede calcular (no un valor
   vacío ambiguo ni un cero engañoso).
3. **Given** una locación con lectura anterior pero todavía sin lectura guardada del periodo
   seleccionado, **When** el usuario escribe un valor en el campo "Lectura Actual" sin guardarlo
   todavía, **Then** la columna "Consumo" se actualiza en el momento reflejando ese valor tipeado,
   igual que ya ocurre hoy con la columna "Total".
4. **Given** una locación sin lectura anterior ni valor tipeado todavía, **When** el usuario ve su
   fila, **Then** la columna "Consumo" muestra el mismo indicador de "sin dato" que ya usa hoy la
   columna "Total" en ese mismo caso.

---

### User Story 2 - Ver el ícono de lectura completada alineado con el valor (Priority: P2)

Un usuario que recorre la columna "Lectura Actual" del registro masivo necesita que el ícono verde
que marca una lectura ya guardada aparezca siempre en el mismo lugar respecto del valor, para poder
escanear la columna de un vistazo. Hoy ese ícono aparece después del valor (a la derecha), lo que
hace que el valor numérico de las filas completadas no quede alineado con el de las filas todavía
pendientes (que muestran un campo de entrada empezando desde el borde izquierdo de la celda).

**Why this priority**: Es una mejora de legibilidad visual, valiosa por sí sola, pero de menor
impacto que ver el dato de consumo de la User Story 1 — no cambia ningún dato mostrado, solo su
posición.

**Independent Test**: Ver una fila con una lectura ya guardada y confirmar que el ícono verde
aparece inmediatamente a la izquierda del valor, no después; confirmar que el comportamiento de
edición al hacer clic en el ícono sigue funcionando igual que antes.

**Acceptance Scenarios**:

1. **Given** una locación con una lectura ya guardada para el periodo seleccionado, **When** el
   usuario ve su fila, **Then** el ícono verde de confirmación aparece inmediatamente a la
   izquierda del valor de la lectura, no a su derecha.
2. **Given** una fila en ese estado, **When** el usuario hace clic sobre el ícono para editar la
   lectura, **Then** la fila cambia a modo edición exactamente igual que antes del reacomodo — solo
   cambió la posición visual del ícono, no su función ni su accesibilidad.

---

### Edge Cases

- ¿Qué pasa si el consumo calculado es negativo (un caso ya permitido por specs/015 cuando el
  usuario confirma explícitamente una lectura menor a la anterior)? La columna "Consumo" debe
  mostrar ese valor negativo tal cual, sin ocultarlo ni marcarlo como error — ya fue validado al
  guardarse.
- ¿Qué pasa si el usuario cambia el periodo seleccionado? El consumo mostrado para cada locación se
  recalcula en relación con el nuevo periodo y su propia lectura anterior correspondiente,
  consistente con la corrección ya definida en specs/016-correccion-registro-masivo-lecturas.
- ¿Qué pasa con el ícono cuando la fila entra en modo edición en línea? Vuelve a mostrar el layout
  de edición ya existente (campo editable + botones guardar/cancelar); el reacomodo del ícono
  aplica únicamente a la vista de solo lectura de una fila ya completada.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE mostrar, en la pantalla de registro masivo de lecturas, una columna
  adicional "Consumo" junto a cada locación alquilable, mostrando la diferencia entre su lectura
  actual y su lectura del periodo anterior para el periodo seleccionado.
- **FR-002**: Para una locación con lectura ya guardada del periodo seleccionado, el valor de
  "Consumo" DEBE coincidir exactamente con el consumo ya calculado y persistido para esa lectura
  (el mismo valor que ya aparece en la columna "Consumo (kWh)" de la exportación a Excel/PDF de
  esta misma pantalla).
- **FR-003**: Para una locación sin lectura guardada todavía pero con un valor tipeado en el campo
  "Lectura Actual" y con una lectura anterior disponible, el valor de "Consumo" DEBE recalcularse
  en el momento a medida que el usuario tipea, sin requerir guardar ni recargar la página.
- **FR-004**: Cuando no existe ninguna lectura de un periodo anterior para una locación, la columna
  "Consumo" DEBE indicar claramente que no se puede calcular, nunca un valor vacío ambiguo ni un
  cero que sugiera consumo nulo.
- **FR-005**: Cuando una locación todavía no tiene lectura guardada ni valor tipeado, la columna
  "Consumo" DEBE mostrar el mismo indicador de "sin dato" que ya usa la columna "Total" existente
  en ese mismo caso, para mantener consistencia visual entre ambas columnas.
- **FR-006**: El sistema DEBE reposicionar el ícono verde que marca una lectura ya guardada para
  que aparezca inmediatamente a la izquierda del valor de la lectura, en vez de a su derecha como
  ocurre hoy, sin alterar su comportamiento de clic para editar ni sus atributos de accesibilidad
  ya existentes (etiqueta descriptiva, tooltip).

### Key Entities *(include if feature involves data)*

- **Lectura de Medidor**: entidad ya existente (specs/005, specs/006, specs/015); esta
  funcionalidad no agrega campos nuevos — el valor de consumo que se muestra ya se calcula y
  persiste hoy (`consumo_calculado`), solo se expone también en pantalla además de en las
  exportaciones.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las locaciones con lectura anterior y lectura actual guardadas muestran,
  en la pantalla de registro masivo, un valor de "Consumo" idéntico al que ya expone la
  exportación a Excel/PDF de la misma pantalla para esa fila.
- **SC-002**: El 100% de las locaciones sin lectura anterior disponible muestran el indicador de
  "sin dato" en la columna "Consumo", nunca un valor numérico vacío o cero sin aclaración.
- **SC-003**: En una revisión visual de todas las filas con lectura ya guardada, el 100% muestran
  el ícono verde inmediatamente a la izquierda del valor, nunca a su derecha.
- **SC-004**: Un usuario puede identificar el consumo de cualquier locación con lectura anterior y
  actual disponibles sin salir de la pantalla de registro masivo ni exportar ningún archivo.

## Assumptions

- La columna "Consumo" se ubica entre "Lectura Actual" y "Total", reflejando el mismo orden y
  encabezado ("Consumo (kWh)") que ya usa la exportación a Excel/PDF de esta pantalla — no se
  introduce un orden ni una nomenclatura nueva.
- El formato numérico del valor de consumo sigue la misma precisión (2 decimales) ya usada para
  las lecturas y para la exportación existente.
- El reacomodo del ícono verde aplica únicamente a la celda de "Lectura Actual" del registro
  masivo (specs/015/016) — es el único lugar de la aplicación donde este ícono de lectura
  completada aparece hoy.
- El recálculo en vivo del consumo mientras el usuario tipea reutiliza la misma lógica de cálculo
  del lado del cliente que ya alimenta hoy la columna "Total", sin requerir una petición nueva al
  servidor.
