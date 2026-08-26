<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante de Recibo #{{ $recibo->id }}</title>
    {{ \Illuminate\Support\Facades\Vite::fonts() }}
    @vite(['resources/js/recibo-comprobante.js'])
    <style>
        /*
         * Esta vista NO usa Tailwind/app.css (a diferencia del resto del proyecto):
         * html2canvas 1.4.x clona el documento COMPLETO al capturar una imagen
         * (US2) y no puede parsear las funciones de color oklch() que Tailwind v4
         * genera por defecto para cualquier clase usada en la página, aunque esté
         * fuera del elemento capturado — lanza
         * "Attempting to parse an unsupported color function oklch" y aborta toda
         * la captura. Por eso esta página entera usa CSS propio con colores
         * hexadecimales, manteniendo el mismo criterio de diseño moderno y alto
         * contraste que el resto del proyecto expresa vía Bootstrap.
         */
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 1.5rem;
            background: #f9fafb;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            color: #111827;
        }
        .barra-acciones {
            margin: 0 auto 1.5rem auto;
            max-width: 42rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1.25rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 0.375rem;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primario {
            background: #1e40af;
            color: #ffffff;
        }
        .btn-secundario {
            background: #ffffff;
            color: #111827;
            border-color: #374151;
        }
        .estado-envio {
            margin: 0 auto 1.5rem auto;
            max-width: 42rem;
            border: 1px solid #1e40af;
            background: #eff6ff;
            color: #1e3a8a;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 0.375rem;
        }
        .oculto {
            display: none;
        }
        #comprobante-recibo {
            position: relative;
            margin: 0 auto;
            max-width: 42rem;
            background: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 2rem;
        }
        #comprobante-recibo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 1.5rem 0;
        }
        #comprobante-recibo dl {
            margin: 0;
        }
        #comprobante-recibo .fila {
            margin-bottom: 1rem;
        }
        #comprobante-recibo dt {
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
        }
        #comprobante-recibo dd {
            font-size: 1rem;
            color: #111827;
            margin: 0;
            font-variant-numeric: tabular-nums;
        }
        #comprobante-recibo .fila-total {
            border-top: 1px solid #d1d5db;
            padding-top: 1rem;
            margin-top: 1rem;
        }
        #comprobante-recibo .fila-total dt,
        #comprobante-recibo .fila-total dd {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }
        #comprobante-recibo .marca-anulado {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        #comprobante-recibo .marca-anulado span {
            transform: rotate(-20deg);
            border: 4px solid #991b1b;
            border-radius: 0.375rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.5rem 2rem;
            font-size: 2.25rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #991b1b;
        }

        {{--
            Reglas de impresión (specs/012, FR-008): ocultan la navegación y los
            controles de interacción (ya marcados `.no-imprimir`) y ajustan el
            comprobante a un documento limpio de una sola columna, sin la
            decoración de "tarjeta flotante" pensada para pantalla.
        --}}
        @media print {
            .no-imprimir {
                display: none !important;
            }
            body {
                background: white !important;
                padding: 0;
            }
            #comprobante-recibo {
                max-width: none;
                border: none;
                border-radius: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="barra-acciones no-imprimir">
        <button type="button" id="btn-imprimir-recibo" class="btn btn-primario">Imprimir Recibo</button>
        <button type="button" id="btn-enviar-whatsapp" class="btn btn-primario">Enviar por WhatsApp</button>
        <a href="{{ route('recibos.show', $recibo) }}" class="btn btn-secundario">Volver al Recibo</a>
    </div>

    <p id="estado-envio-whatsapp" class="estado-envio no-imprimir oculto" role="status"></p>

    <div id="comprobante-recibo" data-recibo-id="{{ $recibo->id }}">
        @if ($recibo->estado === 'anulado')
            <div class="marca-anulado">
                <span>Anulado</span>
            </div>
        @endif

        <h1>Recibo #{{ $recibo->id }}</h1>

        <dl>
            <div class="fila">
                <dt>Locación</dt>
                <dd>{{ $recibo->locacion->nombre }}</dd>
            </div>
            <div class="fila">
                <dt>Inquilino</dt>
                <dd>{{ $recibo->contrato->inquilinoPrincipal()?->nombreCompleto() ?? '—' }}</dd>
            </div>
            <div class="fila">
                <dt>Periodo</dt>
                <dd>{{ $recibo->periodo->translatedFormat('F Y') }}</dd>
            </div>
            <div class="fila">
                <dt>Fecha de emisión</dt>
                <dd>{{ $recibo->fecha_emision->format('d/m/Y') }}</dd>
            </div>
            <div class="fila">
                <dt>Estado</dt>
                <dd>{{ ucfirst($recibo->estado) }}</dd>
            </div>

            @if ($recibo->monto_renta !== null)
                <div class="fila">
                    <dt>Alquiler</dt>
                    <dd>S/ {{ number_format((float) $recibo->monto_renta, 2) }}</dd>
                </div>
            @endif
            @foreach ($recibo->conceptos->sortBy('conceptoGastoFijo.orden') as $reciboConcepto)
                <div class="fila">
                    <dt>{{ $reciboConcepto->conceptoGastoFijo?->nombre ?? 'Concepto eliminado' }}</dt>
                    <dd>S/ {{ number_format((float) $reciboConcepto->monto, 2) }}</dd>
                </div>
            @endforeach

            <div class="fila fila-total">
                <dt>Total</dt>
                <dd>S/ {{ number_format($recibo->total(), 2) }}</dd>
            </div>
        </dl>
    </div>
</body>
</html>
