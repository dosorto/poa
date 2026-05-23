@php
    $montoTotal = collect($recursosSeleccionados)->sum('total');
    $pasos = [
        ['numero' => 1, 'titulo' => 'Cantidad'],
        ['numero' => 2, 'titulo' => 'Resumen'],
        ['numero' => 3, 'titulo' => 'Crear requisicion'],
    ];
@endphp

<x-dialog-modal wire:model="showModalRequisicion" maxWidth="4xl">
    <x-slot name="title">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Revisar sumario</h3>
            <button type="button" wire:click="cerrarModalRequisicion"
                wire:loading.attr="disabled" wire:target="cerrarModalRequisicion"
                class="text-zinc-400 hover:text-zinc-600 disabled:opacity-50 dark:hover:text-zinc-200">
                <span class="sr-only">Cerrar</span>
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </x-slot>

    <x-slot name="content">
        <div class="space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @foreach ($pasos as $paso)
                    @php
                        $activo = $pasoActual === $paso['numero'];
                        $completo = $pasoActual > $paso['numero'];
                    @endphp
                    <div class="flex flex-1 items-center gap-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-semibold
                            {{ $activo ? 'border-indigo-600 bg-indigo-600 text-white' : ($completo ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-zinc-300 bg-white text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400') }}">
                            @if ($completo)
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                                </svg>
                            @else
                                {{ $paso['numero'] }}
                            @endif
                        </div>
                        <p class="min-w-0 text-sm font-medium {{ $activo ? 'text-indigo-700 dark:text-indigo-300' : 'text-zinc-700 dark:text-zinc-300' }}">
                            {{ $paso['titulo'] }}
                        </p>
                    </div>
                @endforeach
            </div>

            @if ($modalRequisicionError)
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    {{ $modalRequisicionError }}
                </div>
            @endif

            @if ($pasoActual === 1)
                <div class="space-y-4">
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        Ingrese la cantidad a requisar para los recursos agregados.
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">Recurso</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">Actividad</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-zinc-500">Disponible</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-zinc-500">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                @forelse ($recursosSeleccionados as $recurso)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100">{{ $recurso['nombre'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $recurso['actividad'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right text-sm text-zinc-600 dark:text-zinc-400">{{ $recurso['cantidad_disponible'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right text-sm">
                                            @if (!empty($recurso['es_combustible']))
                                                <span class="text-zinc-600 dark:text-zinc-400">{{ $recurso['cantidad_seleccionada'] ?? 0 }}</span>
                                            @else
                                                <div class="inline-flex flex-col items-end gap-1">
                                                    <x-input type="number" min="1" step="1"
                                                        max="{{ $recurso['cantidad_disponible'] ?? '' }}"
                                                        wire:model.live="cantidadesInput.{{ $recurso['id'] }}"
                                                        class="w-24 text-right" />
                                                    @if (!empty($erroresCantidad[$recurso['id']]))
                                                        <span class="text-xs text-red-500">{{ $erroresCantidad[$recurso['id']] }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-8 text-center text-sm text-zinc-500">No hay recursos seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($pasoActual === 2)
                <div class="space-y-4">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-semibold">Monto total acumulado</span>
                            <span class="text-2xl font-bold">L {{ number_format($montoTotal, 2) }}</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">Recurso</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">Actividad</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">Proceso</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium uppercase text-zinc-500">Unidad</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-zinc-500">Cantidad</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-zinc-500">Precio</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-zinc-500">Total</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium uppercase text-zinc-500">Accion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                                @forelse ($recursosSeleccionados as $recurso)
                                    <tr>
                                        <td class="px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100">
                                            <div class="flex flex-col gap-1">
                                                <span>{{ $recurso['nombre'] ?? '-' }}</span>
                                                @if (!empty($recurso['es_combustible']))
                                                    <span class="inline-flex w-fit rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                        Con orden
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $recurso['actividad'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $recurso['proceso_compra'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $recurso['unidad_medida'] ?? '-' }}</td>
                                        <td class="px-3 py-2 text-right text-sm text-zinc-600 dark:text-zinc-400">{{ $recurso['cantidad_seleccionada'] ?? 0 }}</td>
                                        <td class="px-3 py-2 text-right text-sm text-zinc-600 dark:text-zinc-400">L {{ number_format($recurso['precio_unitario'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2 text-right text-sm font-semibold text-zinc-900 dark:text-zinc-100">L {{ number_format($recurso['total'] ?? 0, 2) }}</td>
                                        <td class="px-3 py-2 text-right text-sm">
                                            @if (!empty($recurso['es_combustible']))
                                                <button type="button"
                                                    wire:click="editarOrdenCombustible({{ $recurso['id'] }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="editarOrdenCombustible({{ $recurso['id'] }})"
                                                    class="inline-flex items-center justify-center rounded-md border border-indigo-200 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:opacity-60 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/20">
                                                    <span wire:loading.remove wire:target="editarOrdenCombustible({{ $recurso['id'] }})">Editar orden</span>
                                                    <span wire:loading wire:target="editarOrdenCombustible({{ $recurso['id'] }})">Abriendo...</span>
                                                </button>
                                            @else
                                                <span class="text-xs text-zinc-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-3 py-8 text-center text-sm text-zinc-500">No hay recursos seleccionados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($pasoActual === 3)
                <div class="space-y-4">
                    <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-semibold">Monto total de la requisicion</span>
                            <span class="text-2xl font-bold">L {{ number_format($montoTotal, 2) }}</span>
                        </div>
                    </div>

                    <div>
                        <x-label value="Descripcion" class="mb-1" />
                        <x-input wire:model.defer="descripcion" type="text" class="w-full" />
                        @error('descripcion') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-label value="Fecha requerida" class="mb-1" />
                        <x-input wire:model.defer="fechaRequerida" type="date" class="w-full" />
                        @error('fechaRequerida') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-label value="Observacion" class="mb-1" />
                        <x-textarea wire:model.defer="observacion" class="w-full"></x-textarea>
                        @error('observacion') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-between">
            <x-spinner-secondary-button wire:click="cerrarModalRequisicion" type="button" loadingTarget="cerrarModalRequisicion" loadingText="Cerrando...">
                Cerrar
            </x-spinner-secondary-button>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                @if ($pasoActual > 1)
                    <x-spinner-secondary-button wire:click="anteriorPaso" type="button" loadingTarget="anteriorPaso" loadingText="Volviendo...">
                        Atras
                    </x-spinner-secondary-button>
                @endif

                @if ($pasoActual < 3)
                    <x-spinner-button wire:click="siguientePaso" type="button" loadingTarget="siguientePaso" loadingText="Continuando...">
                        Siguiente
                    </x-spinner-button>
                @elseif ($pasoActual === 3)
                    <x-spinner-button wire:click="confirmarRequisicion" type="button" loadingTarget="confirmarRequisicion" loadingText="Enviando...">
                        Enviar
                    </x-spinner-button>
                @endif
            </div>
        </div>
    </x-slot>
</x-dialog-modal>
