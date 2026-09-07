<div class="mx-auto mt-6">
    <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-zinc-900">
        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        @if (session()->has('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif
        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold">Entradas de inventario</h2>
            <div class="flex gap-2">
                <input wire:model.live="search" class="rounded border px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="Buscar entrada">
                @can('inventario.entradas.crear')<a href="{{ route('inventario.entradas.create') }}" class="rounded bg-blue-600 px-4 py-2 text-white">Nueva</a>@endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="border-b text-left dark:border-zinc-700"><th class="py-2">Número</th><th>Bodega</th><th>Factura</th><th>Proveedor</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($entradas as $entrada)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $entrada->numero_entrada }}</td><td>{{ $entrada->bodega?->nombre }}</td><td>{{ $entrada->numero_factura }}</td><td>{{ $entrada->proveedor }}</td><td>{{ $entrada->fecha_entrada?->format('Y-m-d') }}</td><td>{{ $entrada->estado }}</td>
                            <td class="space-x-2 text-right">
                                @if ($entrada->estado === 'borrador')
                                    @can('inventario.entradas.crear')<a href="{{ route('inventario.entradas.edit', $entrada) }}" class="text-blue-600">Editar</a>@endcan
                                    @can('inventario.entradas.confirmar')<button wire:click="abrirConfirmacion({{ $entrada->id }})" class="cursor-pointer text-green-700">Confirmar</button>@endcan
                                @elseif ($entrada->estado === 'confirmado')
                                    @can('inventario.entradas.ver')<a href="{{ route('inventario.entradas.acta-recepcion', $entrada) }}" target="_blank" class="text-blue-600">Acta</a>@endcan
                                    @can('inventario.ajustes.crear')<button wire:click="anular({{ $entrada->id }})" class="cursor-pointer text-red-700">Anular</button>@endcan
                                @endif
                            </td>
                        </tr>
                    @empty<tr><td colspan="7" class="py-4 text-center text-zinc-500">Sin entradas.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $entradas->links() }}</div>
    </div>

    <x-dialog-modal wire:model="showConfirmarModal" max-width="md">
        <x-slot name="title">Confirmar entrada de inventario</x-slot>
        <x-slot name="content">
            <div class="flex gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" /></svg>
                </div>
                <div>
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">¿Está seguro de confirmar esta entrada?</p>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">Se aumentarán las existencias de los productos y se registrarán los movimientos en el kardex.</p>
                    <p class="mt-2 text-sm font-medium text-amber-700 dark:text-amber-400">Después de confirmar, la entrada ya no podrá editarse y solo podrá revertirse anulándola.</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="cerrarConfirmacion">Cancelar</x-secondary-button>
            <x-spinner-button wire:click="confirmar" class="ml-2" loadingTarget="confirmar" :loadingText="__('Confirmando...')">Confirmar entrada</x-spinner-button>
        </x-slot>
    </x-dialog-modal>
</div>
