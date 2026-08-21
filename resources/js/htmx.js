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
