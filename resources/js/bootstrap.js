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

/**
 * Autocierre de las notificaciones de respuesta (specs/042, US1).
 *
 * Cada `<x-mensaje-alerta>` renderizado (un `.alert.alert-dismissible`) se
 * cierra solo tras `MS_AUTOCIERRE` milisegundos. El temporizador se detiene
 * mientras el puntero del ratón o el foco de teclado están dentro de la
 * notificación, y se REINICIA a la duración completa al salir (no se reanuda
 * el remanente). El botón `.btn-close` del propio componente sigue cerrando
 * al instante vía `data-bs-dismiss="alert"`.
 *
 * Sin este script la notificación queda visible de forma persistente
 * (degradación elegante, FR-007).
 *
 * Se engancha a `DOMContentLoaded` y a `htmx:afterSettle` por el mismo motivo
 * que `autoabrirModales`: con hx-boost, una navegación no vuelve a disparar
 * `DOMContentLoaded`.
 */
const MS_AUTOCIERRE = 8000;

function programarAutocierreNotificacion(alerta) {
    if (alerta.dataset.autocierreActivo === '1') {
        return;
    }

    alerta.dataset.autocierreActivo = '1';

    let temporizador = null;

    const detener = () => {
        clearTimeout(temporizador);
        temporizador = null;
    };

    const iniciar = () => {
        detener();
        temporizador = setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(alerta).close();
        }, MS_AUTOCIERRE);
    };

    alerta.addEventListener('mouseenter', detener);
    alerta.addEventListener('mouseleave', () => {
        // Si el foco de teclado sigue dentro de la notificación, no reanudar
        // todavía: lo hará el `focusout` correspondiente.
        if (!alerta.contains(document.activeElement)) {
            iniciar();
        }
    });

    alerta.addEventListener('focusin', detener);
    alerta.addEventListener('focusout', (evento) => {
        // `focusout` también salta al mover el foco entre hijos de la propia
        // notificación; solo se reanuda si el foco realmente salió de ella y
        // el puntero tampoco está encima.
        if (alerta.contains(evento.relatedTarget) || alerta.matches(':hover')) {
            return;
        }
        iniciar();
    });

    iniciar();
}

function escanearNotificaciones() {
    document
        .querySelectorAll('.alert.alert-dismissible:not([data-autocierre-activo])')
        .forEach(programarAutocierreNotificacion);
}

document.addEventListener('DOMContentLoaded', escanearNotificaciones);
document.addEventListener('htmx:afterSettle', escanearNotificaciones);
