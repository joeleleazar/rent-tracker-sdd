/**
 * Recalcula en vivo (specs/015, FR-013/FR-014) el total por local
 * (consumo × tarifa) y el total general de la pantalla de registro masivo;
 * puramente presentacional, no envía nada al servidor — la persistencia de
 * la tarifa la maneja el propio input vía hx-patch en el evento "change".
 */

function leerNumero(valor) {
    const numero = parseFloat(valor);
    return Number.isFinite(numero) ? numero : null;
}

function calcularConsumoDeCampo(campo) {
    const consumoGuardado = leerNumero(campo.dataset.consumo);
    if (consumoGuardado !== null) {
        return consumoGuardado;
    }

    // specs/019 FR-001: sin lectura anterior registrada, se usa 0 (no "sin dato") — mismo
    // criterio que ya aplica el servidor en RegistroMasivoLecturasController::store(), para que
    // el total sugerido en vivo no quede vacío justo en el caso que motivó ese cambio.
    const lecturaAnterior = leerNumero(campo.dataset.lecturaAnterior) ?? 0;
    const inputLectura = campo.querySelector('input[type="number"]');

    if (!inputLectura) {
        return null;
    }

    const lecturaActual = leerNumero(inputLectura.value);
    return lecturaActual === null ? null : lecturaActual - lecturaAnterior;
}

function recalcularTotales() {
    const inputTarifa = document.getElementById('tarifa_kwh');
    const tarifa = inputTarifa ? leerNumero(inputTarifa.value) : null;
    let totalGeneral = 0;
    let huboTotal = false;

    document.querySelectorAll('.campo-lectura-registro-masivo').forEach((campo) => {
        const locacionId = campo.id.replace('campo-lectura-', '');
        const consumoCelda = document.getElementById(`consumo-fila-${locacionId}`);
        const total = document.getElementById(`total-fila-${locacionId}`);

        const consumo = calcularConsumoDeCampo(campo);

        if (consumoCelda) {
            consumoCelda.textContent = consumo === null ? '—' : consumo.toFixed(2);
        }

        if (!total) {
            return;
        }

        // specs/019 Decisión 3: una vez que el usuario editó a mano el total de una fila
        // pendiente, dejar de sobrescribir ese input cada vez que tipea en otra fila — su
        // valor ya dejó de ser "sugerido" para pasar a ser el que se va a guardar. El total
        // general sigue sumando lo que esté efectivamente mostrado en cada fila.
        const esInputEditable = total.tagName === 'INPUT';
        const yaEditadoManualmente = esInputEditable && total.dataset.editadoManualmente === 'true';
        const sugerido = consumo === null || tarifa === null ? null : consumo * tarifa;

        if (!yaEditadoManualmente) {
            const texto = sugerido === null ? '' : sugerido.toFixed(2);

            if (esInputEditable) {
                total.value = texto;
            } else {
                total.textContent = sugerido === null ? '—' : texto;
            }
        }

        const valorMostrado = esInputEditable ? leerNumero(total.value) : sugerido;

        if (valorMostrado !== null) {
            totalGeneral += valorMostrado;
            huboTotal = true;
        }
    });

    const totalGeneralElemento = document.getElementById('total-general-registro-masivo');
    if (totalGeneralElemento) {
        totalGeneralElemento.textContent = huboTotal ? totalGeneral.toFixed(2) : '—';
    }
}

function inicializarTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((elemento) => {
        window.bootstrap?.Tooltip.getOrCreateInstance(elemento);
    });
}

/**
 * specs/020 FR-006: Bootstrap adjunta el popup de un tooltip como un elemento flotante
 * separado (vía Popper), no como hijo del propio disparador — si htmx reemplaza ese
 * disparador (hx-swap="outerHTML", ej. al entrar en modo edición) sin destruir antes la
 * instancia, el tooltip queda huérfano y visible en pantalla. htmx:beforeCleanupElement es
 * el punto que la propia documentación de htmx recomienda para liberar este tipo de recursos
 * de terceros justo antes de que el elemento se remueva del DOM.
 */
function disponerTooltips(elemento) {
    if (!elemento?.querySelectorAll) {
        return;
    }

    const candidatos = elemento.matches?.('[data-bs-toggle="tooltip"]')
        ? [elemento, ...elemento.querySelectorAll('[data-bs-toggle="tooltip"]')]
        : elemento.querySelectorAll('[data-bs-toggle="tooltip"]');

    candidatos.forEach((el) => {
        window.bootstrap?.Tooltip.getInstance(el)?.dispose();
    });
}

document.addEventListener('htmx:beforeCleanupElement', (evento) => {
    disponerTooltips(evento.target);
});

function enganchar() {
    recalcularTotales();
    inicializarTooltips();

    const inputTarifa = document.getElementById('tarifa_kwh');
    if (inputTarifa) {
        inputTarifa.addEventListener('input', recalcularTotales);
    }

    document.querySelectorAll('.campo-lectura-registro-masivo input[type="number"]').forEach((input) => {
        input.addEventListener('input', recalcularTotales);
    });

    document.querySelectorAll('.fila-registro-masivo__total-input').forEach((input) => {
        input.addEventListener('input', () => {
            input.dataset.editadoManualmente = 'true';
        });
    });
}

document.addEventListener('DOMContentLoaded', enganchar);
// htmx reemplaza celdas tras el autoguardado, la edición en línea y el
// guardado de la tarifa (specs/011); hay que reenganchar y recalcular cada vez.
document.addEventListener('htmx:afterSettle', enganchar);
