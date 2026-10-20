<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        @if (session()->has('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-xl font-semibold">Historial de entregas</h2>
                <p class="mt-1 text-sm text-zinc-500">Consulte las entregas registradas. Para generar una entrega, ingrese al seguimiento de la requisición.</p>
            </div>
            <div class="flex gap-2">
                <x-input wire:model.live="search" class="px-3 py-2" placeholder="Buscar entrega, requisición o acta" />
            </div>
        </div>
        <div class="mb-5 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <div class="w-full sm:w-48">
                    <label for="entregas-periodo" class="mb-1 block text-sm font-medium">Fecha de entrega</label>
                    <x-select id="entregas-periodo" wire:model.live="periodo" class="w-full px-3 py-2">
                        <option value="todos">Todas las fechas</option>
                        <option value="hoy">Hoy</option>
                        <option value="semana">Esta semana</option>
                        <option value="mes">Este mes</option>
                        <option value="personalizado">Rango personalizado</option>
                    </x-select>
                </div>
                <div class="w-full sm:w-auto">
                    <label for="entregas-desde" class="mb-1 block text-sm font-medium">Desde</label>
                    <x-input id="entregas-desde" type="date" wire:model.live="fechaDesde" class="w-full px-3 py-2" />
                </div>
                <div class="w-full sm:w-auto">
                    <label for="entregas-hasta" class="mb-1 block text-sm font-medium">Hasta</label>
                    <x-input id="entregas-hasta" type="date" wire:model.live="fechaHasta" min="{{ $fechaDesde }}" class="w-full px-3 py-2" />
                </div>
                @if ($fechaDesde !== '' || $fechaHasta !== '' || $periodo !== 'todos')
                    <x-secondary-button wire:click="limpiarFechas" type="button">Limpiar fechas</x-secondary-button>
                @endif
            </div>
            <p class="mt-2 text-xs text-zinc-500">El filtro se aplica automáticamente e incluye ambas fechas. Puede dejar un extremo vacío.</p>
            @if ($errorFechas)
                <p role="alert" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $errorFechas }}</p>
            @endif
        </div>
        <div class="overflow-x-auto" wire:loading.class="opacity-50">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b dark:border-zinc-700"><th class="py-2">Número</th><th>Bodega</th><th>Requisición</th><th>Departamento</th><th>Entrega</th><th>Acta</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($salidas as $salida)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $salida->numero_salida }}</td>
                            <td>{{ $salida->bodega?->nombre }}</td>
                            <td>{{ $salida->requisicion?->correlativo ?? 'Sin requisición (registro anterior)' }}</td>
                            <td>{{ $salida->requisicion?->departamento?->name ?? '—' }}</td>
                            <td>{{ $salida->actaEntrega?->tipoActaEntrega?->tipo ?? 'Registro anterior' }}</td>
                            <td>{{ $salida->actaEntrega?->correlativo ?? '—' }}</td>
                            <td>{{ $salida->fecha_salida?->format('Y-m-d') }}</td>
                            <td>{{ $salida->estado }}</td>
                            <td class="text-right space-x-2">
                                @if ($salida->requisicion_id && $salida->estado === 'confirmado')
                                    @can('inventario.salidas.ver') <a href="{{ route('inventario.salidas.acta', $salida) }}" class="text-blue-600">Ver entrega y acta</a> @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-4 text-center text-zinc-500">No hay entregas que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $salidas->links() }}</div>
    </div>

</div>
