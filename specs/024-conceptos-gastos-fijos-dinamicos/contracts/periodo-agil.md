# Contrato: Cambio de Periodo Ágil (Registro Masivo de Lecturas y de Recibos)

Sin rutas nuevas — cambia el contrato de interacción de `GET lecturas.registroMasivo.index` y
`GET recibos.registroMasivo.index`, ambas ya existentes.

## Comportamiento

- Cada pantalla renderiza dos enlaces «‹ Anterior» / «Siguiente ›» junto al selector de mes, con
  `hx-get` hacia la misma ruta de índice, `periodo` igual al mes actualmente mostrado ±1 (calculado en el
  servidor con `Carbon`, research.md Decisión 7), y `hx-target`/`hx-swap` apuntando al contenedor de toda la
  tabla (encabezado + filas) — no solo las filas, porque el periodo también aparece en el propio selector de
  mes y debe quedar sincronizado.
- El `<input type="month">` del selector agrega `hx-get` (misma ruta), `hx-trigger="change"`, mismo
  `hx-target`/`hx-swap` — el formulario que lo envuelve pierde su botón "Cambiar Periodo" y su necesidad de
  `method="GET"` con submit manual.
- Ambos casos responden con la vista completa de índice (o una parcial equivalente al contenido que cambia)
  — se prefiere lo más simple que no rompa el resto de la pantalla (ej. el contenedor de modal compartido de
  `recibos/registro-masivo/index.blade.php`, specs/023, permanece fuera del área que se reemplaza).
- Doble clic rápido en una flecha (Edge Case de spec.md): htmx ya cancela por defecto una petición en vuelo
  del mismo elemento cuando llega una nueva de ese mismo elemento (comportamiento estándar de htmx, sin
  configuración adicional) — se verifica en `quickstart.md`, no se implementa lógica propia de cancelación.

## Fuera de alcance

Los flujos individuales (`locaciones.lecturas.create`, `locaciones.recibos.create`) no tienen este patrón de
navegación de periodo — conservan su formulario `GET` con botón, ya que no listan múltiples locaciones para
un mismo periodo (Assumption A-004 de spec.md).
