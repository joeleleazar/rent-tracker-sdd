<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-900">
            Recibo #{{ $recibo->id }} — {{ $recibo->locacion->nombre }}
        </h2>
    </x-slot>

    <div class="max-w-2xl space-y-6">
        @if (session('mensaje'))
            <x-mensaje-alerta tipo="exito">{{ session('mensaje') }}</x-mensaje-alerta>
        @endif

        @if ($errors->any())
            <x-mensaje-alerta tipo="error">
                <ul class="list-disc space-y-1 pl-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-mensaje-alerta>
        @endif

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <dl class="space-y-4">
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Estado</dt>
                    <dd class="text-lg text-gray-900">
                        @php
                            $estiloEstado = match ($recibo->estado) {
                                'pagado' => 'border-green-800 bg-green-50 text-green-900',
                                'anulado' => 'border-red-800 bg-red-50 text-red-900',
                                default => 'border-gray-700 bg-gray-100 text-gray-900',
                            };
                        @endphp
                        <span class="rounded-md border-2 px-3 py-1 font-bold {{ $estiloEstado }}">
                            {{ ucfirst($recibo->estado) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Locación</dt>
                    <dd class="text-lg text-gray-900">{{ $recibo->locacion->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Periodo</dt>
                    <dd class="text-lg text-gray-900">{{ $recibo->periodo->translatedFormat('F Y') }}</dd>
                </div>
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Fecha de emisión</dt>
                    <dd class="text-lg text-gray-900">{{ $recibo->fecha_emision->format('d/m/Y') }}</dd>
                </div>
                @if ($recibo->incluye_alquiler)
                    <div>
                        <dt class="text-lg font-semibold text-gray-700">Monto de Renta</dt>
                        <dd class="text-lg text-gray-900">S/ {{ number_format((float) $recibo->monto_renta, 2) }}</dd>
                    </div>
                @endif
                @if ($recibo->incluye_agua)
                    <div>
                        <dt class="text-lg font-semibold text-gray-700">Monto de Agua</dt>
                        <dd class="text-lg text-gray-900">S/ {{ number_format((float) $recibo->monto_agua, 2) }}</dd>
                    </div>
                @endif
                @if ($recibo->incluye_luz)
                    <div>
                        <dt class="text-lg font-semibold text-gray-700">Monto de Luz</dt>
                        <dd class="text-lg text-gray-900">S/ {{ number_format((float) $recibo->monto_luz, 2) }}</dd>
                    </div>
                @endif
                @if ($recibo->incluye_pasadizo)
                    <div>
                        <dt class="text-lg font-semibold text-gray-700">Monto de Luz de Pasadizo</dt>
                        <dd class="text-lg text-gray-900">S/ {{ number_format((float) $recibo->monto_pasadizo, 2) }}</dd>
                    </div>
                @endif
                @if ($recibo->incluye_seguridad)
                    <div>
                        <dt class="text-lg font-semibold text-gray-700">Monto de Seguridad</dt>
                        <dd class="text-lg text-gray-900">S/ {{ number_format((float) $recibo->monto_seguridad, 2) }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-lg font-semibold text-gray-700">Total</dt>
                    <dd class="text-lg font-bold text-gray-900">S/ {{ number_format($recibo->total(), 2) }}</dd>
                </div>
            </dl>

            <div class="flex flex-wrap gap-4 pt-2">
                <a href="{{ route('recibos.edit', $recibo) }}" class="btn-senior-primario">Editar Recibo</a>
                <a href="{{ route('recibos.comprobante', $recibo) }}" class="btn-senior-primario">Ver Comprobante</a>
                <a href="{{ route('locaciones.recibos.index', $recibo->locacion) }}" class="btn-senior-secundario">Ver Historial de Recibos</a>
            </div>
        </div>

        <div class="space-y-4 rounded-md border-2 border-gray-300 bg-white p-6">
            <h3 class="text-xl font-bold text-gray-900">Estado de Pago</h3>

            <div class="flex flex-wrap gap-4">
                @if ($recibo->estado === 'pendiente')
                    <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="nuevo_estado" value="pagado">
                        <input type="hidden" name="confirmado" value="1">
                        <x-primary-button>Marcar como Pagado</x-primary-button>
                    </form>

                    <x-danger-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'anular-recibo')"
                    >Anular Recibo</x-danger-button>
                @elseif ($recibo->estado === 'pagado')
                    <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="nuevo_estado" value="pendiente">
                        <input type="hidden" name="confirmado" value="1">
                        <x-secondary-button>Marcar como Pendiente</x-secondary-button>
                    </form>

                    <x-danger-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'anular-recibo')"
                    >Anular Recibo</x-danger-button>
                @else
                    <x-primary-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'revertir-pendiente')"
                    >Revertir Anulación a Pendiente</x-primary-button>

                    <x-primary-button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'revertir-pagado')"
                    >Revertir Anulación a Pagado</x-primary-button>
                @endif
            </div>
        </div>

        <x-modal name="anular-recibo" focusable>
            <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="nuevo_estado" value="anulado">
                <input type="hidden" name="confirmado" value="1">

                <h2 class="text-xl font-bold text-gray-900">¿Anular este recibo?</h2>
                <p class="mt-2 text-lg text-gray-700">
                    Un recibo anulado se marcará visiblemente como "ANULADO" en su comprobante.
                </p>

                <div class="mt-6 flex justify-end gap-4">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">No, cancelar</x-secondary-button>
                    <x-danger-button>Sí, anular recibo</x-danger-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="revertir-pendiente" focusable>
            <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="nuevo_estado" value="pendiente">
                <input type="hidden" name="confirmado" value="1">

                <h2 class="text-xl font-bold text-gray-900">¿Revertir la anulación de este recibo a Pendiente?</h2>

                <div class="mt-6 flex justify-end gap-4">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">No, cancelar</x-secondary-button>
                    <x-primary-button>Sí, revertir a pendiente</x-primary-button>
                </div>
            </form>
        </x-modal>

        <x-modal name="revertir-pagado" focusable>
            <form method="POST" action="{{ route('recibos.estado.update', $recibo) }}" class="p-6">
                @csrf
                @method('patch')
                <input type="hidden" name="nuevo_estado" value="pagado">
                <input type="hidden" name="confirmado" value="1">

                <h2 class="text-xl font-bold text-gray-900">¿Revertir la anulación de este recibo a Pagado?</h2>

                <div class="mt-6 flex justify-end gap-4">
                    <x-secondary-button type="button" x-on:click="$dispatch('close')">No, cancelar</x-secondary-button>
                    <x-primary-button>Sí, revertir a pagado</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
