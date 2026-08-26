# Feature Specification: Completar Total en Importación Histórica y Seeder

**Feature Branch**: `022-completar-total-importacion`

**Created**: 2026-08-25

**Status**: Implemented (documentación retroactiva — el fix ya se aplicó y verificó antes de escribir esta spec; ver Nota de Proceso)

**Input**: User description: "Completar el campo total (NOT NULL desde specs/019) en los dos puntos de escritura de LecturaMedidor que quedaron sin él tras esa migración: el comando de importación histórica app/Console/Commands/ImportarLecturasMedidorHistoricas.php y database/seeders/DatabaseSeeder.php. Ninguno de los dos tiene cobertura de test propia, por lo que el defecto no se detectó hasta una revisión manual posterior al cierre de specs/019/021 — ambos fallarían con un error de base de datos (NOT NULL) la próxima vez que se corrieran. El fix calcula total = consumo x tarifa vigente, el mismo criterio ya usado en LecturaMedidorController::calcularTotal() (specs/019): el comando de importación usa ConfiguracionGeneral::actual()->tarifa_luz_por_unidad (única tarifa disponible, sin historial de tarifas), y el seeder usa la tarifa 0.85 que el mismo seeder ya configura al principio de su ejecución. Se verificó que no había datos irregulares que corregir en la base de datos de desarrollo (0 lecturas con total nulo o en cero, y el comando de importación nunca se había corrido)."

## Nota de Proceso

Esta spec se escribió **después** de aplicar el fix, no antes. specs/019-total-editable-recibos volvió obligatorio (`NOT NULL`) el campo `total` de cada lectura de medidor, y su tarea de implementación cubrió los dos flujos con test automatizado (registro individual y registro masivo), pero dejó sin actualizar otros dos puntos del código que también crean lecturas de medidor: el comando de importación histórica y el seeder de datos de demostración. Ninguno de los dos tiene test propio, así que el hueco pasó dos ciclos completos (specs/019 y specs/021) sin detectarse, hasta que apareció en una revisión manual de archivos durante la implementación de specs/021. Se corrigió de inmediato para no dejar el repositorio en un estado que rompería en el próximo uso, y esta spec documenta retroactivamente esa corrección con el mismo nivel de detalle que el resto del proyecto, a pedido del usuario, para mantener un historial de specs limpio y completo.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Importar el histórico de lecturas sin que la importación falle (Priority: P1)

Quien administra el sistema necesita poder correr el comando de importación del histórico de lecturas de medidor (planilla 2018-2019) en cualquier momento — por ejemplo, al preparar un nuevo entorno o al reprocesar el archivo original — y que la importación complete sin errores, dejando cada lectura importada con un total monetario calculado, listo para usarse en recibos.

**Why this priority**: sin esto, el comando falla por completo (ninguna fila se importa) apenas se ejecute, porque la base de datos exige un total en cada lectura desde specs/019. Es la funcionalidad más visible y más grave: un comando que existe en el repositorio pero no puede correrse.

**Independent Test**: correr `php artisan medidores:importar-historico` contra el archivo JSON normalizado de ejemplo y confirmar que termina en éxito, que se crean tantas lecturas como registros válidos había en el archivo, y que ninguna queda con `total` nulo.

**Acceptance Scenarios**:

1. **Given** un archivo `extracted.json` con registros históricos válidos y ninguna lectura de medidor importada todavía, **When** se ejecuta el comando de importación, **Then** el comando termina en éxito y cada lectura creada tiene un total monetario mayor o igual a cero.
2. **Given** dos registros consecutivos de la misma unidad en el archivo histórico (una lectura anterior y una posterior), **When** se importan, **Then** el total de la lectura posterior corresponde al consumo entre ambas lecturas multiplicado por la tarifa de electricidad vigente al momento de correr la importación.

---

### User Story 2 - Poblar un entorno de desarrollo nuevo sin que el seeder falle (Priority: P1)

Quien configura un entorno de desarrollo nuevo (o lo resetea) necesita poder correr el seeder de datos de demostración y obtener una base de datos utilizable de punta a punta — incluyendo el historial de lecturas de medidor de ejemplo — sin que el proceso se interrumpa por un error de base de datos.

