# Implementation Plan: Corrección de Lectura Previa y Autoguardado en Registro Masivo

**Branch**: `016-correccion-registro-masivo-lecturas` | **Date**: 2026-08-25 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/016-correccion-registro-masivo-lecturas/spec.md`

## Summary

specs/015-registro-masivo-lecturas ya está implementada y sus 18 pruebas automatizadas pasan, pero
dos comportamientos ya especificados no se sostienen en uso real: (1) la "lectura del periodo
anterior" mostrada junto a cada locación (FR-006 de 015), y (2) el autoguardado del borrador cada
2 minutos y su restauración (FR-010/FR-011 de 015). Esta feature no cambia alcance ni introduce
comportamiento nuevo — corrige ambos hasta que vuelvan a cumplir exactamente lo ya especificado.

La revisión de código de Phase 0 (ver `research.md`) no encontró, por lectura estática del
controlador, el servicio de cálculo ni las vistas, un defecto lógico evidente que reproduzca los
síntomas reportados — la consulta SQL/colección de "lectura anterior" y el cableado htmx del
autoguardado coinciden con las decisiones ya documentadas en `specs/015/research.md` (Decisiones 2
y 4-6). Sí se encontró una brecha real y accionable: **ninguna prueba existente puede detectar los
dos defectos reportados** — la prueba de "lectura anterior" solo verifica que el valor aparece en
algún lugar de la página (`assertSee('1250.00')`), sin comprobar a qué locación/fila está ligado
(ver Hallazgo H1 en research.md), y las tres pruebas de borrador invocan la ruta `guardarBorrador`
directamente, sin pasar nunca por el disparador `hx-trigger="every 120s"` real (Hallazgo H2). El
enfoque técnico es entonces: (a) reproducir cada defecto con datos reales en el navegador antes de
tocar código de producción (protocolo en `research.md`), (b) endurecer las pruebas para que
detecten con precisión el escenario reportado (fila/locación correcta, ciclos sucesivos), y (c)
aplicar el fix mínimo que el hallazgo de la reproducción indique.

## Technical Context

**Language/Version**: PHP 8.2+ (proyecto corre localmente con PHP 8.4.12 vía Herd; el intérprete
por defecto de PATH, PHP 8.0.30 de XAMPP, no cumple el mínimo de `composer.lock` — usar el binario
de Herd para `artisan`/`pest` en esta máquina, ver research.md)

**Primary Dependencies**: Laravel 11.x, htmx (`hx-boost`, sin JS de temporizador custom — Principio
VI), Bootstrap 5.3, Pest 4 — sin dependencias nuevas; misma pila que specs/015

**Storage**: PostgreSQL 15+ — tablas `lecturas_medidor` y `borradores_lectura_medidor` ya
existentes (specs/006, specs/015), sin cambios de esquema

**Testing**: Pest (Feature) — se amplían `tests/Feature/RegistroMasivoLecturasControllerTest.php`
con los escenarios no cubiertos hoy (ver research.md); no hay Dusk/Playwright instalado, por lo que
la verificación del disparador `hx-trigger="every 120s"` en el navegador real es manual
(`quickstart.md`), reforzada por una prueba de "contrato HTML" que falla si el atributo desaparece

**Target Platform**: Navegador de escritorio, misma pantalla `/lecturas/registro-masivo` ya
existente

**Project Type**: Aplicación web Laravel (Blade + htmx), monolito — sin frontend/backend separados

**Performance Goals**: Sin cambio respecto a specs/015 — una sola consulta para "lecturas
anteriores" de todas las locaciones alquilables (sin N+1), autoguardado en una sola sentencia
`upsert()` por ciclo

**Constraints**: El fix DEBE preservar exactamente el comportamiento ya especificado en FR-006 y
FR-010/FR-011 de specs/015 (Assumptions de spec.md) — no es una renegociación de alcance

**Scale/Scope**: Mismo alcance que specs/015 — todas las locaciones alquilables del sistema, un
borrador por usuario+periodo

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Principio I (Stack)**: Sin cambios de stack ni consultas SQL directas fuera del ORM. PASA.
- **Principio II (Español)**: Ningún nombre nuevo previsto (correcciones dentro de clases/vistas ya
  nombradas en español). PASA.
- **Principio III (Diseño Moderno)**: No se tocan patrones de interacción ni tipografía; el mensaje
  de estado del autoguardado (`#estado-autoguardado`) ya existe y no cambia de forma. PASA.
- **Principio IV (Pruebas Exhaustivas)**: Esta feature es, en sí misma, un endurecimiento de
  cobertura de pruebas — ver research.md Hallazgos H1/H2. PASA (y es el objeto central del cambio).
- **Principio V (Integridad de Datos)**: Sin cambios a transacciones ni tipos numéricos; se
  preserva `DECIMAL`/`decimal:2` ya usado. PASA.
- **Principio VI (Bootstrap 5 / htmx / impeccable)**: Si el fix toca alguna vista Blade
  (`fila-registro-masivo.blade.php`, `campo-lectura-registro-masivo.blade.php`, `index.blade.php`),
  DEBE pasar por revisión `impeccable` antes de cerrarse, igual que cualquier vista modificada. La
  Excepción de interactividad asíncrona (htmx, no Alpine.js/`setInterval`) se mantiene: el fix del
  autoguardado, si requiere cambios, se resuelve con atributos htmx declarativos, no con JS custom
  de temporizador. PASA condicionado a esa revisión en implementación.

Sin violaciones. No aplica Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/016-correccion-registro-masivo-lecturas/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output (/speckit-plan command)
├── data-model.md         # Phase 1 output (/speckit-plan command)
├── quickstart.md        # Phase 1 output (/speckit-plan command)
├── contracts/           # Phase 1 output (/speckit-plan command)
└── tasks.md             # Phase 2 output (/speckit-tasks command - NOT created by /speckit-plan)
```

### Source Code (repository root)

Ningún archivo ni directorio nuevo: esta feature corrige archivos ya creados por specs/015, dentro
de la misma estructura de aplicación Laravel monolítica ya establecida.

```text
app/
├── Http/Controllers/RegistroMasivoLecturasController.php   # datosDelPeriodo(), guardarBorrador()
├── Services/ServicioCalculoConsumoMedidor.php               # sugerirLecturaAnterior() (flujo individual, no tocado por FR-006 masivo)
└── Models/BorradorLecturaMedidor.php

resources/
├── views/lecturas/registro-masivo/
│   ├── index.blade.php                                       # trigger hx-post "every 120s"
│   └── partials/
│       ├── fila-registro-masivo.blade.php                     # columna "Lectura Periodo Anterior"
│       └── campo-lectura-registro-masivo.blade.php             # data-lectura-anterior
└── js/registro-masivo-lecturas.js                             # sin temporizador (por diseño, Decisión 4 de 015)

tests/Feature/RegistroMasivoLecturasControllerTest.php         # pruebas a endurecer (research.md)
```

**Structure Decision**: Se reutiliza íntegramente la estructura de specs/015 — esta feature no
agrega capas ni módulos nuevos, solo corrige comportamiento dentro de los archivos ya listados.
