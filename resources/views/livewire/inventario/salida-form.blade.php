<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 shadow sm:rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold">{{ $salidaId ? 'Editar salida' : 'Nueva salida' }}</h2>
                <p class="text-sm text-zinc-500">Complete los datos y genere la entrega de inventario.</p>
            </div>
            <a href="{{ route('inventario.salidas') }}" class="px-4 py-2 border rounded">Volver</a>
        </div>

        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        @if (session()->has('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif
        @php
            $bodegaSeleccionada = $bodegas->firstWhere('id', $bodega_id) ?? $bodegas->first();
            $actaVista = $actaSeleccionada ?? $actas->firstWhere('id', $acta_entrega_id);
        @endphp

        <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded border px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                {{ $bodegaSeleccionada?->nombre ?? 'No hay bodega activa' }}
            </div>
            <div class="rounded border px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                Entrega por acta
            </div>
            <div class="rounded border px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                {{ $actaVista?->requisicion?->correlativo ?? $actaVista?->correlativo ?? 'Seleccione acta' }}
            </div>
        </div>

        <div class="mb-6 flex items-center gap-3">
            <button type="button" wire:click="$set('paso', 1)" class="flex items-center gap-2 text-sm font-semibold {{ $paso === 1 ? 'text-blue-600' : 'text-zinc-500' }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border {{ $paso === 1 ? 'border-blue-600 bg-blue-600 text-white' : 'border-zinc-300 dark:border-zinc-600' }}">1</span>
                Datos
            </button>
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
            <button type="button" wire:click="siguientePaso" class="flex items-center gap-2 text-sm font-semibold {{ $paso === 2 ? 'text-blue-600' : 'text-zinc-500' }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border {{ $paso === 2 ? 'border-blue-600 bg-blue-600 text-white' : 'border-zinc-300 dark:border-zinc-600' }}">2</span>
                Productos
            </button>
            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
            <button type="button" @if ($salidaId) wire:click="$set('paso', 3)" @endif class="flex items-center gap-2 text-sm font-semibold {{ $paso === 3 ? 'text-blue-600' : 'text-zinc-500' }} {{ ! $salidaId ? 'cursor-not-allowed opacity-60' : '' }}">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border {{ $paso === 3 ? 'border-blue-600 bg-blue-600 text-white' : 'border-zinc-300 dark:border-zinc-600' }}">3</span>
                Acta
            </button>
        </div>

        @if ($paso === 1)
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                @unless ($actaBloqueada)
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Acta de entrega <span class="text-red-500">*</span></label>
                        <select wire:model.live="acta_entrega_id" class="w-full rounded border px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                            <option value="">Seleccione acta</option>
                            @foreach ($actas as $acta)
                                <option value="{{ $acta->id }}">{{ $acta->correlativo }} - {{ $acta->requisicion?->correlativo }}</option>
                            @endforeach
                        </select>
                        @error('acta_entrega_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                @endunless

                <div class="rounded border px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Número de acta</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $actaVista?->correlativo ?? 'Seleccione acta' }}</p>
                </div>
                <div class="rounded border px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-xs uppercase text-zinc-500">Requisición</p>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $actaVista?->requisicion?->correlativo ?? 'Pendiente' }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Empleado recibe</label>
                    <select wire:model.live="empleado_recibe_id" class="w-full rounded border px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                        <option value="">Empleado recibe</option>
                        @foreach ($empleados as $empleado) <option value="{{ $empleado->id }}">{{ $empleado->nombre }} {{ $empleado->apellido }}</option> @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Observación</label>
                    <textarea wire:model.live="observacion" class="w-full rounded border px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" placeholder="Observación"></textarea>
                </div>
            </div>
        @endif

        @if ($paso === 2)
        <div class="mt-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">Productos de la requisición</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ $acta_entrega_id ? 'Agregue los productos y lotes que se despacharán.' : 'Seleccione un acta para cargar sus productos.' }}</p>
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
                                    <td class="px-4 py-3 text-sm">{{ $existenciaDetalle?->lote?->codigo_lote ?? 'Sin existencia disponible' }}</td>
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
        @endif

        @if ($paso === 3)
            <div class="mt-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200">{{ $actaTitulo }}</h3>
                        <p class="mt-1 text-sm text-zinc-500">Lista para imprimir o descargar.</p>
                    </div>
                    @if ($actaDownloadUrl)
                        <a href="{{ $actaDownloadUrl }}" target="_blank" class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition active:translate-y-px">Descargar</a>
                    @endif
                </div>
                @if ($actaPdfUrl)
                    <iframe src="{{ $actaPdfUrl }}" class="h-[70vh] w-full rounded-lg border border-zinc-200 dark:border-zinc-700" type="application/pdf">
                        <p class="p-6 text-center text-zinc-500">Tu navegador no puede mostrar el PDF.</p>
                    </iframe>
                @else
                    <div class="rounded-lg bg-zinc-50 py-12 text-center text-zinc-500 dark:bg-zinc-800">Seleccione un acta para ver la impresión.</div>
                @endif
            </div>
        @endif

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

        <x-dialog-modal wire:model="showGuardarModal" max-width="md">
            <x-slot name="title">Generar entrega</x-slot>
            <x-slot name="content">
                <div class="space-y-3">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">¿Está seguro de generar esta entrega?</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        @if (empty($detalles) && $actaSeleccionada && strtolower((string) $actaSeleccionada->tipoActaEntrega?->tipo) === 'final')
                            No hay productos pendientes para despachar. Se cerrará la requisición sin movimiento de inventario.
                        @else
                            Se descontarán las existencias seleccionadas y quedará registrada en el historial de entregas.
                        @endif
                    </p>
                </div>
            </x-slot>
            <x-slot name="footer">
                <x-secondary-button wire:click="cerrarConfirmacionGuardar">Cancelar</x-secondary-button>
                <x-spinner-button wire:click="save" class="ml-2" loadingTarget="save" :loadingText="__('Generando...')">Generar entrega</x-spinner-button>
            </x-slot>
        </x-dialog-modal>

        <x-dialog-modal wire:model="showAdvertenciaModal" max-width="md">
            <x-slot name="title">{{ $advertenciaTitulo ?: 'Advertencia' }}</x-slot>
            <x-slot name="content">
                <div class="flex gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                        </svg>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $advertenciaMensaje }}</p>
                </div>
            </x-slot>
            <x-slot name="footer">
                <x-secondary-button wire:click="cerrarAdvertencia">Entendido</x-secondary-button>
            </x-slot>
        </x-dialog-modal>

        @if ($errors->any()) <div class="mt-4 text-sm text-red-600">{{ $errors->first() }}</div> @endif
        <div class="flex justify-end gap-2 mt-6">
            <a href="{{ route('inventario.salidas') }}" class="px-4 py-2 border rounded">Cancelar</a>
            @if ($paso === 1)
                <button type="button" wire:click="siguientePaso" class="cursor-pointer px-4 py-2 bg-blue-600 text-white rounded">Siguiente</button>
            @elseif ($paso === 2)
                <button type="button" wire:click="pasoAnterior" class="cursor-pointer px-4 py-2 border rounded">Atrás</button>
                <button type="button" wire:click="abrirConfirmacionGuardar" class="cursor-pointer px-4 py-2 bg-blue-600 text-white rounded">Generar entrega</button>
            @else
                <button type="button" wire:click="pasoAnterior" class="cursor-pointer px-4 py-2 border rounded">Atrás</button>
                <a href="{{ route('inventario.salidas') }}" class="px-4 py-2 bg-blue-600 text-white rounded">Finalizar</a>
            @endif
        </div>
    </div>
</div>
