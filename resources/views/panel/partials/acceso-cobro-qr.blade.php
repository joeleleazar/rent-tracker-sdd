{{--
    specs/044 (US3): acceso directo desde el inicio a la vista de cobro por QR.
    Disponible para Master y Administrador (misma pila de middleware que el
    resto del panel).
--}}
<div class="card border-primary">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary-subtle text-primary flex-shrink-0" style="width: 3rem; height: 3rem;">
            <i class="bi bi-qr-code-scan fs-4" aria-hidden="true"></i>
        </span>
        <div class="flex-grow-1">
            <h3 class="fs-5 fw-bold mb-1">Cobro por QR</h3>
            <p class="text-secondary mb-0">
                Registre el pago de un recibo escaneando su código o ingresando su número.
            </p>
        </div>
        <a href="{{ route('cobro.index') }}" class="btn btn-primary">
            <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Abrir cobro por QR
        </a>
    </div>
</div>
