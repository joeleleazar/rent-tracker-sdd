/**
 * Modal de confirmación de borrado, compartido por todas las miniaturas de
 * `resources/views/contratos/partials/galeria-documentos.blade.php`
 * (specs/002-gestion-contratos). Reemplaza el modal Alpine.js genérico
 * original (evento `abrir-confirmacion-borrado`) por el patrón nativo de
 * Bootstrap para modales con contenido variable según el botón que los abre:
 * https://getbootstrap.com/docs/5.3/components/modal/#varying-modal-content
 *
 * Cada botón "Eliminar" trae `data-accion` con la URL del formulario de
 * borrado de ESE documento; al abrirse el modal (`show.bs.modal`), se lee
 * `event.relatedTarget.dataset.accion` y se fija como `action` del único
 * formulario del modal.
 */
document.addEventListener('DOMContentLoaded', () => {
    const modalElemento = document.getElementById('confirmar-borrado-documento');

    if (!modalElemento || !window.bootstrap) {
        return;
    }

    modalElemento.addEventListener('show.bs.modal', (evento) => {
        const boton = evento.relatedTarget;
        const accion = boton?.dataset?.accion;

        if (accion) {
            modalElemento.querySelector('form').setAttribute('action', accion);
        }
    });
});
