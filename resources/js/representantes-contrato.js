/**
 * Reemplazo en JS nativo (sin Alpine.js) de la interactividad de
 * `resources/views/contratos/partials/representantes-contrato.blade.php`
 * (specs/003-representantes-contrato), migrada a Bootstrap 5 en la spec
 * 010-migracion-interfaz-bootstrap.
 *
 * Cubre dos escenarios, ambos ya existentes en Alpine.js y sin ningún
 * cambio de comportamiento ni de nombres de campo:
 *
 * 1. Editor dinámico de filas al crear un contrato (`#editor-representantes`):
 *    agregar/quitar filas, reindexar sus `name="representantes[i][...]"`, y
 *    exigir exactamente un "Representante Principal" cuando hay más de una
 *    fila (radio `principal_index`).
 * 2. Búsqueda por DNI (autocompletar apellidos/nombres/fecha de nacimiento)
 *    tanto en cada fila del editor dinámico como en el modal "Agregar Otro
 *    Representante" de un contrato ya existente.
 */

async function buscarRepresentantePorDni(url, dni) {
    if (!dni) {
        return null;
    }

    const respuesta = await fetch(`${url}?q=${encodeURIComponent(dni)}`);
    const datos = await respuesta.json();

    return datos.representantes && datos.representantes.length > 0 ? datos.representantes[0] : null;
}

function inicializarEditorDinamicoRepresentantes() {
    const contenedor = document.getElementById('editor-representantes');

    if (!contenedor) {
        return;
    }

    const listaFilas = contenedor.querySelector('[data-filas-representantes]');
    const plantilla = document.getElementById('plantilla-fila-representante');
    const botonAgregar = contenedor.querySelector('[data-agregar-fila-representante]');
    const urlBuscar = contenedor.dataset.buscarUrl;

    function reindexarFilas() {
        const filas = listaFilas.querySelectorAll('.fila-representante');

        filas.forEach((fila, indice) => {
            fila.querySelectorAll('[name]').forEach((campo) => {
                campo.name = campo.name.replace(/representantes\[(?:\d+|__index__)\]/, `representantes[${indice}]`);
            });

            const radio = fila.querySelector('input[type="radio"]');
            if (radio) {
                radio.value = String(indice);
            }
        });

        const permiteQuitarYElegirPrincipal = filas.length > 1;

        filas.forEach((fila) => {
            fila.querySelector('.fila-principal-wrapper').classList.toggle('d-none', !permiteQuitarYElegirPrincipal);
            fila.querySelector('.btn-quitar-fila-representante').classList.toggle('d-none', !permiteQuitarYElegirPrincipal);
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
            const encontrado = await buscarRepresentantePorDni(urlBuscar, dni);

            if (encontrado) {
                fila.querySelector('.campo-apellidos-fila').value = encontrado.apellidos;
                fila.querySelector('.campo-nombres-fila').value = encontrado.nombres;
                fila.querySelector('.campo-fecha-nacimiento-fila').value = encontrado.fecha_nacimiento.substring(0, 10);
                fila.querySelector('.campo-representante-id-fila').value = encontrado.id;
            }
        });

        fila.querySelector('.btn-quitar-fila-representante').addEventListener('click', () => {
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

    listaFilas.querySelectorAll('.fila-representante').forEach(vincularFila);
    botonAgregar.addEventListener('click', agregarFila);
    reindexarFilas();
}

function inicializarBusquedaDniModalAgregarRepresentante() {
    const formulario = document.getElementById('formulario-agregar-representante');

    if (!formulario) {
        return;
    }

    const urlBuscar = formulario.dataset.buscarUrl;

    formulario.querySelector('.btn-buscar-dni-modal').addEventListener('click', async () => {
        const dni = formulario.querySelector('#nuevo_dni').value;
        const encontrado = await buscarRepresentantePorDni(urlBuscar, dni);

        if (encontrado) {
            formulario.querySelector('#nuevo_apellidos').value = encontrado.apellidos;
            formulario.querySelector('#nuevo_nombres').value = encontrado.nombres;
            formulario.querySelector('#nueva_fecha_nacimiento').value = encontrado.fecha_nacimiento.substring(0, 10);
            formulario.querySelector('#nuevo_representante_id').value = encontrado.id;
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    inicializarEditorDinamicoRepresentantes();
    inicializarBusquedaDniModalAgregarRepresentante();
});
