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

        {{--
            specs/031: reformato en 6 bloques verticales separados (encabezado, metadatos, partes,
            conceptos, total, cierre — research.md Decisión 1), cada uno seguido de un
            `.separador-bloque` en vez del `<dl>` plano anterior. Jerarquía tipográfica de 3
            niveles (research.md Decisión 4): título (1.5rem), texto base (0.95rem, con variación
            de peso/mayúsculas para etiquetas), total (1.75rem) — ningún tamaño adicional.
        --}}
        #comprobante-recibo .separador-bloque {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 1.25rem 0;
        }

        #comprobante-recibo .bloque-encabezado {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }
        #comprobante-recibo .logo-comprobante {
            height: 2.5rem;
            width: auto;
            flex-shrink: 0;
        }
        #comprobante-recibo .bloque-encabezado h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        #comprobante-recibo .fila-dato {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.35rem 0;
        }
        #comprobante-recibo .fila-dato .etiqueta {
            font-size: 0.95rem;
            font-weight: 600;
            color: #374151;
        }
        #comprobante-recibo .fila-dato .valor {
            font-size: 0.95rem;
            font-weight: 400;
            color: #111827;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        #comprobante-recibo .fila-concepto {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.35rem 0;
        }
        #comprobante-recibo .fila-concepto .nombre-concepto {
            font-size: 0.95rem;
            font-weight: 600;
            color: #111827;
        }
        #comprobante-recibo .fila-concepto .monto-concepto {
            font-size: 0.95rem;
            font-weight: 400;
            color: #111827;
            text-align: right;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        #comprobante-recibo .bloque-total {
            background: #1e40af;
            color: #ffffff;
            border-radius: 0.375rem;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 1rem;
        }
        #comprobante-recibo .bloque-total .etiqueta-total {
            font-size: 0.95rem;
            font-weight: 600;
        }
        #comprobante-recibo .bloque-total .monto-total {
            font-size: 1.75rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        #comprobante-recibo .bloque-cierre {
            text-align: center;
            font-size: 0.95rem;
            font-style: italic;
            color: #374151;
        }

        /*
         * specs/044 (US3): código para registrar el pago. Discreto y centrado
         * al pie, sin romper la composición del documento; se imprime junto con
         * el resto del comprobante. Es un `data:` URI PNG, así que la captura
         * con html2canvas (US2 de specs/031) no tiene problemas de color.
         */
        #comprobante-recibo .bloque-qr-cobro {
            margin-top: 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
        }
        #comprobante-recibo .bloque-qr-cobro img {
            width: 96px;
            height: 96px;
        }
        #comprobante-recibo .bloque-qr-cobro .leyenda-qr {
            font-size: 0.75rem;
            color: #4b5563;
            text-align: center;
        }
        #comprobante-recibo .bloque-qr-cobro .numero-recibo-grande {
            font-size: 1.5rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
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

        {{-- Bloque 1: encabezado — logo + nombre del documento (research.md Decisión 5) --}}
        <div class="bloque-encabezado">
            <img src="{{ asset('images/logo-nicson-plaza.png') }}" alt="Nicson Plaza" class="logo-comprobante">
            <h1>Recibo de Pago</h1>
        </div>

        <hr class="separador-bloque">

        {{-- Bloque 2: metadatos del recibo (research.md Decisión 5) --}}
        <div class="bloque-metadatos">
            <div class="fila-dato">
                <span class="etiqueta">N.° de recibo</span>
                <span class="valor">{{ $recibo->id }}</span>
            </div>
            <div class="fila-dato">
                <span class="etiqueta">Fecha de emisión</span>
                <span class="valor">{{ $recibo->fecha_emision->format('d/m/Y') }}</span>
            </div>
            <div class="fila-dato">
                <span class="etiqueta">Período</span>
                <span class="valor">{{ $recibo->periodo->translatedFormat('F Y') }}</span>
            </div>
            <div class="fila-dato">
                <span class="etiqueta">Estado</span>
                <span class="valor">{{ ucfirst($recibo->estado) }}</span>
            </div>
        </div>

        <hr class="separador-bloque">

        {{-- Bloque 3: datos de las partes --}}
        <div class="bloque-partes">
            <div class="fila-dato">
                <span class="etiqueta">Recibí de</span>
                <span class="valor">{{ $recibo->contrato->inquilinoPrincipal()?->nombreCompleto() ?? '—' }}</span>
            </div>
            <div class="fila-dato">
                <span class="etiqueta">Locación</span>
                <span class="valor">{{ $recibo->locacion->nombre }}</span>
            </div>
            @if (filled($nombrePropietario))
                <div class="fila-dato">
                    <span class="etiqueta">Recibido por</span>
                    <span class="valor">{{ $nombrePropietario }}</span>
                </div>
            @endif
        </div>

        <hr class="separador-bloque">

        {{-- Bloque 4: detalle de conceptos — cada ítem en su propia línea (spec.md FR-006) --}}
        <div class="bloque-conceptos">
            @if ($recibo->monto_renta !== null)
                <div class="fila-concepto">
                    <span class="nombre-concepto">Alquiler</span>
                    <span class="monto-concepto">S/ {{ number_format((float) $recibo->monto_renta, 2) }}</span>
                </div>
            @endif
            @foreach ($recibo->conceptos->sortBy('conceptoGastoFijo.orden') as $reciboConcepto)
                <div class="fila-concepto">
                    <span class="nombre-concepto">{{ $reciboConcepto->conceptoGastoFijo?->nombre ?? 'Concepto eliminado' }}</span>
                    <span class="monto-concepto">S/ {{ number_format((float) $reciboConcepto->monto, 2) }}</span>
                </div>
            @endforeach
        </div>

        <hr class="separador-bloque">

        {{-- Bloque 5: total — el único elemento que debe saltar a la vista de inmediato (research.md Decisión 3) --}}
        <div class="bloque-total">
            <span class="etiqueta-total">Total pagado</span>
            <span class="monto-total">S/ {{ number_format($recibo->total(), 2) }}</span>
        </div>

        <hr class="separador-bloque">

        {{-- Bloque 6: cierre (research.md Decisión 7) --}}
        <p class="bloque-cierre">Gracias por su pago puntual.</p>

        {{-- specs/044 (US3): código para registrar el pago de este recibo desde "Cobro por QR". --}}
        <div class="bloque-qr-cobro">
            @if (! empty($codigoQrCobro))
                <img src="{{ $codigoQrCobro }}" alt="Código para registrar el pago del recibo #{{ $recibo->id }}">
            @else
                <span class="numero-recibo-grande">Recibo #{{ $recibo->id }}</span>
            @endif
            <span class="leyenda-qr">Escanee este código en «Cobro por QR» para registrar el pago.</span>
        </div>
    </div>
</body>
</html>
