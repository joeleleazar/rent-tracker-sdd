# Data Model: Loader de Carga de Página y Notificaciones de Respuesta con Autocierre

Esta feature **no persiste datos** y no introduce entidades de dominio: no hay migraciones, modelos Eloquent,
columnas ni tablas. Los mensajes flash de sesión existentes (`session('mensaje')`, `session('status')`,
`$errors`) se consumen tal cual, sin cambio de forma ni de disparador.

Lo que sí hay son dos **máquinas de estado de interfaz**, enteramente en el cliente. Se documentan aquí como
el "modelo" de la feature.

## 1. Notificación de respuesta (`x-mensaje-alerta` renderizado)

**Origen**: el servidor renderiza el componente cuando hay un flash (`mensaje` / `status` / error de
resumen). El nodo existe en el HTML desde el primer frame.

**Atributos observables del nodo**:

| Atributo | Valor | Nota |
|----------|-------|------|
| clase base | `alert alert-success` \| `alert alert-danger` | según `tipo` |
| clases nuevas | `alert-dismissible fade show` | habilitan cierre con transición |
| `role` | `alert` | sin cambios (accesibilidad) |
| control de cierre | `<button class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar">` | nuevo |
| ícono de apoyo | `bi-check-circle-fill` \| `bi-exclamation-triangle-fill` | sin cambios |

**Estados** (por notificación, gestionados por `bootstrap.js`):

```
                 ┌────────────────────────────────────────────┐
                 │                                            │
   [render] ──▶ VISIBLE ──(8000 ms sin hover/foco)──▶ CERRÁNDOSE ──(fin fade)──▶ RETIRADA
                 │  ▲                                            ▲
   mouseenter /  │  │ mouseleave / focusout                      │
   focusin       ▼  │ (reinicia timer a 8000 ms)                 │
              PAUSADA ───────────────────────────────────────────┘
                 │
   btn-close / autocierre ──────────────────────────────────────┘
```

- **VISIBLE**: temporizador de 8 000 ms corriendo.
- **PAUSADA**: puntero o foco de teclado dentro del nodo; temporizador cancelado; no hay límite de tiempo.
- **CERRÁNDOSE**: se llamó `close()` (por timeout o por `btn-close`); corre la transición `fade`.
- **RETIRADA**: el nodo sale del DOM (comportamiento estándar de `bootstrap.Alert`).

**Transiciones**:

| Desde | Evento | Hacia | Efecto |
|-------|--------|-------|--------|
| VISIBLE | `mouseenter` / `focusin` | PAUSADA | `clearTimeout` |
| PAUSADA | `mouseleave` / `focusout` (sin hover ni foco restante) | VISIBLE | nuevo `setTimeout(8000)` |
| VISIBLE | timeout 8 000 ms | CERRÁNDOSE | `Alert.close()` |
| VISIBLE \| PAUSADA | clic en `btn-close` | CERRÁNDOSE | `Alert.close()` (Bootstrap) |
| CERRÁNDOSE | fin de la transición `fade` | RETIRADA | nodo removido |

**Reglas de validación / invariantes**:
- INV-N1: el temporizador nunca corre mientras hay hover o foco dentro del nodo (FR-002).
- INV-N2: al pasar de PAUSADA a VISIBLE, el tiempo se reinicia a 8 000 ms completos, no al remanente (FR-003).
- INV-N3: sin JavaScript, el nodo permanece en VISIBLE indefinidamente (persistente); ninguna vista se rompe
  (FR-007).
- INV-N4: `role="alert"` presente en el primer frame para que el lector de pantalla lo anuncie antes de
  cualquier cierre.
- INV-N5: éxito y error siguen la misma máquina (decisión Q1); no hay tipo "persistente por defecto".

**Degradación `prefers-reduced-motion`**: el estado CERRÁNDOSE es instantáneo (transición anulada por CSS);
la máquina es idéntica en lo demás.

## 2. Barra de carga de navegación

**Origen**: `resources/js/htmx.js`, a partir de los eventos del ciclo de vida de `hx-boost`.

**Nodo**: `div.barra-carga-navegacion` (fijo, borde superior, ~3 px) con un `div.progress-bar` interno.
Oculto por defecto.

**Estados** (global, único a la vez):

```
   IDLE ──htmx:beforeRequest (verbo GET)──▶ PENDIENTE(≤150 ms)
     ▲                                          │
     │                              150 ms sin respuesta
     │                                          ▼
     │                                       VISIBLE
     │                                          │
     │        htmx:beforeSwap / afterRequest / sendError /
     │        responseError / htmx:abort
     └──────────────────────────────────────────┘
        (limpia timeout si PENDIENTE; oculta si VISIBLE)
```

- **IDLE**: barra oculta, sin timeout armado.
- **PENDIENTE**: navegación GET en curso; `setTimeout(150 ms)` armado; barra aún oculta (anti-parpadeo).
- **VISIBLE**: barra mostrada; `progress-bar` animando su ancho hacia ~90 %.

**Transiciones**:

| Desde | Evento | Hacia | Efecto |
|-------|--------|-------|--------|
| IDLE | `htmx:beforeRequest` con verbo GET (navegación) | PENDIENTE | `setTimeout(150)` |
| IDLE | `htmx:beforeRequest` con verbo POST/PUT/PATCH/DELETE (envío de formulario) | IDLE | — (no arma nada) |
| PENDIENTE | 150 ms cumplidos | VISIBLE | mostrar barra, iniciar animación de ancho |
| PENDIENTE | `htmx:beforeSwap` / `afterRequest` / `sendError` / `responseError` / `htmx:abort` | IDLE | `clearTimeout` |
| VISIBLE | `htmx:beforeSwap` / `afterRequest` (éxito) | IDLE | ancho a 100 %, desvanecer y ocultar |
| VISIBLE | `htmx:sendError` / `htmx:responseError` / `htmx:abort` | IDLE | ocultar de inmediato |

**Reglas de validación / invariantes**:
- INV-B1: en IDLE la barra está oculta y no hay `setTimeout` pendiente (nunca "colgada", FR-010 / SC-005).
- INV-B2: una navegación resuelta en < 150 ms nunca llega a VISIBLE de forma perceptible (FR-011 / SC-004
  parte negativa).
- INV-B3: un envío de formulario nunca lleva la barra a VISIBLE por sí mismo (Q2 / FR-012 / SC-006).
- INV-B4: cualquier terminación de la petición (éxito, error de red, error 5xx, abort) lleva a IDLE
  (FR-010).
- INV-B5: la primera carga dura de página y `htmx:historyRestore` no arman la barra (usa el indicador nativo
  del navegador).

**Degradación `prefers-reduced-motion`**: la animación de ancho del `progress-bar` se anula; la barra pasa de
oculta a visible (y viceversa) sin transición. La máquina de estados no cambia.

**Sin JavaScript**: la barra nunca sale de IDLE (los listeners no se registran); la navegación es clásica con
el indicador del navegador (FR-007).
