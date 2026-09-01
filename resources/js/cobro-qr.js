/**
 * specs/044 (US3 — cobro por QR): inicializa el lector de cámara en la vista
 * de cobro (`/cobro`). Degradación elegante — si no hay cámara, no hay permiso
 * o `html5-qrcode` no cargó, se oculta el bloque de cámara y queda operativo
 * el ingreso manual del número de recibo (que funciona sin JavaScript).
 *
 * Al decodificar un QR cuyo texto es una URL de `cobro.recibo` de este mismo
 * host, se navega a ella (lleva la firma de `URL::signedRoute`).
 */

async function iniciarLector() {
    const contenedor = document.getElementById('lector-qr');
    if (!contenedor) {
        return;
    }

    const soporteCamara = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    const libreriaDisponible = typeof window.Html5Qrcode !== 'undefined';

    if (!soporteCamara || !libreriaDisponible) {
        ocultarBloqueCamara();
        return;
    }

    const bloque = document.getElementById('bloque-camara-cobro');
    if (bloque) {
        bloque.hidden = false;
    }

    const lector = new window.Html5Qrcode('lector-qr');
    const config = { fps: 10, qrbox: { width: 240, height: 240 } };

    const alLeer = (textoDecodificado) => {
        let url;
        try {
            url = new URL(textoDecodificado);
        } catch (error) {
            return; // no es una URL — se ignora y se sigue escaneando
        }

        if (url.host !== window.location.host || !url.pathname.includes('/cobro/recibo/')) {
            return;
        }

        lector.stop().catch(() => {}).finally(() => {
            window.location.assign(url.toString());
        });
    };

    try {
        await lector.start({ facingMode: 'environment' }, config, alLeer, () => {});
    } catch (error) {
        ocultarBloqueCamara();
    }
}

function ocultarBloqueCamara() {
    const bloque = document.getElementById('bloque-camara-cobro');
    if (bloque) {
        bloque.hidden = true;
    }
    const aviso = document.getElementById('aviso-sin-camara');
    if (aviso) {
        aviso.hidden = false;
    }
    const manual = document.getElementById('numero-recibo-cobro');
    if (manual) {
        manual.focus();
    }
}

document.addEventListener('DOMContentLoaded', iniciarLector);
