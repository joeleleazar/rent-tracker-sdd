# Research: Barra de Progreso de Pagos

## Decisión 1: El componente deriva porcentaje y color de `montoPagado`/`montoTotal`, no de un estado pasado aparte

**Decisión**: `<x-barra-progreso-pago>` recibe solo `:monto-pagado` y `:monto-total` (dos floats). Calcula
internamente el porcentaje (`min(100, montoTotal > 0 ? round(montoPagado / montoTotal * 100) : 0)`) y el
color (`bg-secondary` si `montoPagado <= 0`, `bg-success` si `montoPagado >= montoTotal`, `bg-warning` en
cualquier otro caso).

**Rationale**: FR-004 exige que la barra nunca quede desactualizada respecto al texto, y SC-003 exige que
coincida exactamente con el nuevo avance tras registrar/editar/eliminar un pago. La forma más simple de
garantizar eso **por construcción** — sin depender de que dos llamados distintos pasen el mismo enum de
estado de forma consistente — es que la barra reciba los mismos dos números que el texto adyacente ya
muestra y derive todo lo demás de ahí. Esto también evita tener que armonizar los dos vocabularios de estado
que ya conviven en el proyecto (`estadoAgregado`: `sin_recibos`/`sin_pagos`/`parcial`/`pagado` en specs/032;
`recibo->estado`: `pendiente`/`pagado`/`anulado` en el modelo `Recibo`) — el componente no necesita saber
cuál de los dos está usando quien lo invoca.

**Alternatives considered**:
- Pasar un prop `estado` ya calculado por el llamador: descartado — obligaría a mapear dos vocabularios de
  estado distintos a un tercero común en cada sitio de uso, y reintroduce el riesgo de desincronización que
  esta decisión busca eliminar (ej. si alguien pasa el `estadoAgregado` de una locación pero los montos de
  un recibo distinto).

## Decisión 2: Primer uso del componente `progress` nativo de Bootstrap

**Decisión**: Usar `<div class="progress" role="progressbar" aria-valuenow="{{ $porcentaje }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar {{ $colorClase }}" style="width: {{ $porcentaje }}%"></div></div>`.

**Rationale**: Es el primitivo estándar de Bootstrap 5 para exactamente este propósito (representar una
proporción), documentado y accesible out-of-the-box con los atributos ARIA que ya trae — no hace falta
construir nada casero. Ningún otro componente del sistema (`card`, `badge`, etc.) representa una proporción
continua, así que no hay una alternativa ya establecida en el proyecto que debiera reutilizarse en su lugar.

**Alternatives considered**: Un `<meter>` HTML nativo — descartado porque su estilización cross-browser es
mucho más limitada que la de Bootstrap y el proyecto ya se apoya en el sistema de componentes de Bootstrap
para todo lo demás (Principio VI).

## Decisión 3: Altura de la barra — delgada, no el `progress` de altura completa por defecto

**Decisión**: Se aplica `style="height: 0.5rem;"` (o una clase de utilidad equivalente) al contenedor
`.progress`, en vez de la altura por defecto de Bootstrap (~1rem).

**Rationale**: FR-006 exige que la barra "acompañe" el texto sin reemplazarlo — en la columna "Avance" de
"Registro de Pagos" (`.fila-seguimiento-pagos__avance`, ancho `minmax(12rem, 1fr)`) y en la cabecera de la
tarjeta de Pagos del recibo, el texto numérico sigue siendo el dato principal y la barra es un refuerzo
secundario debajo de él; una barra más delgada dejar claro visualmente cuál de los dos es el dato principal
sin necesitar tamaños de fuente distintos (evitando además una cuarta escala tipográfica, prohibida por el
"No-Decoration Rule" de `DESIGN.md`).

**Alternatives considered**: Altura por defecto de Bootstrap — descartado, competiría visualmente con el
texto en vez de acompañarlo.
