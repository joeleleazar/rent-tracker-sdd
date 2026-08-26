# Feature Specification: Consumo Calculado en el Momento en vez de Almacenado

**Feature Branch**: `021-derivar-consumo-calculado`

**Created**: 2026-08-25

**Status**: Draft

**Input**: User description: "Eliminar la columna consumo_calculado de lecturas_medidor: es derivable de lectura_actual - lectura_anterior (o 0 si no hay anterior, en registro masivo) y hoy se lee directamente en app/Console/Commands/ImportarLecturasMedidorHistoricas.php, resources/views/locaciones/lecturas/index.blade.php (historial individual), resources/views/locaciones/recibos/create.blade.php (form de generar recibo), y el registro masivo (specs/015/017/019). Reemplazar esos usos por un cálculo derivado (accessor o cálculo en el momento) en vez de una columna almacenada, ya que ahora el valor que realmente importa para facturación es "total" (specs/019), que sí sigue siendo persistido."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ver siempre el consumo correcto, sin importar cómo se registró la lectura (Priority: P1)

Un usuario que consulta el consumo de una locación — en su historial individual, en el formulario
de generación de recibo, o en el registro masivo — necesita que ese número sea siempre la
diferencia real entre la lectura actual y la anterior de esa misma lectura, sin que dependa de un
valor guardado por separado que podría quedar desactualizado si alguna vía de registro (individual,
masiva, o una importación histórica) lo calculó mal en su momento. Hoy el sistema ya tuvo dos
correcciones sobre esta misma pantalla en la misma sesión de trabajo (specs/016, specs/019)
precisamente porque un valor calculado en el momento de guardar podía divergir silenciosamente de
lo que las lecturas realmente dicen.

**Why this priority**: Es la única funcionalidad de este cambio — eliminar una fuente de
desincronización ya demostrada, sin alterar ningún otro comportamiento del sistema.

**Independent Test**: Ver el consumo de una lectura en el historial individual, en el formulario de
recibo y en el registro masivo, y verificar que en los tres lugares coincide exactamente con
lectura actual menos lectura anterior de esa lectura — sin necesidad de que exista ningún valor
guardado aparte.

**Acceptance Scenarios**:

1. **Given** una lectura con lectura anterior conocida, **When** el usuario la ve en cualquiera de
   las tres pantallas que hoy muestran su consumo, **Then** el valor mostrado es exactamente
   lectura actual menos lectura anterior.
2. **Given** una lectura sin ninguna lectura anterior (del flujo individual o del registro masivo),
   **When** el usuario la ve, **Then** el consumo mostrado usa 0 como lectura anterior — el mismo
   criterio, aplicado ahora de forma pareja en todo el sistema (antes exclusivo del registro
   masivo, specs/019 FR-001). Para el flujo individual, esto es un cambio visible intencional: una
   lectura sin anterior deja de mostrar "sin dato" y pasa a mostrar su propia lectura actual como
   consumo.
3. **Given** el historial completo de una locación con varias lecturas, **When** el usuario lo
   revisa, **Then** ningún valor de consumo mostrado cambia respecto a lo que el sistema ya muestra
   hoy, salvo el caso cubierto explícitamente en el Escenario 2.

---

### Edge Cases

- ¿Qué pasa con las lecturas ya existentes cuyo `consumo_calculado` guardado no coincide
  exactamente con `lectura_actual − lectura_anterior` de esa misma fila (por ejemplo, por alguno de
  los defectos ya corregidos en specs/016/019)? Al dejar de leer el valor guardado y calcularlo
  siempre a partir de las dos lecturas, esas filas pasan a mostrar el valor matemáticamente
  correcto — que puede diferir del que se veía antes si esa fila en particular tenía el defecto ya
  corregido.
- ¿Qué pasa con la importación histórica (`ImportarLecturasMedidorHistoricas`)? Dejar de escribir un
  valor de consumo propio — ya tiene en memoria la lectura actual y la anterior de cada fila que
  importa, así que no pierde ninguna capacidad.
