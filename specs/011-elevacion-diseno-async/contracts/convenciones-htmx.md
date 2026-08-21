# Contrato de Interfaz: Convenciones de htmx y Ruta Raíz

**Feature**: `011-elevacion-diseno-async` | **Date**: 2026-08-21

## Ruta raíz (US2)

| Método | Ruta | Comportamiento | Notas |
|---|---|---|---|
| GET | `/` | `redirect()->route('dashboard')` si hay sesión; `redirect()->route('login')` si no | Reemplaza `return view('welcome')`. Sin cambios en `routes/auth.php` ni en el middleware `auth`/`guest` ya existentes (ver `research.md` §2) |

Rutas ya existentes que participan de este contrato sin modificarse:

- `route('login')` (`routes/auth.php`, middleware `guest`): ya redirige a `route('dashboard')` si el visitante ya tiene sesión.
- Cualquier ruta bajo `Route::middleware('auth')` (`routes/web.php`): ya redirige a `route('login')` si no hay sesión, y Laravel recuerda la URL original vía `redirect()->intended()` en `AuthenticatedSessionController::store()`.

## Convenciones de `hx-boost` (US3)

- `hx-boost="true"` se aplica **una sola vez**, en el elemento contenedor de nivel superior dentro de `<body>` en `resources/views/components/layouts/app-bootstrap.blade.php`. No se agrega `hx-boost` individualmente en cada vista ni formulario — todo enlace/formulario dentro de ese contenedor queda boosteado automáticamente por herencia, salvo que se excluya explícitamente.
- Para excluir un elemento puntual del boosting (si alguna interacción no debe comportarse como navegación, ej. un enlace externo o `target="_blank"`), se usa el atributo `hx-boost="false"` directamente sobre ese elemento — no se retira el boosting global por esto.
- Ningún formulario requiere atributos `hx-post`/`hx-put`/`hx-delete`/`hx-target` explícitos para el comportamiento base: al estar dentro de un contenedor boosteado, htmx respeta el `method`/`action` ya declarados en el HTML (`@method('PUT')`, `@method('DELETE')` de Blade siguen funcionando igual, htmx los traduce a la petición HTTP correcta).
- Los modales de confirmación (`x-modal-bootstrap`) que contienen un `<form>` de eliminación siguen funcionando sin cambios: al estar presentes en el HTML inicial de la página (no inyectados dinámicamente después del boost), quedan boosteados igual que cualquier otro formulario.
- Los eventos JS centralizados en `resources/js/htmx.js` (bloqueo de doble envío, mensaje de error de red) se enganchan sobre `document` una sola vez; ninguna vista individual necesita JS propio para obtener este comportamiento.

## Formato de respuesta del servidor

**Sin cambios.** Ningún controlador, Form Request ni vista devuelve un formato de respuesta distinto al actual (HTML completo vía Blade, redirecciones 302 con `withErrors()`/`with('mensaje', ...)`). htmx consume exactamente esas mismas respuestas; no existe un "modo JSON" ni un "modo parcial" que el backend deba implementar para esta feature.

## Accesibilidad y anuncios de cambio de contenido

- Tras cada swap boosteado, htmx dispara el evento `htmx:afterSettle`; el listener centralizado usa este evento únicamente para rehabilitar botones (FR-009) y no para anunciar el cambio a lectores de pantalla de forma adicional, ya que htmx boost actualiza `document.title` y hace foco de accesibilidad de forma nativa equivalente a una navegación normal.
