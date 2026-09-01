{{--
    specs/044 (US1): acciones de carga masiva por plantilla, dentro de la fila
    de controles de la pantalla de Registro Masivo de Lecturas. No reemplazan
    la grilla manual ni la exportación de specs/015 — son una vía adicional.
    "Descargar plantilla" es una descarga binaria (hx-boost="false", igual que
    Exportar). "Importar" sube el archivo por htmx y pinta la vista previa
    editable en #vista-previa-importacion-lecturas.
--}}
<div class="d-flex flex-wrap align-items-end gap-2">
    <a
        href="{{ route('lecturas.registroMasivo.plantilla', ['periodo' => $periodo->format('Y-m')]) }}"
        class="btn btn-outline-secondary btn-sm"
        hx-boost="false"
    >
        <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> Descargar plantilla
    </a>

    <form
        method="POST"
        action="{{ route('lecturas.registroMasivo.importar.previsualizar') }}"
        enctype="multipart/form-data"
        hx-post="{{ route('lecturas.registroMasivo.importar.previsualizar') }}"
        hx-encoding="multipart/form-data"
        hx-target="#vista-previa-importacion-lecturas"
        hx-swap="innerHTML"
        class="d-flex align-items-end gap-2"
    >
        @csrf
        <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">
        <div>
            <x-input-label for="archivo_importacion_lecturas" value="Importar archivo" />
            <input
                id="archivo_importacion_lecturas"
                type="file"
                name="archivo"
                accept=".xlsx,.xls,.csv"
                class="form-control form-control-sm"
                required
                aria-describedby="ayuda_importacion_lecturas"
            >
        </div>
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-upload" aria-hidden="true"></i> Importar
        </button>
    </form>
</div>
<p id="ayuda_importacion_lecturas" class="text-secondary small mb-0 w-100">
    Descargue la plantilla del periodo, complete la columna «Lectura Actual» y vuelva a subirla. Verá una
    vista previa editable antes de guardar.
</p>
