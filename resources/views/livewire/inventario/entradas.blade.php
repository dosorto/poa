<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        @if (session()->has('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <h2 class="text-xl font-semibold">Entradas de inventario</h2>
            <div class="flex gap-2">
                <input wire:model.live="search" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Buscar entrada">
                @can('inventario.entradas.crear')
                    <button wire:click="create" class="cursor-pointer bg-blue-600 text-white px-4 py-2 rounded transition active:translate-y-px">Nueva</button>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b dark:border-zinc-700"><th class="py-2">Numero</th><th>Bodega</th><th>Factura</th><th>Proveedor</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($entradas as $entrada)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $entrada->numero_entrada }}</td>
                            <td>{{ $entrada->bodega?->nombre }}</td>
                            <td>{{ $entrada->numero_factura }}</td>
                            <td>{{ $entrada->proveedor }}</td>
                            <td>{{ $entrada->fecha_entrada?->format('Y-m-d') }}</td>
                            <td>{{ $entrada->estado }}</td>
                            <td class="text-right space-x-2">
                                @if ($entrada->estado === 'borrador')
                                    @can('inventario.entradas.crear') <button wire:click="edit({{ $entrada->id }})" class="cursor-pointer text-blue-600 transition active:translate-y-px">Editar</button> @endcan
                                    @can('inventario.entradas.confirmar') <button wire:click="confirmar({{ $entrada->id }})" class="cursor-pointer text-green-700 transition active:translate-y-px">Confirmar</button> @endcan
                                @elseif ($entrada->estado === 'confirmado')
                                    @can('inventario.ajustes.crear') <button wire:click="anular({{ $entrada->id }})" class="cursor-pointer text-red-700 transition active:translate-y-px">Anular</button> @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-zinc-500">Sin entradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $entradas->links() }}</div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div class="w-full max-w-6xl overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Entrada en borrador</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Registra la compra o ingreso fisico antes de confirmar el movimiento en bodega.</p>
                </div>

                <div class="max-h-[72vh] overflow-y-auto px-6 py-5">
                    <div class="space-y-6">
                        <section>
                            <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">Datos de entrada</h4>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Numero de entrada</span>
                                    <input wire:model="numero_entrada" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Bodega destino</span>
                                    <select wire:model="bodega_id" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        <option value="">Seleccionar bodega</option>
                                        @foreach ($bodegas as $bodega) <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option> @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Fecha de entrada</span>
                                    <input wire:model="fecha_entrada" type="date" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                            </div>
                        </section>

                        <section>
                            <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">Documento de compra</h4>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Factura</span>
                                    <input wire:model="numero_factura" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Proveedor</span>
                                    <input wire:model="proveedor" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Fecha de factura</span>
                                    <input wire:model="fecha_factura" type="date" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Orden de compra</span>
                                    <input wire:model="orden_compra_referencia" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Requisicion opcional</span>
                                    <select wire:model="requisicion_id" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        <option value="">Sin requisicion asociada</option>
                                        @foreach ($requisiciones as $requisicion) <option value="{{ $requisicion->id }}">{{ $requisicion->correlativo }}</option> @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Observacion</span>
                                    <input wire:model="observacion" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                            </div>
                        </section>

                        <section class="border-t border-zinc-200 pt-5 dark:border-zinc-700">
                            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">Detalle de productos</h4>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Cada fila representa el producto, lote y cantidad que entrara a la bodega.</p>
                                </div>
                                <button wire:click="addDetalle" class="inline-flex h-9 cursor-pointer items-center justify-center rounded-md border border-zinc-300 px-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 active:translate-y-px dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Agregar producto</button>
                            </div>

                            <div class="space-y-3">
                                @foreach ($detalles as $index => $detalle)
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">Producto {{ $index + 1 }}</span>
                                            <button wire:click="removeDetalle({{ $index }})" class="cursor-pointer text-sm font-semibold text-red-600 transition hover:text-red-700 active:translate-y-px dark:text-red-400">Quitar</button>
                                        </div>

                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                                            <label class="block md:col-span-4">
                                                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Producto</span>
                                                <select wire:model="detalles.{{ $index }}.producto_id" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Seleccionar producto</option>
                                                    @foreach ($productos as $producto) <option value="{{ $producto->id }}">{{ $producto->codigo_interno }} - {{ $producto->nombre }}</option> @endforeach
                                                </select>
                                            </label>
                                            <label class="block md:col-span-2">
                                                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Lote</span>
                                                <input wire:model="detalles.{{ $index }}.codigo_lote" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                            </label>
                                            <label class="block md:col-span-2">
                                                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Cantidad</span>
                                                <input wire:model="detalles.{{ $index }}.cantidad" type="number" step="0.01" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                            </label>
                                            <label class="block md:col-span-2">
                                                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Costo</span>
                                                <input wire:model="detalles.{{ $index }}.costo_unitario" type="number" step="0.01" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                            </label>
                                            <label class="block md:col-span-2">
                                                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Vencimiento</span>
                                                <input wire:model="detalles.{{ $index }}.fecha_vencimiento" type="date" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </div>
                </div>

                @if ($errors->any()) <div class="mx-6 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">{{ $errors->first() }}</div> @endif
                <div class="flex justify-end gap-2 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <button wire:click="closeModal" class="cursor-pointer rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 active:translate-y-px dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancelar</button>
                    <button wire:click="save" class="cursor-pointer rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 active:translate-y-px">Guardar borrador</button>
                </div>
            </div>
        </div>
    @endif
</div>
