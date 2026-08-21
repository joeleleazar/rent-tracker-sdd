/**
 * Bundle JS nativo de Bootstrap 5 (incluye Popper), usado por las vistas ya
 * migradas a `layouts/app-bootstrap.blade.php` para Modal/Collapse en vez de
 * Alpine.js (ver specs/010-migracion-interfaz-bootstrap/research.md §2).
 *
 * Se expone en `window.bootstrap` para poder inicializar componentes desde
 * scripts inline puntuales (ej. el modal genérico de borrado de documentos
 * de contratos, que necesita fijar dinámicamente la acción del formulario
 * según el botón que lo abrió).
 */
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

/**
 * Reabre automáticamente cualquier modal marcado con `data-autoshow="1"` por
 * `components/modal-bootstrap.blade.php` (prop `show`), usado por ejemplo en
 * "Eliminar cuenta" para volver a mostrar el modal de confirmación cuando la
 * contraseña ingresada es incorrecta, o en el modal de solapamiento de
 * contratos (specs/012) — la página se recarga con errores de validación y
 * el modal debe reaparecer ya abierto, igual que hacía el `x-modal` de
 * Alpine con `:show="$errors->...->isNotEmpty()"`.
 *
 * Se engancha tanto a `DOMContentLoaded` (primera carga completa de página)
 * como a `htmx:afterSettle` (specs/011: con hx-boost activo, una navegación
 * normal NUNCA vuelve a disparar `DOMContentLoaded`, solo reemplaza el
 * contenido — sin este segundo listener, un modal auto-abierto tras un
 * envío boosteado nunca se mostraría).
 */
function autoabrirModales() {
    document.querySelectorAll('.modal[data-autoshow="1"]:not(.show)').forEach((elementoModal) => {
        new bootstrap.Modal(elementoModal).show();
    });
}

document.addEventListener('DOMContentLoaded', autoabrirModales);
document.addEventListener('htmx:afterSettle', autoabrirModales);