- ¿Qué pasa con una lectura del flujo individual que hoy muestra "sin dato" por no tener lectura
  anterior? Pasa a mostrar su lectura actual como consumo (0 como lectura anterior), el mismo
  criterio que ya regía en el registro masivo desde specs/019 FR-001 — es un cambio de
  comportamiento visible, intencional y confirmado (ver Assumptions).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El sistema DEBE calcular el consumo de cada lectura como lectura actual menos
  lectura anterior de esa misma lectura, en el momento de mostrarlo, en vez de leer un valor
  guardado por separado.
- **FR-002**: El sistema NO DEBE persistir el consumo como su propio campo — toda pantalla que hoy
  lo muestra (historial individual de lecturas, formulario de generación de recibo, registro
  masivo) DEBE seguir mostrándolo, ahora calculado en el momento.
- **FR-003**: La importación histórica de lecturas de medidor (`ImportarLecturasMedidorHistoricas`)
  DEBE dejar de escribir un valor de consumo propio — sigue teniendo en memoria los dos valores
  necesarios para que el consumo se calcule igual que en el resto del sistema.
- **FR-004**: Quitar el almacenamiento del consumo NO DEBE requerir una migración de datos que
  dependa del valor previamente guardado — el cálculo se deriva siempre de columnas que ya existen
  (lectura actual y lectura anterior de la misma fila).
- **FR-005**: Cuando una lectura no tiene ninguna lectura anterior, el sistema DEBE calcular su
  consumo usando 0 como lectura anterior — el mismo criterio en todo el sistema (individual y
  registro masivo por igual), sin excepción por pantalla ni por cómo se haya originado la lectura.
  Esto extiende a todo el sistema el criterio que specs/019 FR-001 ya aplicaba exclusivamente al
  registro masivo.

### Key Entities *(include if feature involves data)*

- **Lectura de Medidor**: entidad ya existente (specs/005, specs/006, specs/015-019). Esta
  funcionalidad elimina su campo `consumo_calculado` — el consumo deja de ser un dato propio de la
  entidad y pasa a ser un valor derivado de sus otros dos campos (`lectura_actual`,
  `lectura_anterior`) en cada consulta.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El 100% de las lecturas con lectura anterior conocida muestran, en las tres pantallas
  donde aparece su consumo, un valor idéntico a lectura actual menos lectura anterior — verificable
  sin consultar ningún campo de consumo guardado, porque ya no existe.
- **SC-002**: Cero líneas de código de la aplicación (controladores, comandos, vistas) escriben un
  valor de consumo al guardar una lectura, en ningún flujo (individual, masivo, importación).
- **SC-003**: La importación histórica de lecturas sigue completándose sin errores y sin pérdida de
  información, sin escribir su propio valor de consumo.

## Assumptions

- Decisión confirmada (Q1): el criterio "sin lectura anterior → 0" pasa a regir en todo el sistema,
  no solo en el registro masivo. Es un cambio de comportamiento visible e intencional en el flujo
  individual: una lectura sin lectura anterior deja de mostrar "sin dato" (specs/006) y pasa a
  mostrar su propia lectura actual como consumo, igual que ya ocurre en el registro masivo desde
  specs/019. Esto NO cambia lo que `LecturaMedidorController` sugiere o guarda como
  `lectura_anterior` al crear una lectura (specs/006, sigue siendo `null` cuando no hay dato) — solo
  cambia cómo se calcula el consumo *mostrado* a partir de ese valor ya guardado.
- Esta funcionalidad no cambia el valor de `total` (specs/019), que sigue siendo el campo
  persistido y autoritativo para la generación de recibos — el consumo derivado es exclusivamente
  para las pantallas informativas ya mencionadas, no para ningún cálculo de facturación.
- El nombre exacto del mecanismo que reemplaza la columna (accessor de Eloquent, método de
  servicio, u otro) es una decisión de la fase de planificación, no de esta especificación — lo
  único que importa a nivel de producto es que el dato mostrado sea correcto y no dependa de un
  valor guardado por separado.
- Las pruebas automatizadas ya existentes que hoy crean una lectura con un `consumo_calculado`
  explícito (para simular un escenario) se ajustan en la fase de implementación para reflejar que
  ese valor ya no se guarda — sin cambiar lo que cada prueba verifica del comportamiento visible.
