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
                <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                    <table class="min-w-full divide-y dark:divide-zinc-700">
                        <thead class="bg-zinc-100 dark:bg-zinc-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs uppercase">Recurso</th>
                                <th class="px-4 py-3 text-left text-xs uppercase">Producto</th>
                                <th class="px-4 py-3 text-left text-xs uppercase">Lote</th>
                                <th class="px-4 py-3 text-center text-xs uppercase">Cantidad</th>
                                <th class="px-4 py-3 text-center text-xs uppercase">Máximo</th>
                                <th class="px-4 py-3 text-center text-xs uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-zinc-700">
                            @foreach ($detalles as $index => $detalle)
                                @php
                                    $detalleActa = collect($detallesActaDisponibles)->firstWhere('id', (int) $detalle['detalle_acta_entrega_id']);
                                    $productoDetalle = collect($productosPorDetalleActa[$detalle['detalle_acta_entrega_id']] ?? [])->firstWhere('id', (int) $detalle['producto_id']);
                                    $existenciaDetalle = $existencias->first(fn ($item) => (int) $item->producto_id === (int) $detalle['producto_id'] && (int) $item->lote_id === (int) $detalle['lote_id'] && (int) $item->bodega_id === (int) $bodega_id);
                                @endphp
                                <tr wire:key="salida-producto-{{ $index }}">
                                    <td class="px-4 py-3 text-sm font-medium">{{ $detalleActa['recurso'] ?? $detalle['recurso'] ?? 'Recurso' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium">{{ $productoDetalle['text'] ?? $detalle['producto_nombre'] ?? 'Producto no disponible' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $existenciaDetalle?->lote?->codigo_lote ?? 'Sin lote' }}</td>
                                    <td class="px-4 py-3 text-center text-sm">{{ number_format((float) $detalle['cantidad'], 2) }}</td>
                                    <td class="px-4 py-3 text-center text-sm">{{ number_format((float) ($detalleActa['cantidad_autorizada'] ?? $detalle['cantidad_autorizada'] ?? 0), 2, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="removeDetalle({{ $index }})" class="cursor-pointer font-semibold text-red-600">Quitar</button>
                                    </td>
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
