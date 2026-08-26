/**
 * specs/023 (research.md Decisiones 4-5): abre el modal compartido tras cargar su
 * contenido por htmx (en vez de con data-bs-toggle, para evitar el "flash" de
 * contenido viejo mientras la petición GET todavía está en curso), lo cierra tras
 * una confirmación exitosa, y muestra el error dentro del modal sin cerrarlo
 * cuando el servidor rechaza la generación (concepto ya cubierto, validación).
 */

function modalRecibo() {
    return window.bootstrap?.Modal.getOrCreateInstance(document.getElementById('modal-recibo-registro-masivo'));
}

document.addEventListener('htmx:afterSwap', (evento) => {
    if (evento.detail.target.id === 'contenido-modal-recibo') {
        modalRecibo()?.show();
    }
});

document.addEventListener('htmx:afterRequest', (evento) => {
    if (evento.detail.successful && evento.detail.elt.id === 'formulario-modal-recibo') {
        modalRecibo()?.hide();
    }
});

document.addEventListener('htmx:responseError', (evento) => {
    if (evento.detail.elt.id === 'formulario-modal-recibo') {
        const contenedorErrores = document.getElementById('errores-modal-recibo');
        if (contenedorErrores) {
            contenedorErrores.innerHTML = evento.detail.xhr.response;
        }
    }
});
