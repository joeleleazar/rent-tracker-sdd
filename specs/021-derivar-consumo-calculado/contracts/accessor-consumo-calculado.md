# Contrato de Interfaz: Accessor `consumo_calculado`

**Feature**: `021-derivar-consumo-calculado` | **Date**: 2026-08-25

No hay rutas ni endpoints nuevos — el único contrato de esta feature es el del propio atributo
`LecturaMedidor::consumo_calculado`, ahora un valor calculado en vez de una columna, consumido por
las mismas tres pantallas de siempre.

## Contrato 1 — `LecturaMedidor->consumo_calculado`

- Para cualquier instancia de `LecturaMedidor`, acceder a `->consumo_calculado` DEBE devolver
  `lectura_actual − (lectura_anterior ?? 0)`, redondeado a 2 decimales, como string con formato
  `"0.00"` (mismo formato que el cast `decimal:2` que reemplaza — nunca notación científica, nunca
  sin los 2 decimales).
- Este valor **nunca** es `null` mientras la instancia de `LecturaMedidor` exista — la ausencia de
  `lectura_anterior` ya no produce "sin dato" (Q1:A de spec.md), sin excepción por cómo se creó la
  lectura (registro individual, registro masivo, o importación histórica).
- El cálculo NO ejecuta ninguna consulta a base de datos ni carga ninguna relación — usa
  exclusivamente los atributos `lectura_actual`/`lectura_anterior` ya presentes en la instancia.
- `LecturaMedidor::create()`/`->update()` DEJAN de aceptar `consumo_calculado` como atributo
  asignable en masa (ya no está en `$fillable`, no existe la columna) — cualquier código que
  intente escribirlo debe quitarse, no reemplazarse por otra forma de persistirlo.

## Contrato 2 — Pantallas de lectura (sin cambios de comportamiento salvo Q1:A)

- `resources/views/lecturas/registro-masivo/partials/campo-lectura-registro-masivo.blade.php`
  (`data-consumo`), `resources/views/locaciones/lecturas/index.blade.php` (historial individual) y
  `resources/views/locaciones/recibos/create.blade.php` (formulario de recibo) DEBEN seguir
  mostrando `$lectura->consumo_calculado` exactamente como hoy — sin ningún cambio de marcado más
  allá de quitar la rama "sin dato anterior" ya inalcanzable (research.md Decisión 4).
