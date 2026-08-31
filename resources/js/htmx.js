import 'htmx.org';

/**
 * Capa de progressive enhancement (specs/011): con hx-boost activo en el
 * layout, todo enlace/formulario se comporta de forma asíncrona sin que
 * ningún controlador tenga que cambiar. Este archivo solo agrega lo que
 * htmx no resuelve por sí solo: bloqueo de doble envío y aviso explícito
 * de error de conectividad (ver research.md §4-5).
 */

function botonEnvioDe(elemento) {
    if (elemento.tagName === 'FORM') {
        return elemento.querySelector('button[type="submit"], input[type="submit"]');
    }

    return null;
}

document.addEventListener('htmx:beforeRequest', (evento) => {
    const boton = botonEnvioDe(evento.target);

    if (boton && !boton.disabled) {
        boton.dataset.textoOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = 'Guardando…';
    }
});

function restaurarBoton(evento) {
    const boton = botonEnvioDe(evento.target);

    if (boton && boton.dataset.textoOriginal !== undefined) {
        boton.disabled = false;
        boton.innerHTML = boton.dataset.textoOriginal;
        delete boton.dataset.textoOriginal;
    }
}

document.addEventListener('htmx:afterRequest', restaurarBoton);

function mostrarErrorConectividad() {
    const contenedor = document.querySelector('main');

    if (!contenedor) {
        return;
    }

    const alerta = document.createElement('div');
    alerta.className = 'alert alert-danger fs-5 fw-semibold mb-4';
    alerta.setAttribute('role', 'alert');
    alerta.textContent = 'No se pudo completar la acción por un problema de conexión. Intente nuevamente.';

    contenedor.prepend(alerta);
}

document.addEventListener('htmx:sendError', mostrarErrorConectividad);

document.addEventListener('htmx:responseError', (evento) => {
    const codigo = evento.detail.xhr.status;

    // Los 4xx (422 de validación, 403, 404) ya vienen con su propio mensaje
    // de negocio renderizado por el servidor; solo se avisa por errores de
    // servidor genuinos (5xx), conforme al Edge Case de la especificación.
    if (codigo >= 500) {
        mostrarErrorConectividad();
    }
});

/**
 * Barra de carga de navegación (specs/042, US2).
 *
 * Aparece cuando una NAVEGACIÓN boosteada (petición GET) tarda más que
 * `MS_UMBRAL_ANTIPARPADEO`, y se retira al completarse, fallar o abortarse.
 * Un envío de formulario (POST/PUT/PATCH/DELETE) NO la dispara: ya tiene el
 * botón «Guardando…» de arriba. La primera carga dura de página usa el
 * indicador nativo del navegador y no pasa por estos eventos.
 *
 * El markup vive en el layout (`app-bootstrap.blade.php`) y se re-renderiza
 * en cada navegación boosteada, así que basta con volver a localizarlo.
 */
const MS_UMBRAL_ANTIPARPADEO = 150;

let temporizadorBarraCarga = null;

function esNavegacion(evento) {
    const verbo = evento.detail && evento.detail.requestConfig && evento.detail.requestConfig.verb;

    if (typeof verbo === 'string') {
        return verbo.toLowerCase() === 'get';
    }

    // Respaldo si htmx no expone el verbo: es navegación si el disparador es
    // un enlace y no está dentro de un formulario.
    const objetivo = evento.target;

    return Boolean(objetivo && objetivo.tagName === 'A' && !objetivo.closest('form'));
}

function mostrarBarraCarga() {
    const barra = document.querySelector('.barra-carga-navegacion');

    if (!barra) {
        return;
    }

    barra.classList.remove('d-none');

    const relleno = barra.querySelector('.progress-bar');

    if (relleno) {
        void relleno.offsetWidth; // fuerza el reflow para que la transición arranque desde scaleX(0)
        relleno.style.transform = 'scaleX(0.9)';
    }
}

function ocultarBarraCarga() {
    clearTimeout(temporizadorBarraCarga);
    temporizadorBarraCarga = null;

    const barra = document.querySelector('.barra-carga-navegacion');

    if (!barra) {
        return;
    }

    const relleno = barra.querySelector('.progress-bar');

    if (relleno) {
        relleno.style.transform = 'scaleX(0)';
    }

    barra.classList.add('d-none');
}

document.addEventListener('htmx:beforeRequest', (evento) => {
    if (!esNavegacion(evento)) {
        return;
    }

    clearTimeout(temporizadorBarraCarga);
    temporizadorBarraCarga = setTimeout(mostrarBarraCarga, MS_UMBRAL_ANTIPARPADEO);
});

document.addEventListener('htmx:beforeSwap', ocultarBarraCarga);
document.addEventListener('htmx:afterRequest', ocultarBarraCarga);
document.addEventListener('htmx:sendError', ocultarBarraCarga);
document.addEventListener('htmx:responseError', ocultarBarraCarga);
document.addEventListener('htmx:abort', ocultarBarraCarga);
