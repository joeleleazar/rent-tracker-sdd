/**
 * Recalcula en vivo el "Total de Referencia" (specs/012, FR-004) como la suma
 * de los 4 costos opcionales (agua/luz/pasadizo/seguridad); no incluye
 * monto_renta, que es un campo obligatorio distinto del contrato principal
 * (ver research.md §2). Puramente presentacional: no envía nada al servidor.
 */

function recalcularTotal(grid) {
    const campos = grid.querySelectorAll('.costo-fijo-campo');
    const total = grid.querySelector('.costo-fijo-total');

    if (!total) {
        return;
    }

    const suma = Array.from(campos).reduce((acumulado, campo) => {
        const valor = parseFloat(campo.value);
        return acumulado + (Number.isFinite(valor) ? valor : 0);
    }, 0);

    total.value = suma.toFixed(2);
}

function inicializarGrid(grid) {
    recalcularTotal(grid);
    grid.querySelectorAll('.costo-fijo-campo').forEach((campo) => {
        campo.addEventListener('input', () => recalcularTotal(grid));
    });
}

function inicializarTodosLosGrids() {
    document.querySelectorAll('.costos-fijos-grid').forEach(inicializarGrid);
}

document.addEventListener('DOMContentLoaded', inicializarTodosLosGrids);
// htmx boost reemplaza el contenido tras cada navegación (specs/011); hay que
// volver a enganchar los listeners cuando eso ocurre.
document.addEventListener('htmx:afterSettle', inicializarTodosLosGrids);
