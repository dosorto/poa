@php
    $recursosCombustible = collect($recursosSeleccionados)->filter(fn ($recurso) => !empty($recurso['es_combustible']));
    $tieneCombustibles = $recursosCombustible->isNotEmpty();
    $ordenesCompletas = $recursosCombustible->every(fn ($recurso) => !empty($ordenesCombustible[$recurso['id']]['confirmada']));
    $montoTotal = collect($recursosSeleccionados)->sum('total');
    $pasos = $tieneCombustibles
        ? [
            ['numero' => 1, 'interno' => 1, 'titulo' => 'Ordenes de combustible'],
            ['numero' => 2, 'interno' => 2, 'titulo' => 'Resumen'],
            ['numero' => 3, 'interno' => 3, 'titulo' => 'Crear requisicion'],
        ]
        : [
            ['numero' => 1, 'interno' => 2, 'titulo' => 'Resumen'],
            ['numero' => 2, 'interno' => 3, 'titulo' => 'Crear requisicion'],
        ];
@endphp

<x-dialog-modal wire:model="showModalRequisicion" maxWidth="4xl">
    <x-slot name="title">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Revisar sumario</h3>
            <button type="button" wire:click="cerrarModalRequisicion"
                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
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
                        $activo = $pasoActual === $paso['interno'];
                        $completo = $pasoActual > $paso['interno'];
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
                        <div class="min-w-0">
                            <p class="text-sm font-medium {{ $activo ? 'text-indigo-700 dark:text-indigo-300' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ $paso['titulo'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($modalRequisicionError)
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    {{ $modalRequisicionError }}
                </div>
            @endif

            @if ($pasoActual === 1 && $tieneCombustibles)
                <div class="space-y-4">
                    @foreach ($recursosCombustible as $recurso)
                        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h4 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $recurso['nombre'] }}</h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $recurso['actividad'] ?? '-' }}</p>
                                </div>
                                @if (!empty($ordenesCombustible[$recurso['id']]['confirmada']))
                                    <span class="inline-flex w-fit items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                        Orden confirmada
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <x-label value="Modelo de vehiculo" class="mb-1" />
                                    <x-input wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.modelo_vehiculo" type="text" class="w-full" />
                                    @error("ordenesCombustible.{$recurso['id']}.modelo_vehiculo") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <x-label value="Placa" class="mb-1" />
                                    <x-input wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.placa" type="text" class="w-full" />
                                    @error("ordenesCombustible.{$recurso['id']}.placa") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <x-label value="Responsable" class="mb-1" />
                                    <select wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.idEmpleado"
                                        class="w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                                        <option value="">Seleccione un empleado</option>
                                        @foreach ($empleados as $empleado)
                                            <option value="{{ $empleado->id }}">
                                                {{ trim($empleado->nombre . ' ' . $empleado->apellido) }}{{ $empleado->num_empleado ? ' - #' . $empleado->num_empleado : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("ordenesCombustible.{$recurso['id']}.idEmpleado") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <x-label value="Lugar de salida" class="mb-1" />
                                    <x-input wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.lugar_salida" type="text" class="w-full" />
                                    @error("ordenesCombustible.{$recurso['id']}.lugar_salida") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <x-label value="Lugar de destino" class="mb-1" />
                                    <x-input wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.lugar_destino" type="text" class="w-full" />
                                    @error("ordenesCombustible.{$recurso['id']}.lugar_destino") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <x-label value="Recorrido km" class="mb-1" />
                                    <x-input wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.recorrido_km" type="number" step="0.01" min="0" class="w-full" />
                                    @error("ordenesCombustible.{$recurso['id']}.recorrido_km") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <x-label value="Fecha a realizar" class="mb-1" />
                                    <x-input wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.fecha_realizar" type="date" class="w-full" />
                                    @error("ordenesCombustible.{$recurso['id']}.fecha_realizar") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <x-label value="Actividades" class="mb-1" />
                                    <x-textarea wire:model.defer="ordenesCombustible.{{ $recurso['id'] }}.actividades" class="w-full"></x-textarea>
                                    @error("ordenesCombustible.{$recurso['id']}.actividades") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <x-spinner-button wire:click="confirmarOrdenCombustible({{ $recurso['id'] }})" type="button"
                                    loadingTarget="confirmarOrdenCombustible({{ $recurso['id'] }})" loadingText="Confirmando...">
                                    Confirmar orden
                                </x-spinner-button>
                            </div>
                        </div>
                    @endforeach
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
                                                        Con orden de combustible
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
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-8 text-center text-sm text-zinc-500">No hay recursos seleccionados.</td>
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
                @if (($pasoActual === 2 && $tieneCombustibles) || $pasoActual === 3)
                    <x-spinner-secondary-button wire:click="anteriorPaso" type="button" loadingTarget="anteriorPaso" loadingText="Volviendo...">
                        Atras
                    </x-spinner-secondary-button>
                @endif

                @if ($pasoActual === 1)
                    <button type="button" wire:click="siguientePaso" @disabled(!$ordenesCompletas)
                        class="inline-flex items-center justify-center rounded-md border border-transparent px-4 py-2 text-sm font-semibold text-white shadow-sm transition {{ $ordenesCompletas ? 'bg-indigo-600 hover:bg-indigo-700' : 'cursor-not-allowed bg-zinc-300 dark:bg-zinc-700' }}">
                        <span wire:loading.remove wire:target="siguientePaso">Siguiente</span>
                        <span wire:loading wire:target="siguientePaso">Validando...</span>
                    </button>
                @elseif ($pasoActual === 2)
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
