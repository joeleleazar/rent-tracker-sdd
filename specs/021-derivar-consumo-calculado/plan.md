# Implementation Plan: Consumo Calculado en el Momento en vez de Almacenado

**Branch**: `021-derivar-consumo-calculado` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/021-derivar-consumo-calculado/spec.md`

## Summary

`lecturas_medidor.consumo_calculado` es hoy una columna persistida que 4 puntos de escritura
distintos (registro individual crear/editar, registro masivo crear/editar en línea, importación
histórica) calculan y guardan por su cuenta — la misma clase de duplicación que ya causó defectos
reales corregidos en specs/016 y specs/019 en esta misma sesión. El valor es matemáticamente
derivable de `lectura_actual` y `lectura_anterior`, que ya viven en la misma fila. Esta feature
reemplaza la columna por un accessor de Eloquent que calcula el consumo en el momento de leerse,
usando 0 como lectura anterior cuando no hay ninguna registrada (FR-005, decisión confirmada Q1:A
— extiende a todo el sistema el criterio que specs/019 FR-001 ya aplicaba solo al registro masivo).

El enfoque técnico es quirúrgico: el accessor se declara con el **mismo nombre** de atributo
(`consumo_calculado`), así que los 3 lugares de solo lectura (historial individual, formulario de
recibo, registro masivo) no cambian ni una línea — siguen leyendo `$lectura->consumo_calculado`
exactamente igual, solo que ahora es un valor calculado en vez de columna. Los cambios reales son:
quitar la escritura en los 4 puntos que hoy la persisten, quitar la columna de la base de datos, y
simplificar las 2 vistas que hoy tienen una rama "sin dato" que deja de ser alcanzable (Q1:A).

## Technical Context

**Language/Version**: PHP 8.2+ (usar `C:\Users\joel5\.config\herd\bin\php.bat` para
`artisan`/`pest` en esta máquina, igual que specs/016-020).

**Primary Dependencies**: Ninguna nueva — `Illuminate\Database\Eloquent\Casts\Attribute` (parte
del framework ya usado, mismo mecanismo de accessors moderno de Laravel 11).

**Storage**: PostgreSQL — se elimina la columna `consumo_calculado` de `lecturas_medidor` (migración
`DROP COLUMN`, sin backfill: el valor nunca dependió de nada externo, siempre fue derivable de
columnas que ya existen en la misma fila).

**Testing**: Pest (Feature + Unit). Todo lo que este cambio afecta es observable por HTTP/Eloquent
directo — no hay comportamiento de navegador involucrado (a diferencia de specs/017/019/020), así
que no hace falta una sección de "brecha de pruebas no verificable por Pest" esta vez.

**Target Platform**: Las tres pantallas ya existentes que muestran consumo (historial individual,
formulario de recibo, registro masivo) y los 2 puntos de escritura por lote (individual, masivo) +
1 comando de importación.

**Project Type**: Aplicación web Laravel (Blade + htmx), monolito.

**Performance Goals**: Sin impacto — el cálculo es una resta en memoria sobre columnas ya cargadas
con el modelo, sin ninguna consulta adicional (ni siquiera una relación); estrictamente más barato
que antes, que ya escribía una columna extra en cada INSERT/UPDATE.

**Constraints**: FR-002/SC-001 exigen que las tres pantallas de lectura sigan mostrando exactamente
el mismo valor — el accessor DEBE reproducir el mismo formato (string con 2 decimales) que ya
producía el cast `decimal:2` de la columna, para que ningún sitio de lectura necesite tocarse.

**Scale/Scope**: Mismo alcance que specs/006/015-020 — toda lectura de medidor del sistema, en
cualquiera de sus tres vías de creación.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack)**: Sin cambios de stack; `DROP COLUMN` vía migración de Laravel, sin SQL
  directo fuera de ella. PASA.
- **Principio II (Español)**: El accessor se declara como `consumoCalculado()` (camelCase que
  Eloquent traduce a `consumo_calculado`), consistente con el resto de nombres en español ya
  usados. PASA.
- **Principio III (Diseño Moderno)**: Sin cambios de interacción — se simplifican 2 ramas de vista
  que ya no son alcanzables, sin agregar ningún patrón nuevo. PASA.
- **Principio IV (Pruebas Exhaustivas)**: Se actualizan las pruebas que hoy verifican el cast de la
  columna o el caso "sin dato" ya inalcanzable (research.md detalla cada una); se agrega cobertura
  del accessor en sí. PASA.
- **Principio V (Integridad de Datos)**: El accessor sigue devolviendo un string con 2 decimales
  (mismo formato que el cast `decimal:2` que reemplaza), sin introducir tipos flotantes imprecisos
  en ningún cálculo. PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: Se simplifican 2 vistas Blade
  (`locaciones/lecturas/index.blade.php`, `locaciones/recibos/create.blade.php`) quitando una rama
  de texto ("sin dato anterior") que ya no ocurre — DEBE pasar por revisión `impeccable` antes de
  cerrarse, igual que specs/016/017/019/020, aunque el cambio es mínimo (menos texto, no más).
  PASA condicionado a esa revisión en implementación.

Sin violaciones. No aplica Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/021-derivar-consumo-calculado/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md        # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

Ningún archivo nuevo salvo la migración de `DROP COLUMN` — todo lo demás son cambios dentro de
archivos ya existentes de specs/005/006/015-020.

```text
database/migrations/
└── YYYY_MM_DD_HHMMSS_eliminar_consumo_calculado_de_lecturas_medidor.php   # DROP COLUMN, sin backfill

app/
├── Models/LecturaMedidor.php                                 # quita fillable/cast de la columna; agrega accessor consumoCalculado()
├── Http/Controllers/LecturaMedidorController.php              # store()/update(): quita la escritura de consumo_calculado
├── Http/Controllers/RegistroMasivoLecturasController.php      # store()/actualizarInline(): quita la escritura de consumo_calculado
└── Console/Commands/ImportarLecturasMedidorHistoricas.php     # quita la escritura de consumo_calculado (ya tiene los 2 valores en memoria)

database/
├── factories/LecturaMedidorFactory.php                        # quita el default 'consumo_calculado' => null
└── seeders/DatabaseSeeder.php                                 # quita la escritura de consumo_calculado

resources/views/locaciones/
├── lecturas/index.blade.php                                   # quita la rama "sin dato anterior" del consumo (ya inalcanzable, Q1:A)
└── recibos/create.blade.php                                   # simplifica la condición: solo importa si hay lectura, no si su consumo es null

tests/
├── Feature/LecturaMedidorControllerTest.php                   # ajustar expectativas del caso "sin lectura anterior" (Q1:A)
├── Feature/RegistroMasivoLecturasControllerTest.php            # sin cambios de expectativa (ya asumía el criterio de 0, specs/019)
└── Unit/LecturaMedidorTest.php                                 # reescribir la prueba de "cast" como prueba del accessor
```

**Structure Decision**: Se reutiliza íntegramente la estructura de specs/005/006/015-020 — no hay
controlador, servicio ni ruta nuevos; el único artefacto de esquema nuevo es la migración que quita
la columna.
