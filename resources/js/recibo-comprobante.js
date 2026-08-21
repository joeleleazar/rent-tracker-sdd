import html2canvas from 'html2canvas';

/**
 * specs/007-estado-envio-recibo, US2: captura el comprobante del recibo como
 * imagen en el propio navegador (sin generación ni almacenamiento en el
 * servidor, ver research.md §1) y la ofrece mediante el mecanismo nativo de
 * compartir del dispositivo (Web Share API con archivos), con fallback a
 * descarga si no está disponible o el envío falla (Edge Case "sin WhatsApp").
 */
function descargarArchivo(archivo) {
    const url = URL.createObjectURL(archivo);
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = archivo.name;
    document.body.appendChild(enlace);
    enlace.click();
    document.body.removeChild(enlace);
    URL.revokeObjectURL(url);
}

function inicializarEnvioWhatsapp() {
    const boton = document.getElementById('btn-enviar-whatsapp');
    const contenedor = document.getElementById('comprobante-recibo');
    const indicador = document.getElementById('estado-envio-whatsapp');

    if (!boton || !contenedor || !indicador) {
        return;
    }

    boton.addEventListener('click', async () => {
        indicador.textContent = 'Generando imagen del recibo…';
        indicador.classList.remove('oculto');

        let canvas;
        try {
            canvas = await html2canvas(contenedor, { backgroundColor: '#ffffff', scale: 2 });
        } catch (error) {
            indicador.textContent = 'Ocurrió un error al generar la imagen del recibo.';
            return;
        }

        canvas.toBlob(async (blob) => {
            if (!blob) {
                indicador.textContent = 'No se pudo generar la imagen del recibo.';
                return;
            }

            const nombreArchivo = `recibo-${contenedor.dataset.reciboId ?? 'comprobante'}.png`;
            const archivo = new File([blob], nombreArchivo, { type: 'image/png' });

            if (navigator.canShare && navigator.canShare({ files: [archivo] })) {
                try {
                    await navigator.share({
                        files: [archivo],
                        title: 'Recibo',
                        text: 'Recibo de alquiler',
                    });
                    indicador.textContent = 'Imagen del recibo lista y compartida correctamente.';
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        indicador.textContent = '';
                        indicador.classList.add('oculto');
                        return;
                    }
                    indicador.textContent = 'No se pudo completar el envío directo. Se descargó la imagen para compartirla por otro medio.';
                    descargarArchivo(archivo);
                }
            } else {
                indicador.textContent = 'Su navegador no permite compartir directamente. Se descargó la imagen para compartirla por WhatsApp u otro medio.';
                descargarArchivo(archivo);
            }
        }, 'image/png');
    });
}

function inicializarImpresion() {
    const boton = document.getElementById('btn-imprimir-recibo');

    if (!boton) {
        return;
    }

    boton.addEventListener('click', () => window.print());
}

document.addEventListener('DOMContentLoaded', () => {
    inicializarEnvioWhatsapp();
    inicializarImpresion();
});
