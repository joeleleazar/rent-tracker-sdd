/**
 * specs/044 (US1 y US2): recálculo en vivo de la vista previa editable de una
 * importación por plantilla. Puramente presentacional — el servidor revalida
 * todo al confirmar. Cubre dos tablas:
 *
 *  - `.tabla-vista-previa-lecturas`: por fila recalcula "consumo" y "total
 *    sugerido" (consumo × tarifa global de la pantalla) y marca la fila como
 *    válida/ con error (lectura actual numérica ≥ 0 y ≥ lectura anterior).
 *  - `.tabla-vista-previa-recibos`: por fila recalcula "total sugerido"
 *    (renta + luz + Σ conceptos); el input de total sigue al sugerido hasta
 *    que el usuario lo edita a mano (`dataset.editado`).
 *
 * También actualiza el contador "N válidas · M con error" y habilita/
 * deshabilita el botón "Confirmar importación".
 */

function leerNumero(valor) {
    if (valor === null || valor === undefined || String(valor).trim() === '') {
        return null;
    }
    const numero = Number.parseFloat(String(valor).replace(',', '.'));
    return Number.isFinite(numero) ? numero : null;
}

function tarifaGlobal() {
    return leerNumero(document.getElementById('tarifa_kwh')?.value);
}

function marcarFila(fila, esValida, motivos) {
    fila.dataset.valida = esValida ? 'true' : 'false';
    const badge = fila.querySelector('[data-badge-estado]');
    if (badge) {
        badge.textContent = esValida ? 'Válida' : 'Con error';
        badge.classList.toggle('bg-success', esValida);
        badge.classList.toggle('bg-danger', !esValida);
    }
    const celdaMotivos = fila.querySelector('[data-motivos-cliente]');
    if (celdaMotivos) {
        celdaMotivos.textContent = esValida ? '' : motivos.join(' ');
    }
}

function recalcularFilaLectura(fila) {
    // Motivos de servidor (local inexistente, periodo distinto…) no se pueden
    // revertir en cliente: si la fila ya venía con ese tipo de error, no se
    // "arregla" tocando la lectura.
    const errorNoRecuperable = fila.dataset.errorServidor === 'true';
    const anterior = leerNumero(fila.dataset.lecturaAnterior) ?? 0;
    const input = fila.querySelector('input[data-campo="lectura_actual"]');
    const actual = leerNumero(input?.value);

    const consumo = actual === null ? null : actual - anterior;
    const celdaConsumo = fila.querySelector('[data-celda="consumo"]');
    if (celdaConsumo) {
        celdaConsumo.textContent = consumo === null ? '—' : consumo.toFixed(2);
    }

    const tarifa = tarifaGlobal();
    const total = consumo === null || tarifa === null ? null : consumo * tarifa;
    const celdaTotal = fila.querySelector('[data-celda="total_sugerido"]');
    if (celdaTotal) {
        celdaTotal.textContent = total === null ? '—' : total.toFixed(2);
    }

    const motivos = [];
    if (actual === null) {
        motivos.push('Ingrese la lectura actual.');
    } else if (actual < 0) {
        motivos.push('La lectura no puede ser negativa.');
    } else if (consumo !== null && consumo < 0) {
        motivos.push('La lectura actual es menor que la del periodo anterior.');
    }

    marcarFila(fila, !errorNoRecuperable && motivos.length === 0, motivos);
}

function recalcularFilaRecibo(fila) {
    const errorNoRecuperable = fila.dataset.errorServidor === 'true';
    let suma = 0;
    fila.querySelectorAll('input[data-componente]').forEach((input) => {
        suma += leerNumero(input.value) ?? 0;
    });

    const celdaSugerido = fila.querySelector('[data-celda="total_sugerido"]');
    if (celdaSugerido) {
        celdaSugerido.textContent = suma.toFixed(2);
    }

    const inputTotal = fila.querySelector('input[data-campo="total"]');
    if (inputTotal && inputTotal.dataset.editado !== 'true') {
        inputTotal.value = suma.toFixed(2);
    }

    const motivos = [];
    fila.querySelectorAll('input[data-componente], input[data-campo="total"]').forEach((input) => {
        const valor = leerNumero(input.value);
        if (input.value.trim() !== '' && (valor === null || valor < 0)) {
            motivos.push('Los montos deben ser números mayores o iguales a 0.');
        }
    });

    marcarFila(fila, !errorNoRecuperable && motivos.length === 0, [...new Set(motivos)]);
}

function recalcularTabla(tabla) {
    const esRecibos = tabla.classList.contains('tabla-vista-previa-recibos');
    const filas = tabla.querySelectorAll('[data-fila]');
    let validas = 0;

    filas.forEach((fila) => {
        if (esRecibos) {
            recalcularFilaRecibo(fila);
        } else {
            recalcularFilaLectura(fila);
        }
        if (fila.dataset.valida === 'true') {
            validas += 1;
        }
    });

    const contador = tabla.parentElement.querySelector('[data-contador-vista-previa]');
    if (contador) {
        const conError = filas.length - validas;
        contador.textContent = `${validas} válida${validas === 1 ? '' : 's'} · ${conError} con error`;
    }

    const boton = tabla.parentElement.querySelector('[data-confirmar-importacion]');
    if (boton) {
        boton.disabled = validas === 0;
    }
}

function engancharTabla(tabla) {
    tabla.querySelectorAll('input[data-campo="total"]').forEach((input) => {
        input.addEventListener('input', () => {
            input.dataset.editado = 'true';
        });
    });

    tabla.addEventListener('input', (evento) => {
        if (evento.target.matches('input[data-campo], input[data-componente]')) {
            const fila = evento.target.closest('[data-fila]');
            recalcularTabla(tabla);
            if (fila) {
                // recalcular la tabla ya recorre todas las filas; se llama una
                // sola vez para no duplicar trabajo por tecla.
            }
        }
    });

    recalcularTabla(tabla);
}

function enganchar() {
    document
        .querySelectorAll('.tabla-vista-previa-lecturas, .tabla-vista-previa-recibos')
        .forEach((tabla) => {
            if (tabla.dataset.enganchada === 'true') {
                return;
            }
            tabla.dataset.enganchada = 'true';
            engancharTabla(tabla);
        });

    // La tarifa global afecta el total sugerido de la vista previa de lecturas.
    const inputTarifa = document.getElementById('tarifa_kwh');
    if (inputTarifa && inputTarifa.dataset.enganchadaVistaPrevia !== 'true') {
        inputTarifa.dataset.enganchadaVistaPrevia = 'true';
        inputTarifa.addEventListener('input', () => {
            document
                .querySelectorAll('.tabla-vista-previa-lecturas')
                .forEach((tabla) => recalcularTabla(tabla));
        });
    }
}

document.addEventListener('DOMContentLoaded', enganchar);
// htmx inyecta la vista previa como respuesta parcial tras subir el archivo.
document.addEventListener('htmx:afterSettle', enganchar);
