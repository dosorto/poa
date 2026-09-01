<div class="mx-auto mt-6 space-y-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">Inventario / Bodega</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
            <div class="p-4 border rounded bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700">
                <div class="text-sm text-zinc-500">Productos</div>
                <div class="text-2xl font-semibold">{{ $productos }}</div>
            </div>
            <div class="p-4 border rounded bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700">
                <div class="text-sm text-zinc-500">Stock bajo</div>
                <div class="text-2xl font-semibold">{{ $existenciasBajas }}</div>
            </div>
            <div class="p-4 border rounded bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700">
                <div class="text-sm text-zinc-500">Proximos a vencer</div>
                <div class="text-2xl font-semibold">{{ $proximosAVencer }}</div>
            </div>
            <div class="p-4 border rounded bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-700">
                <div class="text-sm text-zinc-500">Vencidos</div>
                <div class="text-2xl font-semibold">{{ $vencidos }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
            <h3 class="font-semibold mb-4">Ultimas entradas</h3>
            <div class="space-y-3">
                @forelse ($ultimasEntradas as $entrada)
                    <div class="flex justify-between border-b pb-2 dark:border-zinc-700">
                        <div>
                            <div class="font-medium">{{ $entrada->numero_entrada }}</div>
                            <div class="text-sm text-zinc-500">{{ $entrada->bodega?->nombre }} / {{ $entrada->estado }}</div>
                        </div>
                        <div class="text-sm text-zinc-500">{{ $entrada->fecha_entrada?->format('Y-m-d') }}</div>
                    </div>
                @empty
                    <div class="text-sm text-zinc-500">Sin entradas registradas.</div>
                @endforelse
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
            <h3 class="font-semibold mb-4">Ultimos movimientos</h3>
            <div class="space-y-3">
                @forelse ($ultimosMovimientos as $movimiento)
                    <div class="flex justify-between border-b pb-2 dark:border-zinc-700">
                        <div>
                            <div class="font-medium">{{ $movimiento->producto?->nombre }}</div>
                            <div class="text-sm text-zinc-500">{{ $movimiento->tipo_movimiento }} / {{ $movimiento->referencia }}</div>
                        </div>
                        <div class="text-sm text-zinc-500">Saldo {{ $movimiento->saldo_nuevo }}</div>
                    </div>
                @empty
                    <div class="text-sm text-zinc-500">Sin movimientos en kardex.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
