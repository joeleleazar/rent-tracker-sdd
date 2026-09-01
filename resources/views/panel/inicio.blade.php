<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Inicio — Estado de cobranza
        </h2>
    </x-slot>

    <div class="col-12">
        <div class="d-flex flex-column gap-4">
            @include('panel.partials.acceso-cobro-qr')
            @include('panel.partials.morosos')
            @include('panel.partials.proximos-vencimientos')
            @include('panel.partials.indicadores')
        </div>
    </div>
</x-layouts.app-bootstrap>
