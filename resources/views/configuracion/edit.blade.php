<x-layouts.app-bootstrap>
    <x-slot name="header">
        <h2 class="fs-2 fw-bold mb-0">
            Configuración General
        </h2>
    </x-slot>

    <div class="col-12 col-lg-8" style="max-width: 42rem;">
        <div class="d-flex flex-column gap-3">
            @if (session('mensaje'))
                <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
            @endif

            @if ($errors->any())
                <x-mensaje-alerta tipo="error">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-mensaje-alerta>
            @endif

            <form method="POST" action="{{ route('configuracion.update') }}" class="card">
                <div class="card-body d-flex flex-column gap-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="nombre_propietario" value="Nombre del Propietario/Administrador" />
                        <x-text-input
                            id="nombre_propietario"
                            name="nombre_propietario"
                            type="text"
                            :value="old('nombre_propietario', $configuracion->nombre_propietario)"
                        />
                        <x-input-error :messages="$errors->get('nombre_propietario')" class="mt-2" />
                        <p class="mt-2 mb-0">
                            Aparece en el comprobante de recibo como "Recibido por". Si se deja vacío, esa línea no se muestra.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="correo_notificaciones_vencimiento" value="Correo para Notificaciones de Vencimiento" />
                        <x-text-input
                            id="correo_notificaciones_vencimiento"
                            name="correo_notificaciones_vencimiento"
                            type="email"
                            :value="old('correo_notificaciones_vencimiento', $configuracion->correo_notificaciones_vencimiento)"
                            required
                        />
                        <x-input-error :messages="$errors->get('correo_notificaciones_vencimiento')" class="mt-2" />
                        <p class="mt-2 mb-0">
                            A esta dirección se enviarán todas las notificaciones automáticas de vencimiento de contratos.
                        </p>
                    </div>

                    <div>
                        <x-input-label for="tarifa_luz_por_unidad" value="Tarifa de Luz por Unidad de Consumo (S/)" />
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <x-text-input
                                id="tarifa_luz_por_unidad"
                                name="tarifa_luz_por_unidad"
                                type="number"
                                step="0.0001"
                                min="0"
                                :value="old('tarifa_luz_por_unidad', $configuracion->tarifa_luz_por_unidad)"
                                required
                            />
                        </div>
                        <x-input-error :messages="$errors->get('tarifa_luz_por_unidad')" class="mt-2" />
                        <p class="mt-2 mb-0">
                            Se usa para calcular el monto sugerido de luz de cada recibo (consumo × tarifa).
                        </p>
                    </div>

                    <div>
                        <x-input-label for="dias_anticipacion_alerta_pago" value="Días de Anticipación para Alerta de Pago" />
                        <x-text-input
                            id="dias_anticipacion_alerta_pago"
                            name="dias_anticipacion_alerta_pago"
                            type="number"
                            step="1"
                            min="1"
                            :value="old('dias_anticipacion_alerta_pago', $configuracion->dias_anticipacion_alerta_pago)"
                            required
                        />
                        <x-input-error :messages="$errors->get('dias_anticipacion_alerta_pago')" class="mt-2" />
                        <p class="mt-2 mb-0">
                            El sistema avisará con esta cantidad de días de anticipación antes del último sábado de cada mes (fecha límite de pago).
                        </p>
                    </div>

                    <x-primary-button class="align-self-start">Guardar Configuración</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app-bootstrap>
