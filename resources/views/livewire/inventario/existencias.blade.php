<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Existencias</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <input wire:model.live="search" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Producto o codigo">
            <select wire:model.live="bodega_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                <option value="">Todas las bodegas</option>
                @foreach ($bodegas as $bodega) <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option> @endforeach
            </select>
            <select wire:model.live="producto_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                <option value="">Todos los productos</option>
                @foreach ($productos as $producto) <option value="{{ $producto->id }}">{{ $producto->nombre }}</option> @endforeach
            </select>
            <select wire:model.live="estado" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                <option value="">Todos los estados</option>
                <option value="disponible">Disponible</option>
                <option value="agotado">Agotado</option>
                <option value="vencido">Vencido</option>
                <option value="bloqueado">Bloqueado</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b dark:border-zinc-700"><th class="py-2">Bodega</th><th>Producto</th><th>Lote</th><th>Disponible</th><th>Reservada</th><th>Vence</th><th>Ubicacion</th></tr></thead>
                <tbody>
                    @forelse ($existencias as $existencia)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $existencia->bodega?->nombre }}</td>
                            <td>{{ $existencia->producto?->codigo_interno }} - {{ $existencia->producto?->nombre }}</td>
                            <td>{{ $existencia->lote?->codigo_lote ?? 'SIN-LOTE' }}</td>
                            <td>{{ $existencia->cantidad_disponible }} {{ $existencia->producto?->unidadMedida?->nombre }}</td>
                            <td>{{ $existencia->cantidad_reservada }}</td>
                            <td>{{ $existencia->lote?->fecha_vencimiento?->format('Y-m-d') ?? 'N/A' }}</td>
                            <td>{{ $existencia->lote?->ubicacion }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-zinc-500">Sin existencias.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $existencias->links() }}</div>
    </div>
</div>
