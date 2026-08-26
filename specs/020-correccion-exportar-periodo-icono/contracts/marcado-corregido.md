# Contrato de Interfaz: Marcado Corregido del Registro Masivo

**Feature**: `020-correccion-exportar-periodo-icono` | **Date**: 2026-08-25

No hay rutas nuevas — las tres correcciones actúan sobre el marcado ya servido por
`GET lecturas.registroMasivo.index` (specs/015) y sobre `resources/js/registro-masivo-lecturas.js`.
Este documento fija el contrato de atributos/estructura que la vista y el script DEBEN cumplir.

## Contrato 1 — Enlaces de exportar excluidos del boost

- `<a href="{{ route('lecturas.registroMasivo.exportarExcel', ...) }}">` y el equivalente de PDF
  en `index.blade.php` DEBEN declarar `hx-boost="false"`, para que el `hx-boost="true"` heredado
  del layout raíz (specs/011) no los intercepte.
- El clic en cualquiera de los dos DEBE producir una navegación/descarga de archivo normal del
  navegador (no una petición AJAX de htmx, no un cambio de `history.pushState`, no un intento de
  reemplazar el `<body>` con contenido binario).

## Contrato 2 — "Cambiar Periodo" envía el formulario

- El `<x-secondary-button>` de "Cambiar Periodo" en `index.blade.php` DEBE declarar
  `type="submit"` explícito (el componente por sí solo por defecto es `type="button"`, correcto
  para el resto de sus usos en la app, pero no para este).
- Un clic en ese botón, con el input `periodo` (`type="month"`) en un valor distinto al de la URL
  actual, DEBE producir una nueva petición `GET` a `lecturas.registroMasivo.index` con el nuevo
  `periodo` como query param, actualizando toda la pantalla (vía el `hx-boost` normal del layout,
  que sí es el comportamiento correcto para este formulario).

## Contrato 3 — Ícono de completada informativo + botón de editar separado

Para una locación con `$lecturaDelPeriodo !== null && ! $modoEdicion` en
`campo-lectura-registro-masivo.blade.php`:

- DEBE existir un `<span>` (no un elemento interactivo) con el ícono `bi-check-circle-fill`,
  `aria-label="Lectura completada"` (o equivalente), sin ningún atributo `hx-*` ni `onclick`.
- DEBE existir, por separado, un `<button>` con el ícono `bi-pencil-square`, con
  `hx-get`/`hx-target="#campo-lectura-{locacion_id}"`/`hx-swap="outerHTML"` apuntando a
  `lecturas.registroMasivo.editarInline` (el mismo endpoint ya usado hoy), y su propio
  `aria-label`/`title` indicando la acción de editar (ej. "Editar lectura de {locación}").
- Ningún tooltip de Bootstrap (`data-bs-toggle="tooltip"`) asociado a un elemento removido por un
  swap de htmx DEBE permanecer visible después de ese swap — `registro-masivo-lecturas.js` DEBE
  disponer (`.dispose()`) la instancia de tooltip de cualquier elemento (o descendiente) alcanzado
  por el evento `htmx:beforeCleanupElement`.
