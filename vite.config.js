import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/recibo-comprobante.js',
                'resources/css/bootstrap.scss',
                'resources/js/bootstrap.js',
                'resources/js/htmx.js',
                'resources/js/inquilinos-contrato.js',
                'resources/js/galeria-documentos.js',
                'resources/js/historial-consumo-medidor.js',
                'resources/js/costos-fijos-contrato.js',
                'resources/js/registro-masivo-lecturas.js',
                'resources/js/registro-masivo-recibos.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
