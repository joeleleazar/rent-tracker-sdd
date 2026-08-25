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

    const lecturaAnterior = leerNumero(campo.dataset.lecturaAnterior);
    const inputLectura = campo.querySelector('input[type="number"]');

    if (lecturaAnterior === null || !inputLectura) {
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
        const total = document.getElementById(`total-fila-${locacionId}`);

        if (!total) {
            return;
        }

        const consumo = calcularConsumoDeCampo(campo);

        if (consumo === null || tarifa === null) {
            total.textContent = '—';
            return;
        }

        const valor = consumo * tarifa;
        total.textContent = valor.toFixed(2);
        totalGeneral += valor;
        huboTotal = true;
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
}

document.addEventListener('DOMContentLoaded', enganchar);
// htmx reemplaza celdas tras el autoguardado, la edición en línea y el
// guardado de la tarifa (specs/011); hay que reenganchar y recalcular cada vez.
document.addEventListener('htmx:afterSettle', enganchar);
