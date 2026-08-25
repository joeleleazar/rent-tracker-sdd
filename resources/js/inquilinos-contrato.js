/**
 * Interactividad en JS nativo (sin Alpine.js) de
 * `resources/views/contratos/partials/inquilinos-contrato.blade.php`
 * (specs/003-representantes-contrato, renombrado desde representantes-contrato.js
 * en la corrección 2026-08-23: el inquilino ES el representante del contrato).
 *
 * Cubre dos escenarios:
 *
 * 1. Editor dinámico de filas al crear un contrato (`#editor-inquilinos`):
 *    agregar/quitar filas, reindexar sus `name="inquilinos[i][...]"`, y
 *    exigir exactamente un "Inquilino Principal" cuando hay más de una
 *    fila (radio `principal_index`).
 * 2. Búsqueda por DNI (autocompletar apellidos/nombres/fecha de nacimiento)
 *    tanto en cada fila del editor dinámico como en el modal "Agregar Otro
 *    Inquilino" de un contrato ya existente.
 */

async function buscarInquilinoPorDni(url, dni) {
    if (!dni) {
        return null;
    }

    const respuesta = await fetch(`${url}?q=${encodeURIComponent(dni)}`);
    const datos = await respuesta.json();

    return datos.inquilinos && datos.inquilinos.length > 0 ? datos.inquilinos[0] : null;
}

function inicializarEditorDinamicoInquilinos() {
    const contenedor = document.getElementById('editor-inquilinos');

    if (!contenedor) {
        return;
    }

    const listaFilas = contenedor.querySelector('[data-filas-inquilinos]');
    const plantilla = document.getElementById('plantilla-fila-inquilino');
    const botonAgregar = contenedor.querySelector('[data-agregar-fila-inquilino]');
    const urlBuscar = contenedor.dataset.buscarUrl;

    function reindexarFilas() {
        const filas = listaFilas.querySelectorAll('.fila-inquilino');

        filas.forEach((fila, indice) => {
            fila.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace(/inquilinos\[(?:\d+|__index__)\]/, `inquilinos[${indice}]`);
            });

            const radio = fila.querySelector('input[type="radio"]');
            if (radio) {
                radio.value = String(indice);
            }
        });

        const permiteQuitarYElegirPrincipal = filas.length > 1;

        filas.forEach((fila) => {
            fila.querySelector('.fila-principal-wrapper').classList.toggle('d-none', !permiteQuitarYElegirPrincipal);
            fila.querySelector('.btn-quitar-fila-inquilino').classList.toggle('d-none', !permiteQuitarYElegirPrincipal);
        });

        if (permiteQuitarYElegirPrincipal && !listaFilas.querySelector('input[type="radio"]:checked')) {
            const primerRadio = listaFilas.querySelector('input[type="radio"]');
            if (primerRadio) {
                primerRadio.checked = true;
            }
        }
    }

    function vincularFila(fila) {
        fila.querySelector('.btn-buscar-dni-fila').addEventListener('click', async () => {
            const dni = fila.querySelector('.campo-dni-fila').value;
            const encontrado = await buscarInquilinoPorDni(urlBuscar, dni);

            if (encontrado) {
                fila.querySelector('.campo-apellidos-fila').value = encontrado.apellidos;
                fila.querySelector('.campo-nombres-fila').value = encontrado.nombres;
                fila.querySelector('.campo-fecha-nacimiento-fila').value = encontrado.fecha_nacimiento.substring(0, 10);
                fila.querySelector('.campo-inquilino-id-fila').value = encontrado.id;
            }
        });

        fila.querySelector('.btn-quitar-fila-inquilino').addEventListener('click', () => {
            fila.remove();
            reindexarFilas();
        });
    }

    function agregarFila() {
        const nueva = plantilla.content.cloneNode(true);
        listaFilas.appendChild(nueva);
        vincularFila(listaFilas.lastElementChild);
        reindexarFilas();
    }

    listaFilas.querySelectorAll('.fila-inquilino').forEach(vincularFila);
    botonAgregar.addEventListener('click', agregarFila);
    reindexarFilas();
}

function inicializarBusquedaDniModalAgregarInquilino() {
    const formulario = document.getElementById('formulario-agregar-inquilino');

    if (!formulario) {
        return;
    }

    const urlBuscar = formulario.dataset.buscarUrl;

    formulario.querySelector('.btn-buscar-dni-modal').addEventListener('click', async () => {
        const dni = formulario.querySelector('#nuevo_dni').value;
        const encontrado = await buscarInquilinoPorDni(urlBuscar, dni);

        if (encontrado) {
            formulario.querySelector('#nuevo_apellidos').value = encontrado.apellidos;
            formulario.querySelector('#nuevo_nombres').value = encontrado.nombres;
            formulario.querySelector('#nueva_fecha_nacimiento').value = encontrado.fecha_nacimiento.substring(0, 10);
            formulario.querySelector('#nuevo_inquilino_id').value = encontrado.id;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    inicializarEditorDinamicoInquilinos();
    inicializarBusquedaDniModalAgregarInquilino();
});
