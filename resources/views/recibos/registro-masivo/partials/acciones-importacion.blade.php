{{--
    specs/044 (US2): acciones de carga masiva de recibos por plantilla. No
    reemplazan la tabla ni las acciones de specs/023 — son una vía adicional.
--}}
<div class="d-flex flex-wrap align-items-end gap-2">
    <a
        href="{{ route('recibos.registroMasivo.plantilla', ['periodo' => $periodo->format('Y-m')]) }}"
        class="btn btn-outline-secondary btn-sm"
        hx-boost="false"
    >
        <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i> Descargar plantilla
    </a>

    <form
        method="POST"
        action="{{ route('recibos.registroMasivo.importar.previsualizar') }}"
        enctype="multipart/form-data"
        hx-post="{{ route('recibos.registroMasivo.importar.previsualizar') }}"
        hx-encoding="multipart/form-data"
        hx-target="#vista-previa-importacion-recibos"
        hx-swap="innerHTML"
        class="d-flex align-items-end gap-2"
    >
        @csrf
        <input type="hidden" name="periodo" value="{{ $periodo->format('Y-m-d') }}">
        <div>
            <x-input-label for="archivo_importacion_recibos" value="Importar archivo" />
            <input
                id="archivo_importacion_recibos"
                type="file"
                name="archivo"
                accept=".xlsx,.xls,.csv"
                class="form-control form-control-sm"
                required
            >
        </div>
        <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-upload" aria-hidden="true"></i> Importar
        </button>
    </form>
</div>
<p class="text-secondary small mb-0 w-100">
    Descargue la plantilla del periodo, ajuste los montos por local y vuelva a subirla. Verá una vista
    previa editable antes de guardar.
</p>
