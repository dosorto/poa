<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <h2 class="text-xl font-semibold">Productos de inventario</h2>
            <div class="flex gap-2">
                <input wire:model.live="search" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Codigo, barra o nombre">
                @can('inventario.productos.crear')
                    <button wire:click="create" class="cursor-pointer bg-blue-600 text-white px-4 py-2 rounded transition active:translate-y-px">Nuevo</button>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b dark:border-zinc-700"><th class="py-2">Codigo</th><th>Nombre</th><th>Unidad</th><th>Recurso</th><th>CUBS</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $producto->codigo_interno }}</td>
                            <td><div class="font-medium">{{ $producto->nombre }}</div><div class="text-xs text-zinc-500">{{ $producto->codigo_barra }}</div></td>
                            <td>{{ $producto->unidadMedida?->nombre }}</td>
                            <td>{{ str($producto->recurso?->nombre)->limit(35) }}</td>
                            <td>{{ $producto->idCubs }}</td>
                            <td>{{ $producto->activo ? 'Activo' : 'Inactivo' }}</td>
                            <td class="text-right">
                                @can('inventario.productos.editar')
                                    <button wire:click="edit({{ $producto->id }})" class="cursor-pointer text-blue-600 transition active:translate-y-px">Editar</button>
                                    <button wire:click="toggleActivo({{ $producto->id }})" class="ml-3 cursor-pointer text-zinc-600 transition active:translate-y-px">Cambiar estado</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-zinc-500">Sin productos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $productos->links() }}</div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/50 p-4">
            <div class="w-full max-w-4xl overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Producto de inventario</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Define el producto fisico y su relacion con recursos, objeto de gasto y CUBS.</p>
                </div>

                <div class="max-h-[72vh] overflow-y-auto px-6 py-5">
                    <div class="space-y-6">
                        <section>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <x-searchable-select
                                        wire:model="recurso_id"
                                        wire:key="inventario-recurso-select-{{ $productoId ?: 'nuevo' }}-{{ $recurso_id ?: 'empty' }}"
                                        label="Recurso POA opcional"
                                        placeholder="Buscar recurso..."
                                        defaultText="Seleccione un recurso"
                                        clearText="Sin recurso asociado"
                                        searchAction="searchRecursosInventario"
                                        :options="$recursos"
                                        :error="$errors->first('recurso_id')"
                                    />
                                </div>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Codigo de barra</span>
                                    <input wire:model="codigo_barra" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                            </div>
                        </section>

                        <section>
                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Detalle tecnico</span>
                                <textarea wire:model="descripcion" rows="4" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                            </label>
                        </section>

                        <section>
                            <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">Clasificacion y control</h4>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Unidad de medida</span>
                                    <select wire:model="unidad_medida_id" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        <option value="">Seleccionar unidad</option>
                                        @foreach ($unidades as $unidad) <option value="{{ $unidad->id }}">{{ $unidad->nombre }}</option> @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Stock minimo</span>
                                    <input wire:model="stock_minimo" type="number" step="0.01" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>
                                <div>
                                    <x-searchable-select
                                        wire:model="idobjeto"
                                        wire:key="inventario-objeto-select-{{ $productoId ?: 'nuevo' }}-{{ $idobjeto ?: 'empty' }}"
                                        label="Objeto de gasto opcional"
                                        placeholder="Buscar objeto..."
                                        defaultText="Seleccione un objeto"
                                        clearText="Sin objeto asociado"
                                        searchAction="searchObjetosGastoInventario"
                                        :options="$objetos"
                                        :error="$errors->first('idobjeto')"
                                    />
                                </div>
                                <div class="md:col-span-2">
                                    <x-searchable-select
                                        wire:model="idCubs"
                                        wire:key="inventario-cubs-select-{{ $productoId ?: 'nuevo' }}-{{ $idCubs ?: 'empty' }}"
                                        label="CUBS opcional"
                                        placeholder="Buscar CUBS..."
                                        defaultText="Seleccione un CUBS"
                                        clearText="Sin CUBS asociado"
                                        searchAction="searchCubsInventario"
                                        :options="$cubs"
                                        :error="$errors->first('idCubs')"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="grid grid-cols-1 gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700 sm:grid-cols-3">
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <input type="checkbox" wire:model="maneja_lote" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                Maneja lote
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <input type="checkbox" wire:model="maneja_vencimiento" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                Maneja vencimiento
                            </label>
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                <input type="checkbox" wire:model="activo" class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500">
                                Activo
                            </label>
                        </section>
                    </div>
                </div>

                @if ($errors->any()) <div class="mx-6 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">{{ $errors->first() }}</div> @endif
                <div class="flex justify-end gap-2 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <button wire:click="closeModal" class="cursor-pointer rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 active:translate-y-px dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancelar</button>
                    <button wire:click="save" class="cursor-pointer rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 active:translate-y-px">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
