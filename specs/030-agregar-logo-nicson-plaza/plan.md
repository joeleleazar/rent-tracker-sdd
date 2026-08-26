# Implementation Plan: Incorporar el Logo de Nicson Plaza a la Interfaz

**Branch**: `030-agregar-logo-nicson-plaza` | **Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/030-agregar-logo-nicson-plaza/spec.md`

**Note**: This template is filled in by the `/speckit-plan` command; its definition describes the execution workflow.

## Summary

Incorporar el archivo de logo entregado por el usuario (marca "Nicson Plaza") como
`public/images/logo-nicson-plaza.png`, referenciado vía `asset()` desde 4 puntos: el layout de invitado
(login), el encabezado del layout autenticado (sidebar), el comprobante de recibo, y una etiqueta
`<link rel="icon">` en ambos layouts para el ícono de pestaña — sin depender de ninguna herramienta de
conversión de imágenes (no disponible en este entorno), aprovechando que los navegadores actuales aceptan un
`.png` directamente como favicon vía `<link rel="icon" type="image/png">`. El archivo fue reemplazado una
vez por una versión provista por el propio usuario (transparente, proporción no cuadrada) tras la
implementación inicial — ver research.md Decisión 1 para el detalle y el ajuste correspondiente.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 13.x (sin cambios), Blade

**Primary Dependencies**: Ninguna nueva — el archivo se sirve como asset estático de `public/`, igual que
`public/favicon.ico` ya existente; no se agrega a la lista de entradas de Vite (esa lista es solo para
JS/CSS que Vite procesa, no para imágenes estáticas)

**Storage**: N/A — un archivo de imagen estático, sin base de datos ni migraciones

**Testing**: Pest (PHPUnit) — Feature tests que verifican la presencia del `<img>`/`<link>` esperado en el
HTML de cada vista tocada

**Target Platform**: Aplicación web Laravel servida por Herd (sin cambios)

**Project Type**: Aplicación web monolítica existente (sin cambios de estructura)

**Performance Goals**: N/A — el archivo actual (`.png` con transparencia, provisto por el usuario tras un
ajuste posterior a la implementación inicial — ver research.md Decisión 1) pesa ~864 KB (1769×962); se sirve
tal cual, sin compresión ni redimensionado adicional (research.md documenta por qué no se optimiza más en
esta feature)

**Constraints**: Sin herramientas de conversión/edición de imágenes disponibles en este entorno
(`magick`/`convert` de ImageMagick, `cwebp`, `ffmpeg`) — cualquier decisión de esta feature debe funcionar
usando el archivo `.jpg` tal cual, sin generar variantes (recortes, `.ico`, WebP) por fuera de lo que CSS y
las etiquetas HTML estándar ya resuelven.

**Scale/Scope**: 4 archivos Blade tocados (2 layouts compartidos + 1 vista de comprobante — los layouts ya
cubren el resto de pantallas por herencia) + 1 archivo de imagen nuevo en `public/`.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **I. Stack Tecnológico Moderno**: Cumple — no involucra base de datos ni backend; es un asset estático
  servido por el mismo mecanismo que ya sirve `favicon.ico`.
- **II. Nomenclatura en Español**: Cumple — el nombre de archivo (`logo-nicson-plaza.png`) y cualquier
  variable Blade nueva seguirán la convención ya usada en el proyecto.
- **III. Diseño Moderno e Intuitivo**: Aplica directamente — el logo debe leerse bien tanto sobre el sidebar
  oscuro como sobre fondos claros; research.md Decisión 2 resuelve esto con el propio patrón `card` ya
  exigido por el Principio VI (una tarjeta blanca pequeña como marco del logo en el sidebar oscuro, por
  contraste contra el fondo casi negro `$dark`) y con un dimensionado por alto (`height` + `width: auto`)
  que respeta la proporción real del archivo en vez de forzarlo a una caja cuadrada.
- **IV. Pruebas Automatizadas Exhaustivas**: Aplica — cada FR de spec.md requiere su propia verificación
  Feature test (presencia del logo en el HTML de cada vista/layout tocado), detallado en tasks.md.
- **V. Integridad de Datos y Seguridad Transaccional**: N/A — no hay escritura de datos ni transacciones
  involucradas.
- **VI. Sistema de Componentes Visuales (Bootstrap 5)**: Aplica directamente — el logo se enmarca en un
  `card`/badge consistente con el resto del sistema de componentes; toda vista Blade nueva o modificada
  pasa por la revisión del skill `impeccable` antes de darse por completa, según exige este principio.

Sin violaciones — no se requiere la sección de Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/030-agregar-logo-nicson-plaza/
├── plan.md              # This file (/speckit-plan command output)
├── research.md          # Phase 0 output — decisiones de formato, fondo y ubicación del archivo
└── quickstart.md        # Phase 1 output — escenarios de validación manual
```

No se generan `data-model.md` ni `contracts/`: no hay entidades de datos ni rutas/endpoints nuevos — es un
asset estático referenciado desde vistas ya existentes.

### Source Code (repository root)

Aplicación Laravel monolítica ya existente — sin cambios de estructura. Archivos relevantes:

```text
public/
└── images/
    └── logo-nicson-plaza.png           # nuevo — copia del archivo entregado por el usuario

resources/views/components/layouts/
├── guest-bootstrap.blade.php           # login: reemplaza <x-application-logo>; + <link rel="icon">
└── app-bootstrap.blade.php             # sidebar: reemplaza el texto de marca; + <link rel="icon">

resources/views/locaciones/recibos/
└── comprobante.blade.php               # + logo en el encabezado del documento

tests/Feature/
└── (nuevo o extendido) LogoInstitucionalTest.php  # verifica la presencia del logo en cada vista tocada
```

**Structure Decision**: Se mantiene la estructura Laravel estándar ya usada por todo el proyecto — el
único archivo nuevo fuera de `resources/`/`tests/` es la propia imagen en `public/images/`, siguiendo el
mismo lugar donde ya vive `public/favicon.ico`.

## Complexity Tracking

Sin violaciones a la constitución — tabla no aplica (ver Constitution Check arriba).
