import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler);

/**
 * Gráfico de consumo histórico de medidor (FR-005, specs/010-migracion-interfaz-bootstrap,
 * mejora nueva sobre specs/006-historial-lectura-medidor). Se alimenta del mismo
 * arreglo {periodo, consumo} que ya calcula/muestra la tabla histórica existente
 * en resources/views/locaciones/lecturas/index.blade.php — sin ninguna lógica de
 * cálculo nueva, solo su visualización como gráfico de líneas.
 */
document.addEventListener('DOMContentLoaded', () => {
    const lienzo = document.getElementById('grafico-consumo-medidor');

    if (!lienzo) {
        return;
    }

    let datos;
    try {
        datos = JSON.parse(lienzo.dataset.consumos || '[]');
    } catch (error) {
        return;
    }

    if (!Array.isArray(datos) || datos.length === 0) {
        return;
    }

    // La tabla histórica se muestra del periodo más reciente al más antiguo
    // (orderByDesc); para que el gráfico se lea de izquierda a derecha en
    // orden cronológico, se invierte solo el arreglo usado por el gráfico —
    // el orden de la tabla no cambia.
    const datosCronologicos = [...datos].reverse();

    // eslint-disable-next-line no-new
    new Chart(lienzo, {
        type: 'line',
        data: {
            labels: datosCronologicos.map((punto) => punto.periodo),
            datasets: [{
                label: 'Consumo por periodo',
                data: datosCronologicos.map((punto) => punto.consumo),
                borderColor: '#1e40af',
                backgroundColor: 'rgba(30, 64, 175, 0.15)',
                borderWidth: 3,
                pointBackgroundColor: '#1e40af',
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.15,
                fill: true,
                spanGaps: true,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            font: {
                size: 18,
            },
            plugins: {
                legend: {
                    display: true,
                    labels: {
                        font: { size: 18 },
                        color: '#111827',
                    },
                },
                tooltip: {
                    bodyFont: { size: 18 },
                    titleFont: { size: 18 },
                },
            },
            scales: {
                x: {
                    ticks: { font: { size: 18 }, color: '#111827' },
                },
                y: {
                    beginAtZero: true,
                    ticks: { font: { size: 18 }, color: '#111827' },
                },
            },
        },
    });
});
