# Implementation Plan: Reformato de Jerarquía Visual del Comprobante de Recibo

**Branch**: `031-reformato-comprobante-recibo` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/031-reformato-comprobante-recibo/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Reestructurar `resources/views/locaciones/recibos/comprobante.blade.php` de un listado plano de pares
etiqueta/valor a 6 bloques verticales separados (encabezado con logo, metadatos, partes, detalle de
conceptos, total destacado, cierre), con montos alineados en una columna fija a la derecha y una jerarquía
tipográfica de 3 niveles. Agrega un campo nuevo `nombre_propietario` a `ConfiguracionGeneral` (patrón
clave-valor ya existente, sin migración de esquema) para mostrar "Recibido por" en el bloque de partes,
editable desde la pantalla de Configuración ya existente.

## Technical Context

**Language/Version**: PHP 8.2+, Laravel (sin cambios de versión), Blade — mismo stack ya usado en todo el
proyecto.

**Primary Dependencies**: Ninguna nueva. Reutiliza `App\Models\ConfiguracionGeneral` (patrón clave-valor de
specs/018), `App\Http\Controllers\ConfiguracionGeneralController` y `App\Http\Requests\SolicitudActualizarConfiguracionGeneral`
ya existentes; el propio CSS embebido de `comprobante.blade.php` (sin Bootstrap/Tailwind — ver excepción ya
documentada en esa vista por incompatibilidad de html2canvas con `oklch()`, Constitution Check abajo).

**Storage**: PostgreSQL, tabla `configuracion_general` (clave-valor) — se agrega una fila nueva con
`clave = 'nombre_propietario'` la primera vez que se guarda un valor; no requiere migración de esquema
(mismo patrón que las 4 claves ya existentes, ver research.md Decisión 6).

**Testing**: Pest — se extienden `tests/Feature/ReciboControllerTest.php` (estructura de bloques del
comprobante, dato "Recibido por"), `tests/Feature/ConfiguracionGeneralControllerTest.php` (validación y
persistencia del nuevo campo) y `tests/Unit/ConfiguracionGeneralTest.php` (valor por defecto). No se tocan
`tests/Feature/LogoInstitucionalTest.php` (sigue pasando sin cambios: solo verifica la presencia del
`<img>` del logo, no su posición).

**Target Platform**: Aplicación web Laravel servida por Herd (sin cambios).

**Project Type**: Aplicación web monolítica existente — modificación de una vista y una entidad de
configuración ya existentes, sin estructura nueva.

**Performance Goals**: N/A — reestructuración visual de una vista ya renderizada por el mismo controlador
existente (`ReciboController::comprobante()`), sin consultas adicionales de peso (una config ya cacheable
vía `ConfiguracionGeneral::actual()`, ya usada en otras vistas).

**Constraints**: El CSS del comprobante DEBE seguir siendo colores hexadecimales planos (sin `oklch()`,
Tailwind ni variables Bootstrap) porque html2canvas 1.4.x aborta la captura completa del documento si
encuentra esa función de color en cualquier regla de la página (restricción ya documentada en el propio
archivo); DEBE seguir funcionando tanto en pantalla como en `@media print` y en la captura de imagen para
WhatsApp.

**Scale/Scope**: 1 vista Blade reestructurada (`comprobante.blade.php`), 1 atributo nuevo en
`ConfiguracionGeneral` + su `FormRequest` + su vista de edición (`configuracion/edit.blade.php`), tests
extendidos en 3 archivos existentes.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)**: Cumple — el nuevo dato se persiste con
  Eloquent a través del modelo ya existente `ConfiguracionGeneral`, sin SQL directo ni bypass del ORM.
- **II. Nomenclatura en Español**: Cumple — el nuevo atributo/clave se llama `nombre_propietario`, la
  etiqueta visible es "Recibido por", y las clases CSS nuevas del comprobante siguen la convención en
  español ya usada en ese archivo (`.fila`, `.fila-total`).
- **III. Diseño Moderno e Intuitivo**: Aplica directamente — la reestructuración en bloques con
  separadores, jerarquía tipográfica de 3 niveles y el bloque de total destacado son exactamente el objeto
  de esta feature; se mantiene el piso de contraste WCAG AA (4.5:1) en los tonos hexadecimales elegidos
  (research.md Decisión 3).
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — `ConfiguracionGeneralControllerTest` cubre la
  validación/persistencia del campo nuevo (FormRequest), `ConfiguracionGeneralTest` cubre su valor por
  defecto (modelo), y `ReciboControllerTest` cubre que el comprobante muestra los 6 bloques y el dato
  "Recibido por".
- **V. Integridad de Datos y Seguridad Transaccional**: N/A para el nuevo campo — es un string de
  configuración sin cálculo financiero ni transacción multi-tabla; se persiste con el mismo mecanismo
  clave-valor atómico (`updateOrInsert` por fila) ya usado por el resto de `ConfiguracionGeneral`.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: `comprobante.blade.php` ya tiene, desde antes de
  esta feature, una excepción documentada dentro del propio archivo (comentario en el `<style>`) al uso de
  Bootstrap/Tailwind, por incompatibilidad real con html2canvas — esta feature no introduce una excepción
  nueva, solo continúa trabajando dentro de la ya existente. La vista de Configuración
  (`configuracion/edit.blade.php`), en cambio, sí usa Bootstrap 5 normalmente (`x-input-label`,
  `x-text-input`, `card`) y el campo nuevo sigue exactamente ese mismo patrón sin excepción.

**Resultado del gate**: PASA — sin violaciones nuevas.

## Project Structure

### Documentation (this feature)

```text
specs/031-reformato-comprobante-recibo/
├── plan.md                                   # This file (/speckit-plan command output)
├── research.md                               # Phase 0 output — decisiones de bloques, tipografía, color del total y el nuevo campo
├── data-model.md                             # Phase 1 output — ConfiguracionGeneral extendido + entidades de solo lectura del comprobante
├── contracts/
│   ├── configuracion-general.md              # Phase 1 output — contrato de validación del campo nombre_propietario
│   └── estructura-comprobante.md             # Phase 1 output — orden y nombres de clase de los 6 bloques del comprobante
└── quickstart.md                             # Phase 1 output — checklist de validación manual contra spec.md
```

### Source Code (repository root)

Aplicación Laravel monolítica ya existente — sin cambios de estructura. Archivos tocados:

```text
app/Models/ConfiguracionGeneral.php                          # + atributo nombre_propietario
app/Http/Requests/SolicitudActualizarConfiguracionGeneral.php  # + regla de validación
resources/views/configuracion/edit.blade.php                  # + campo del formulario

resources/views/locaciones/recibos/comprobante.blade.php     # reestructuración en 6 bloques (foco principal)

tests/Feature/ReciboControllerTest.php                        # tests extendidos del comprobante
tests/Feature/ConfiguracionGeneralControllerTest.php           # tests extendidos del campo nuevo
tests/Unit/ConfiguracionGeneralTest.php                        # test del valor por defecto
```

**Structure Decision**: Se mantiene la estructura Laravel estándar ya usada por todo el proyecto — no se
crean archivos ni directorios nuevos fuera de los ya existentes que esta feature modifica.

## Complexity Tracking

Sin violaciones a la constitución — tabla no aplica (ver Constitution Check arriba).
