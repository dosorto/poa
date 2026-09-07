<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 shadow sm:rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold">{{ $salidaId ? 'Editar salida' : 'Nueva salida' }}</h2>
                <p class="text-sm text-zinc-500">Complete los datos y guarde la salida como borrador.</p>
            </div>
            <a href="{{ route('inventario.salidas') }}" class="px-4 py-2 border rounded">Volver</a>
        </div>

        @if (session()->has('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif
        @php $bodegaSeleccionada = $bodegas->firstWhere('id', $bodega_id) ?? $bodegas->first(); @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input wire:model.live="numero_salida" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Número de salida">
            <div class="border rounded px-3 py-2 text-sm text-zinc-700 dark:bg-zinc-800 dark:border-zinc-700 dark:text-zinc-200">
                {{ $bodegaSeleccionada?->nombre ?? 'No hay bodega activa' }}
            </div>
            <input wire:model.live="fecha_salida" type="date" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
            <div class="border rounded px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700">Entrega por acta intermedia</div>
            <select wire:model.live="acta_entrega_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                <option value="">Seleccione acta intermedia *</option>
                @foreach ($actas as $acta) <option value="{{ $acta->id }}">{{ $acta->correlativo }} — {{ $acta->requisicion?->correlativo }}</option> @endforeach
            </select>
            <div class="border rounded px-3 py-2 text-sm dark:bg-zinc-800 dark:border-zinc-700">
                {{ $actas->firstWhere('id', $acta_entrega_id)?->requisicion?->correlativo ?? 'La requisición se cargará desde el acta' }}
            </div>
            <input wire:model.live="motivo" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Motivo">
            <select wire:model.live="departamento_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                <option value="">Departamento</option>
                @foreach ($departamentos as $departamento) <option value="{{ $departamento->id }}">{{ $departamento->name }}</option> @endforeach
            </select>
            <select wire:model.live="empleado_recibe_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                <option value="">Empleado recibe</option>
                @foreach ($empleados as $empleado) <option value="{{ $empleado->id }}">{{ $empleado->nombre }} {{ $empleado->apellido }}</option> @endforeach
            </select>
            <textarea wire:model.live="observacion" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700 md:col-span-3" placeholder="Observación"></textarea>
        </div>

        <div class="mt-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Productos de la requisición</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ $acta_entrega_id ? 'Agregue los productos y lotes que se despacharán.' : 'Seleccione un acta intermedia para cargar sus productos.' }}</p>
                </div>
                <x-spinner-button wire:click="openProductoModal" loadingTarget="openProductoModal" :loadingText="__('Abriendo...')" :disabled="!$acta_entrega_id" class="{{ !$acta_entrega_id ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' }}">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Agregar producto
                </x-spinner-button>
            </div>

            @if (empty($detalles))
                <div class="text-center py-12 bg-zinc-50 dark:bg-zinc-800 rounded-lg">
                    <svg class="mx-auto h-12 w-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                    <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-zinc-100">Sin productos agregados</h3>
                    <p class="mt-1 text-sm text-zinc-500">Seleccione un acta y agregue los productos que saldrán de bodega.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-700"><tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Recurso</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Producto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase">Lote</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 uppercase">Cantidad</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 uppercase">Acciones</th>
                        </tr></thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach ($detalles as $index => $detalle)
                                @php
                                    $detalleActa = collect($detallesActaDisponibles)->firstWhere('id', (int) $detalle['detalle_acta_entrega_id']);
                                    $productoDetalle = collect($productosPorDetalleActa[$detalle['detalle_acta_entrega_id']] ?? [])->firstWhere('id', (int) $detalle['producto_id']);
                                    $existenciaDetalle = $existencias->first(fn ($item) => (int) $item->producto_id === (int) $detalle['producto_id'] && (int) $item->lote_id === (int) $detalle['lote_id'] && (int) $item->bodega_id === (int) $bodega_id);
                                @endphp
                                <tr wire:key="salida-producto-{{ $index }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-4 py-3 text-sm font-medium">{{ $detalleActa['recurso'] ?? $detalle['recurso'] ?? 'Recurso' }}</td>
                                    <td class="min-w-72 px-4 py-3 text-sm">
                                        <x-searchable-select
                                            wire:model.live="detalles.{{ $index }}.producto_id"
                                            wire:key="salida-producto-auto-{{ $detalle['detalle_acta_entrega_id'] }}-{{ $index }}-{{ $detalle['producto_id'] ?: 'empty' }}"
                                            placeholder="Buscar producto..."
                                            defaultText="Seleccione un producto"
                                            :options="$productosPorDetalleActa[$detalle['detalle_acta_entrega_id']] ?? []"
                                            :error="$errors->first('detalles.' . $index . '.producto_id')"
                                        />
                                    </td>
                                    <td class="min-w-56 px-4 py-3 text-sm">
                                        <select wire:model.live="detalles.{{ $index }}.lote_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                            <option value="">Seleccione lote</option>
                                            @foreach ($existencias->where('producto_id', $detalle['producto_id'] ?? null)->where('bodega_id', $bodega_id) as $existencia)
                                                <option value="{{ $existencia->lote_id }}">{{ $existencia->lote?->codigo_lote }} / disponible {{ $existencia->cantidad_disponible }}</option>
                                            @endforeach
                                        </select>
                                        @error('detalles.' . $index . '.lote_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="min-w-32 px-4 py-3 text-sm text-center">
                                        <input wire:model="detalles.{{ $index }}.cantidad" type="number" min="0.01" step="0.01" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-center dark:border-zinc-600 dark:bg-zinc-900">
                                        <p class="mt-1 text-xs text-zinc-500">Máximo: {{ number_format((float) ($detalleActa['cantidad_autorizada'] ?? 0), 2, ',', '.') }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center"><button type="button" wire:click="removeDetalle({{ $index }})" class="text-red-600 hover:text-red-800 cursor-pointer">Quitar</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <x-dialog-modal wire:model="showProductoModal" max-width="2xl">
            <x-slot name="title">Agregar producto</x-slot>
            <x-slot name="content">
                <div class="space-y-4">
                    <x-searchable-select wire:model.live="nuevoDetalle.detalle_acta_entrega_id" wire:key="recurso-producto-{{ $nuevoDetalle['detalle_acta_entrega_id'] ?: 'empty' }}" label="Recurso de la requisición" :required="true" placeholder="Buscar recurso..." defaultText="Seleccione un recurso" :options="$detallesActaDisponibles" :error="$errors->first('nuevoDetalle.detalle_acta_entrega_id')" />
                    <x-searchable-select wire:model.live="nuevoDetalle.producto_id" wire:key="producto-salida-{{ $nuevoDetalle['detalle_acta_entrega_id'] ?: 'empty' }}-{{ $nuevoDetalle['producto_id'] ?: 'empty' }}" label="Producto de inventario" :required="true" placeholder="Buscar producto..." defaultText="Seleccione un producto" :options="$productosPorDetalleActa[$nuevoDetalle['detalle_acta_entrega_id'] ?? 0] ?? []" :disabled="!$nuevoDetalle['detalle_acta_entrega_id']" :error="$errors->first('nuevoDetalle.producto_id')" />
                    <div>
                        <label class="block mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">Lote <span class="text-red-500">*</span></label>
                        <select wire:model.live="nuevoDetalle.lote_id" class="w-full border rounded-md px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                            <option value="">Seleccione lote disponible</option>
                            @foreach ($existencias->where('producto_id', $nuevoDetalle['producto_id'] ?? null)->where('bodega_id', $bodega_id) as $existencia)<option value="{{ $existencia->lote_id }}">{{ $existencia->lote?->codigo_lote }} / disponible {{ $existencia->cantidad_disponible }}</option>@endforeach
                        </select>
                        @error('nuevoDetalle.lote_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block mb-1 text-sm font-medium text-zinc-700 dark:text-zinc-300">Cantidad <span class="text-red-500">*</span></label>
                        <input wire:model="nuevoDetalle.cantidad" type="number" min="0.01" step="0.01" class="w-full border rounded-md px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        @error('nuevoDetalle.cantidad') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-slot>
            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showProductoModal', false)">Cancelar</x-secondary-button>
                <x-spinner-button wire:click="agregarProducto" class="ml-2" loadingTarget="agregarProducto" :loadingText="__('Agregando...')">Agregar producto</x-spinner-button>
            </x-slot>
        </x-dialog-modal>

        @if ($errors->any()) <div class="mt-4 text-sm text-red-600">{{ $errors->first() }}</div> @endif
        <div class="flex justify-end gap-2 mt-6">
            <a href="{{ route('inventario.salidas') }}" class="px-4 py-2 border rounded">Cancelar</a>
            <button type="button" wire:click="save" class="cursor-pointer px-4 py-2 bg-blue-600 text-white rounded">Guardar borrador</button>
        </div>
    </div>
</div>
