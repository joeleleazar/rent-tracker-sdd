# Implementation Plan: Comprobante de Pago Firmado y Evidencia de Pago

**Branch**: `035-comprobante-evidencia-pago` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/035-comprobante-evidencia-pago/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Agregar un comprobante imprimible propio de cada pago (monto de ese pago, acumulado y saldo pendiente del
recibo, espacio de firma para quien recibe el pago) y la posibilidad de subir/reemplazar un archivo de
evidencia (imagen o PDF) del comprobante ya firmado, asociado a ese pago específico — reutilizando el mismo
patrón de almacenamiento de archivos ya establecido para los documentos de contrato (specs de gestión
documental de contratos).

## Technical Context

**Language/Version**: PHP 8.2+, Laravel (sin cambios), Blade, PostgreSQL — mismo stack ya usado en todo el
proyecto.

**Primary Dependencies**: Ninguna nueva. Reutiliza `Illuminate\Support\Facades\Storage` (disco `local`) y el
mismo patrón de subida/streaming ya usado por `DocumentoContratoController` para los documentos de
contrato.

**Storage**: PostgreSQL — 3 columnas nuevas en `pagos` (`evidencia_ruta`, `evidencia_nombre_archivo`,
`evidencia_tipo`), todas nullable; archivo físico en el disco `local` de Laravel, carpeta `pagos/{id}` (
mismo criterio que `contratos/{id}` para documentos de contrato). Un pago admite una única evidencia a la
vez (spec.md Assumptions) — no se necesita una tabla aparte tipo `documentos_contrato`, basta con columnas
directas en `pagos`.

**Testing**: Pest — tests nuevos para el comprobante de pago (contenido correcto: monto del pago, acumulado,
saldo pendiente) y para subir/reemplazar/consultar evidencia (incluyendo rechazo de archivos no admitidos).

**Target Platform**: Aplicación web Laravel servida por Herd (sin cambios).

**Project Type**: Aplicación web monolítica existente — una vista nueva + una acción de subida de archivo,
sin cambios de estructura.

**Performance Goals**: N/A — un documento generado a partir de un único pago ya cargado, y una subida de
archivo individual (no un lote).

**Constraints**: El nuevo comprobante de pago, a diferencia del comprobante del recibo completo (specs/031),
NO necesita capturarse como imagen vía html2canvas (spec.md no pide compartir por WhatsApp) — por lo tanto
NO hereda la restricción de evitar Bootstrap/Tailwind por el conflicto con `oklch()` (research.md Decisión
1) y puede construirse con Bootstrap 5 real, sin la excepción que sí aplica al comprobante del recibo.

**Scale/Scope**: 3 columnas nuevas en `pagos`, 1 vista nueva (comprobante de pago, standalone), 1
controlador nuevo (evidencia de pago) + 1 acción nueva en el controlador de pagos ya existente (ver
comprobante), 2-3 rutas nuevas.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno (PHP, Laravel y PostgreSQL)**: Cumple — columnas nuevas vía migración
  Eloquent estándar, subida de archivos con `Illuminate\Support\Facades\Storage`, sin SQL directo.
- **II. Nomenclatura en Español**: Cumple — `evidencia_ruta`/`evidencia_nombre_archivo`/`evidencia_tipo`,
  `EvidenciaPagoController`, nombres de ruta y vista en español, siguiendo la convención ya usada en
  `documentos_contrato`.
- **III. Diseño Moderno e Intuitivo**: Aplica — el comprobante de pago debe verse bien tanto en pantalla
  como impreso, con el monto del pago como elemento más destacado (mismo criterio que ya exige specs/031
  para el total del recibo).
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — cobertura Feature del comprobante de pago (contenido
  correcto) y de subir/reemplazar/consultar evidencia, incluyendo el rechazo de archivos no admitidos.
- **V. Integridad de Datos y Seguridad Transaccional**: Aplica — reemplazar una evidencia (borrar el
  archivo físico anterior del disco + actualizar la fila) ocurre dentro de una única `DB::transaction`,
  mismo patrón que ya usa `DocumentoContratoController::destroy()`.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Cumple **sin excepción** — a diferencia del
  comprobante del recibo completo (specs/031, que sí tiene una excepción documentada por su necesidad de
  captura con html2canvas), este comprobante de pago no se captura como imagen, así que puede y debe
  construirse con Bootstrap 5 real (`card`, `btn`, utilidades `d-print-*`), sin duplicar CSS propio.

**Resultado del gate**: PASA — sin excepciones nuevas (y de hecho, sin heredar la única excepción que sí
existe hoy en este mismo dominio de "comprobantes").

## Project Structure

### Documentation (this feature)

```text
specs/035-comprobante-evidencia-pago/
├── plan.md                                   # This file (/speckit-plan command output)
├── research.md                               # Phase 0 output — decisiones de documento, almacenamiento y reemplazo de evidencia
├── data-model.md                             # Phase 1 output — columnas nuevas de Pago
├── contracts/
│   ├── comprobante-pago.md                   # Phase 1 output — ruta y contenido del comprobante de pago
│   └── evidencia-pago.md                     # Phase 1 output — rutas y validación de subir/consultar evidencia
└── quickstart.md                             # Phase 1 output — checklist de validación manual contra spec.md
```

### Source Code (repository root)

Aplicación Laravel monolítica ya existente — sin cambios de estructura. Archivos nuevos o tocados:

```text
database/migrations/
└── ..._add_evidencia_a_pagos_table.php                 # nuevo

app/Models/Pago.php                                      # + evidencia_ruta/evidencia_nombre_archivo/evidencia_tipo, tieneEvidencia()

app/Http/Controllers/
├── PagoReciboController.php                              # + comprobante()
└── EvidenciaPagoController.php                           # nuevo — store (subir/reemplazar) y show (consultar)

app/Http/Requests/
└── SolicitudSubirEvidenciaPago.php                       # nuevo

resources/views/pagos/
├── comprobante.blade.php                                 # nuevo — standalone, Bootstrap real (sin la excepción de specs/031)
└── (recibos/show.blade.php ya existente)                 # + enlaces a "Ver Comprobante" y a la evidencia por cada pago

routes/web.php                                            # + rutas de comprobante y evidencia de pago

tests/Feature/
├── ComprobantePagoControllerTest.php                     # nuevo
└── EvidenciaPagoControllerTest.php                       # nuevo
```

**Structure Decision**: Se mantiene la estructura Laravel estándar ya usada por todo el proyecto. El
comprobante de pago vive en `resources/views/pagos/` (no dentro de `locaciones/recibos/`) porque, aunque
está relacionado con un recibo, es un documento propio de la entidad `Pago` — mismo criterio de separación
ya aplicado en specs/033 para `pagos/seguimiento/`.

## Complexity Tracking

Sin violaciones a la constitución — tabla no aplica (ver Constitution Check arriba).
