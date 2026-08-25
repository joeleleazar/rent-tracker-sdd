{{--
    Plantilla dedicada para dompdf (FR-016, specs/015) — no es la vista
    interactiva, es una tabla estática con el mismo contenido reunido por
    RegistroMasivoLecturasController::filasExportables().

    Props esperadas:
    - $periodo (Illuminate\Support\Carbon)
    - $filas (array del mismo tipo que ExportacionRegistroMasivoLecturas)
    - $tarifa (float)
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Registro de Lecturas — {{ ucfirst($periodo->translatedFormat('F Y')) }}</title>
    <style>
        {{-- Paleta de DESIGN.md (neutral-ink/neutral-secondary/neutral-border/neutral-surface):
             dompdf no procesa las variables CSS de bootstrap.scss, así que se repiten los
             mismos hex ya documentados en vez de introducir un color nuevo para este export. --}}
        body { font-family: sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.tarifa { color: #374151; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 4px 8px; text-align: left; }
        th { background-color: #f9fafb; }
        td.numero, th.numero { text-align: right; }
        tfoot td { font-weight: bold; border-top: 2px solid #d1d5db; }
    </style>
</head>
<body>
    <h1>Registro de Lecturas de Luz — {{ ucfirst($periodo->translatedFormat('F Y')) }}</h1>
    <p class="tarifa">Tarifa por kWh: S/ {{ number_format((float) $tarifa, 4) }}</p>

    <table>
        <thead>
            <tr>
                <th>Local</th>
                <th class="numero">Lectura Periodo Anterior</th>
                <th class="numero">Lectura Actual</th>
                <th class="numero">Consumo (kWh)</th>
                <th class="numero">Total (S/)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($filas as $fila)
                <tr>
                    <td>{{ $fila['ubicacion'] }}</td>
                    <td class="numero">{{ $fila['lectura_anterior'] ?? 'Sin lectura previa' }}</td>
                    <td class="numero">{{ $fila['lectura_actual'] ?? '—' }}</td>
                    <td class="numero">{{ $fila['consumo'] !== null ? number_format((float) $fila['consumo'], 2) : '—' }}</td>
                    <td class="numero">{{ $fila['total'] !== null ? number_format($fila['total'], 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Total general</td>
                <td class="numero">S/ {{ number_format(collect($filas)->sum('total'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
