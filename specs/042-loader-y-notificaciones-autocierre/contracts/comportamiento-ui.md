# Contrato de Interfaz: Notificaciones Efímeras y Barra de Carga de Navegación

No hay contrato HTTP nuevo (ni rutas, ni JSON, ni parámetros). Esta feature define dos contratos de
**comportamiento de interfaz** que la implementación debe cumplir y que `quickstart.md` verifica.

---

## A. Componente `x-mensaje-alerta`

### Entrada (props) — sin cambios

| Prop | Valores | Default |
|------|---------|---------|
| `tipo` | `exito` \| `error` | `exito` |
| slot | contenido del mensaje (texto/HTML) | — |
| `$attributes` | se fusionan sobre `class` (p. ej. `class="mb-4"`) | — |

### Salida (HTML renderizado) — cambios

Antes:

```html
<div class="alert alert-success d-flex align-items-start gap-2" role="alert">
  <i class="bi bi-check-circle-fill fs-5 flex-shrink-0" aria-hidden="true"></i>
  <div class="flex-grow-1">{{ slot }}</div>
</div>
```

Después (contrato):

```html
<div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
  <i class="bi bi-check-circle-fill fs-5 flex-shrink-0" aria-hidden="true"></i>
  <div class="flex-grow-1">{{ slot }}</div>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
```

- **DEBE** conservar `role="alert"`, el ícono de apoyo y las clases `alert-success` / `alert-danger` según
  `tipo`.
- **DEBE** añadir `alert-dismissible fade show` y el `btn-close` con `data-bs-dismiss="alert"` y
  `aria-label="Cerrar"` (en español).
- El `btn-close` **DEBE** quedar accesible por teclado (es un `<button>` nativo; sin `tabindex` negativo).
- La fusión de `$attributes` **DEBE** seguir funcionando (una vista que pasa `class="mb-4"` sigue obteniendo
  ese margen).

### Comportamiento (JS, `resources/js/bootstrap.js`)

- Al cargar la página (`DOMContentLoaded`) y tras cada intercambio boosteado (`htmx:afterSettle`), **DEBE**
  localizar cada `.alert.alert-dismissible` visible y armarle un temporizador de **8 000 ms**.
- Mientras el puntero esté sobre el nodo (`mouseenter` … `mouseleave`) o el foco de teclado esté dentro
  (`focusin` … `focusout`), el temporizador **DEBE** estar cancelado.
- Al salir el puntero y el foco, el temporizador **DEBE** rearmarse a **8 000 ms completos**.
- Al agotarse, **DEBE** cerrarse vía `bootstrap.Alert.getOrCreateInstance(el).close()`.
- El mismo nodo no **DEBE** recibir dos temporizadores simultáneos (marcar con un flag/dataset al procesarlo).
- **DEBE** aplicarse por igual a `tipo="exito"` y `tipo="error"`.

### Degradación

- Sin JS: el nodo permanece visible indefinidamente; el `btn-close` no cierra (requiere Bootstrap JS) — la
  notificación es totalmente persistente, sin romper nada (FR-007).
- `prefers-reduced-motion: reduce`: `.alert.fade` **DEBE** tener `transition: none` (cierre instantáneo).

---

## B. Barra de carga de navegación

### Markup (en `resources/views/components/layouts/app-bootstrap.blade.php`, al inicio de `<body>`)

```html
<div class="barra-carga-navegacion progress d-none" aria-hidden="true">
  <div class="progress-bar" style="width: 0%"></div>
</div>
```

- Posición **fija**, borde superior, ancho completo, alto ~3 px, `z-index` por encima del contenido y del
  sidebar.
- Oculta por defecto (`d-none` u `opacity:0` + `visibility:hidden`).

### Comportamiento (JS, `resources/js/htmx.js`)

| Evento htmx | Condición | Acción sobre la barra |
|-------------|-----------|-----------------------|
| `htmx:beforeRequest` | verbo `GET` (o disparador `<a>` / no dentro de `<form>`) | armar `setTimeout(150 ms)` → al cumplirse: quitar `d-none`, animar `progress-bar` hacia ~90 % |
| `htmx:beforeRequest` | verbo `POST` / `PUT` / `PATCH` / `DELETE` (o disparador `<form>`) | **no hacer nada** |
| `htmx:beforeSwap` | siempre | si hay `setTimeout` pendiente, `clearTimeout`; si la barra está visible, llevar a 100 % y ocultar |
| `htmx:afterRequest` | `detail.successful === true` | idem `beforeSwap` (red de seguridad) |
| `htmx:sendError` | siempre | `clearTimeout` + ocultar de inmediato |
| `htmx:responseError` | siempre (incl. 4xx y 5xx) | `clearTimeout` + ocultar de inmediato |
| `htmx:abort` | siempre | `clearTimeout` + ocultar de inmediato |

- El nombre exacto de la propiedad del verbo (`detail.requestConfig.verb` en htmx 2.x) **DEBE** verificarse
  contra htmx 2.0.10 al implementar; el respaldo por `tagName` / `closest('form')` es aceptable.
- La barra **NO DEBE** recibir foco ni interceptar clics del usuario.
- Solo puede haber una barra activa a la vez; una navegación nueva reinicia el ciclo.

### Invariantes verificables

- **INV-1**: tras completarse, fallar o abortarse cualquier navegación, la barra queda oculta (0 casos de
  barra colgada) — SC-005.
- **INV-2**: un envío de formulario no muestra la barra; su feedback es el botón «Guardando…» — SC-006.
- **INV-3**: una navegación < 150 ms no muestra la barra de forma perceptible — FR-011.
- **INV-4**: una navegación > ~150 ms muestra la barra dentro de 1 s de iniciada — SC-004.

### Degradación

- Sin JS: los listeners no se registran; la barra queda en `d-none` para siempre; navegación clásica con el
  indicador nativo del navegador.
- `prefers-reduced-motion: reduce`: `.barra-carga-navegacion .progress-bar { transition: none }` — la barra
  aparece/desaparece sin animación de ancho.

---

## C. No-regresión (contrato negativo)

- **NINGUNA** ruta, controlador, Form Request, mensaje flash de sesión ni comportamiento de negocio cambia
  (FR-016).
- Las 433 pruebas automatizadas actuales **DEBEN** seguir pasando sin cambios (FR-017 / SC-007).
- `npm run build` **DEBE** terminar sin errores.
- El texto de cada notificación, su `tipo` y el momento en que aparece **DEBEN** ser idénticos a hoy en las
  23 vistas que usan el componente.
