<div class="mx-auto mt-6">
    <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-zinc-900">
        @if (session()->has('message'))
            <div class="mb-4 text-green-700 dark:text-green-400">{{ session('message') }}</div>
        @endif

        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold">Productos de inventario</h2>
            <div class="flex gap-2">
                <x-input wire:model.live="search" class="px-3 py-2" placeholder="Código, barra o nombre" />
                @can('inventario.productos.crear')
                    <x-spinner-button type="button" wire:click="create" loadingTarget="create" :loadingText="__('Abriendo...')">Nuevo</x-spinner-button>
                @endcan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left dark:border-zinc-700">
                        <th class="py-2">Código</th>
                        <th>Nombre</th>
                        <th>Unidad</th>
                        <th>Recurso</th>
                        <th>CUBS</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($productos as $producto)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $producto->codigo_interno }}</td>
                            <td>
                                <div class="font-medium">{{ $producto->nombre }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $producto->codigo_barra }}</div>
                            </td>
                            <td>{{ $producto->unidadMedida?->nombre }}</td>
                            <td>{{ str($producto->recurso?->nombre)->limit(35) }}</td>
                            <td>{{ $producto->idCubs }}</td>
                            <td>{{ $producto->activo ? 'Activo' : 'Inactivo' }}</td>
                            <td class="space-x-3 text-right">
                                @can('inventario.productos.editar')
                                    <button type="button" wire:click="edit({{ $producto->id }})" class="cursor-pointer text-blue-600 transition active:translate-y-px dark:text-blue-400">Editar</button>
                                    <button type="button" wire:click="toggleActivo({{ $producto->id }})" class="cursor-pointer text-zinc-600 transition active:translate-y-px dark:text-zinc-300">Cambiar estado</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-zinc-500">Sin productos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $productos->links() }}</div>
    </div>

    <x-dialog-modal wire:model="showModal" max-width="4xl">
        <x-slot name="title">Producto de inventario</x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <section>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Código de barra</span>
                            <x-input wire:model="codigo_barra" class="h-10 w-full px-3" />
                            @error('codigo_barra') <x-input-error for="codigo_barra" class="mt-1" /> @enderror
                        </label>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre</span>
                        <x-input wire:model="nombre" class="h-10 w-full px-3" />
                        @error('nombre') <x-input-error for="nombre" class="mt-1" /> @enderror
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Código interno</span>
                        <x-input wire:model="codigo_interno" class="h-10 w-full px-3" placeholder="Se genera automáticamente si queda vacío" />
                        @error('codigo_interno') <x-input-error for="codigo_interno" class="mt-1" /> @enderror
                    </label>
                </section>

                <section>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Detalle técnico</span>
                        <x-textarea wire:model="descripcion" rows="4" />
                        @error('descripcion') <x-input-error for="descripcion" class="mt-1" /> @enderror
                    </label>
                </section>

                <section>
                    <h4 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-white">Clasificación y control</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Unidad de medida</span>
                            <x-select wire:model="unidad_medida_id" class="h-10 px-3">
                                <option value="">Seleccionar unidad</option>
                                @foreach ($unidades as $unidad)
                                    <option value="{{ $unidad->id }}">{{ $unidad->nombre }}</option>
                                @endforeach
                            </x-select>
                            @error('unidad_medida_id') <x-input-error for="unidad_medida_id" class="mt-1" /> @enderror
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Stock mínimo</span>
                            <x-input wire:model="stock_minimo" type="number" step="0.01" class="h-10 w-full px-3" />
                            @error('stock_minimo') <x-input-error for="stock_minimo" class="mt-1" /> @enderror
                        </label>

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
                </section>

                <section class="grid grid-cols-1 gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700 sm:grid-cols-3">
                    <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        <x-checkbox wire:model="maneja_lote" />
                        <span>Maneja lote</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        <x-checkbox wire:model="maneja_vencimiento" />
                        <span>Maneja vencimiento</span>
                    </label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        <x-checkbox wire:model="activo" />
                        <span>Activo</span>
                    </label>
                </section>

                @if ($errors->any())
                    <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                        {{ $errors->first() }}
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeModal">Cancelar</x-secondary-button>
            <x-spinner-button wire:click="save" class="ml-2" loadingTarget="save" :loadingText="__('Guardando...')">Guardar</x-spinner-button>
        </x-slot>
    </x-dialog-modal>
</div>