**Why this priority**: mismo nivel de gravedad que la Historia 1 — el seeder es el punto de entrada estándar para levantar un entorno de trabajo, y sin este fix se interrumpe con un error en cuanto intenta crear la primera lectura de medidor de ejemplo.

**Independent Test**: correr `php artisan db:seed` sobre una base de datos vacía y confirmar que termina en éxito, que las tres lecturas de medidor de ejemplo del Local 101 quedan creadas, y que las tres tienen un total monetario coherente con la tarifa configurada por el propio seeder.

**Acceptance Scenarios**:

1. **Given** una base de datos vacía, **When** se ejecuta el seeder de demostración, **Then** el proceso termina en éxito y las tres lecturas de medidor de ejemplo quedan creadas con un total monetario calculado, ninguno nulo.
2. **Given** las tres lecturas de ejemplo (consumos crecientes en el tiempo), **When** se revisan sus totales, **Then** cada total es igual al consumo de esa lectura multiplicado por la tarifa de electricidad que el mismo seeder configuró al principio de su ejecución.

---

### Edge Cases

- Primera lectura de una unidad, sin lectura anterior conocida: el consumo se calcula tomando la lectura anterior como cero (mismo criterio ya vigente en el resto del sistema desde specs/019/021), por lo que el total no queda nulo ni el proceso falla.
- No hay una tarifa histórica registrada por período: ambos procesos usan la única tarifa de electricidad disponible en el momento de ejecutarse (la vigente), asumiendo que fue razonablemente estable en el rango de fechas del histórico importado — limitación conocida y ya documentada para el resto del sistema en specs/019.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: El comando de importación histórica de lecturas de medidor DEBE calcular y guardar un total monetario en cada lectura que crea, sin dejar ninguna con el total vacío.
- **FR-002**: El total calculado por el comando de importación DEBE ser el resultado de multiplicar el consumo de esa lectura (lectura actual menos lectura anterior, o menos cero si no hay anterior) por la tarifa de electricidad vigente al momento de correr la importación.
- **FR-003**: El seeder de datos de demostración DEBE calcular y guardar un total monetario en cada lectura de medidor de ejemplo que crea, sin dejar ninguna con el total vacío.
- **FR-004**: El total calculado por el seeder DEBE ser consistente con la tarifa de electricidad que el propio seeder configura como dato de demostración.
- **FR-005**: Ninguno de los dos procesos DEBE requerir que exista un historial de tarifas por período — ambos usan la tarifa disponible en el momento de ejecutarse, documentando esa limitación en vez de intentar resolverla.

### Key Entities *(include if feature involves data)*

- **Lectura de medidor**: registro de consumo eléctrico de una unidad en un período determinado (lectura anterior, lectura actual, y ahora obligatoriamente un total monetario). Esta feature no agrega ni cambia atributos de la entidad — solo asegura que los dos procesos que faltaban la completen correctamente.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: El comando de importación histórica corre de punta a punta sin errores de base de datos, con el 100% de las lecturas importadas quedando con un total monetario calculado.
- **SC-002**: El seeder de datos de demostración corre de punta a punta sin errores de base de datos, con el 100% de las lecturas de ejemplo quedando con un total monetario calculado.
- **SC-003**: El total de cada lectura creada por cualquiera de los dos procesos coincide exactamente (sin redondeos inconsistentes) con consumo × tarifa vigente en ese proceso.

## Assumptions

- La base de datos de desarrollo actual no tenía datos irregulares que corregir: se verificó (2026-08-25) que ninguna lectura existente tiene `total` nulo o en cero, y que el comando de importación histórica nunca se había ejecutado en este entorno — por lo tanto esta feature es puramente preventiva sobre código, no requiere una migración de datos de corrección (backfill).
- No existe (ni esta feature lo introduce) un historial de tarifas de electricidad por período; se acepta la misma limitación ya documentada en specs/019 (Decisión sobre el backfill de `total`): usar la tarifa vigente al momento de ejecutar cada proceso es una aproximación razonable, no un cálculo históricamente exacto.
- El criterio "lectura anterior ausente cuenta como cero" para calcular consumo es el mismo ya vigente en el resto del sistema (specs/019 Q1, specs/021), no una decisión nueva de esta feature.
