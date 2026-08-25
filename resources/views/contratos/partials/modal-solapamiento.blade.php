{{--
    Modal de advertencia de solapamiento de contratos (specs/012, FR-002),
    con el contrato existente y el que se intentó registrar en dos bloques
    de alerta separados. `contratoEnConflicto` llega vía sesión como array
    plano (ContratoController::datosContratoEnConflicto()) únicamente cuando
    se atrapó ContratoSolapadoException en este mismo request (ver
    research.md §3) — nunca se construye lógica de detección de solapamiento
    aquí, solo se presenta lo que el servidor ya calculó.
--}}
@if (session('contratoEnConflicto'))
    @php
        $existente = session('contratoEnConflicto');
        $indicePrincipalNuevo = old('principal_index', 0);
        $apellidosNuevo = old("inquilinos.{$indicePrincipalNuevo}.apellidos");
        $nombresNuevo = old("inquilinos.{$indicePrincipalNuevo}.nombres");
    @endphp

    <x-modal-bootstrap name="modal-solapamiento" :show="true" maxWidth="lg" focusable>
        <div class="modal-header border-danger">
            <h2 class="modal-title fs-3 fw-bold text-danger">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> Conflicto de Fechas Detectado
            </h2>
        </div>

        <div class="modal-body p-4">
            <p class="fw-bold text-danger mb-2">La locación ya tiene un contrato activo en este período:</p>
            <div class="alert alert-warning" role="alert">
                <strong>Contrato Existente</strong><br>
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                Desde: {{ $existente['fecha_inicio'] }} | Hasta: {{ $existente['fecha_fin'] }}<br>
                <i class="bi bi-person" aria-hidden="true"></i> Inquilino: {{ $existente['inquilino_nombre'] }}<br>
                <i class="bi bi-cash-coin" aria-hidden="true"></i> Monto: S/ {{ $existente['monto_renta'] }}
            </div>

            <p class="fw-bold text-danger mb-2">Su nuevo contrato se superpone:</p>
            <div class="alert alert-warning" role="alert">
                <strong>Nuevo Contrato</strong><br>
                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                Desde: {{ old('fecha_inicio') }} | Hasta: {{ old('fecha_fin') }}<br>
                <i class="bi bi-person" aria-hidden="true"></i> Inquilino: {{ $apellidosNuevo ? "{$apellidosNuevo}, {$nombresNuevo}" : '—' }}<br>
                <i class="bi bi-cash-coin" aria-hidden="true"></i> Monto: S/ {{ number_format((float) old('monto_renta'), 2) }}
            </div>

            <p class="mb-0">
                Debe <strong>rescindir o modificar el contrato existente</strong> antes de registrar este nuevo contrato.
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
            <a href="{{ route('contratos.edit', $existente['contrato_id']) }}" class="btn btn-warning">Editar Contrato Existente</a>
        </div>
    </x-modal-bootstrap>
@endif
