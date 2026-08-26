{{--
    specs/023: parcial de error del modal — devuelta con estado 422 tanto por
    validación (SolicitudGuardarReciboRegistroMasivo::failedValidation()) como por
    ConceptosReciboYaCubiertosException/SinContratoActivoEnPeriodoException
    (RegistroMasivoRecibosController::store()). registro-masivo-recibos.js la
    inyecta dentro de #errores-modal-recibo sin cerrar el modal (htmx:responseError).
--}}
<x-mensaje-alerta tipo="error">{{ $mensaje }}</x-mensaje-alerta>
