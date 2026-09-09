<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        @if (session()->has('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <h2 class="text-xl font-semibold">Salidas de inventario</h2>
            <div class="flex gap-2">
                <input wire:model.live="search" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Buscar salida">
                @can('inventario.salidas.crear')
                    <a href="{{ route('inventario.salidas.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded transition active:translate-y-px">Nueva</a>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b dark:border-zinc-700"><th class="py-2">Número</th><th>Bodega</th><th>Tipo</th><th>Acta</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($salidas as $salida)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $salida->numero_salida }}</td>
                            <td>{{ $salida->bodega?->nombre }}</td>
                            <td>{{ $salida->tipo_salida }}</td>
                            <td>{{ $salida->actaEntrega?->correlativo ?? 'Manual' }}</td>
                            <td>{{ $salida->fecha_salida?->format('Y-m-d') }}</td>
                            <td>{{ $salida->estado }}</td>
                            <td class="text-right space-x-2">
                                @if ($salida->estado === 'borrador')
                                    @can('inventario.salidas.crear') <a href="{{ route('inventario.salidas.edit', $salida) }}" class="text-blue-600">Editar</a> @endcan
                                    @can('inventario.salidas.confirmar')
                                        <button
                                            wire:click="abrirConfirmacion({{ $salida->id }})"
                                            class="cursor-pointer text-green-700 transition active:translate-y-px"
                                        >Confirmar</button>
                                    @endcan
                                @elseif ($salida->estado === 'confirmado')
                                    @can('inventario.salidas.ver') <a href="{{ route('inventario.salidas.acta', $salida) }}" class="text-blue-600">Ver acta</a> @endcan
                                    @can('inventario.ajustes.crear') <button wire:click="anular({{ $salida->id }})" class="cursor-pointer text-red-700 transition active:translate-y-px">Anular</button> @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-zinc-500">Sin salidas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $salidas->links() }}</div>
    </div>

    <x-dialog-modal wire:model="showConfirmarModal" max-width="md">
        <x-slot name="title">Confirmar salida de inventario</x-slot>

        <x-slot name="content">
            <div class="flex gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">¿Está seguro de confirmar esta salida?</p>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Se descontarán las existencias seleccionadas y se registrará el movimiento en el kardex.</p>
                    <p class="mt-2 text-sm font-medium text-amber-700 dark:text-amber-400">Esta acción solo podrá revertirse anulando la salida.</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cerrarConfirmacion">Cancelar</x-secondary-button>
            <x-spinner-button wire:click="confirmar" class="ml-2" loadingTarget="confirmar" :loadingText="__('Confirmando...')">
                Confirmar salida
            </x-spinner-button>
        </x-slot>
    </x-dialog-modal>
</div>
