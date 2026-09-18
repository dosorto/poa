<div class="mx-auto mt-6">
    <div class="bg-white p-6 shadow sm:rounded-lg dark:bg-zinc-900">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ $entradaId ? 'Editar entrada' : 'Nueva entrada' }}</h2>
                <p class="text-sm text-zinc-500">Complete los datos y genere la entrada de inventario.</p>
            </div>
            <a href="{{ route('inventario.entradas') }}" class="inline-flex items-center rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Volver</a>
        </div>

        @php
            $bodegaSeleccionada = $bodegas->firstWhere('id', $bodega_id);
            $fechaEntradaVista = $fecha_entrada ? \Carbon\Carbon::parse($fecha_entrada)->format('d/m/Y') : '-';
            $fechaFacturaVista = $fecha_factura ? \Carbon\Carbon::parse($fecha_factura)->format('d/m/Y') : '-';
            $campoEntradaClass = 'h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm transition focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-950 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-zinc-400 dark:focus:ring-zinc-500';
        @endphp

        <div class="mb-6 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded border px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                {{ $bodegaSeleccionada?->nombre ?? 'Seleccione bodega' }}
            </div>
            <div class="rounded border px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                {{ $numero_factura ?: 'Factura' }}
            </div>
            <div class="rounded border px-3 py-2 text-sm font-semibold text-zinc-800 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                {{ $proveedor ?: 'Proveedor' }}
            </div>
        </div>

        <div class="mb-6 grid grid-cols-[auto_1fr_auto_1fr_auto] items-center gap-3 text-sm font-semibold">
            <div class="flex items-center gap-2 {{ $paso === 1 ? 'text-blue-600' : 'text-zinc-500' }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-full border {{ $paso === 1 ? 'border-blue-600 bg-blue-600 text-white' : 'dark:border-zinc-700' }}">1</span>
                <span>Datos</span>
            </div>
            <div class="h-px bg-zinc-200 dark:bg-zinc-700"></div>
            <div class="flex items-center gap-2 {{ $paso === 2 ? 'text-blue-600' : 'text-zinc-500' }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-full border {{ $paso === 2 ? 'border-blue-600 bg-blue-600 text-white' : 'dark:border-zinc-700' }}">2</span>
                <span>Productos</span>
            </div>
            <div class="h-px bg-zinc-200 dark:bg-zinc-700"></div>
            <div class="flex items-center gap-2 {{ $paso === 3 ? 'text-blue-600' : 'text-zinc-500' }}">
                <span class="flex h-8 w-8 items-center justify-center rounded-full border {{ $paso === 3 ? 'border-blue-600 bg-blue-600 text-white' : 'dark:border-zinc-700' }}">3</span>
                <span>Acta</span>
            </div>
        </div>

        @if($paso === 1)
            <div class="space-y-4">
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/70">
                    <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Detalle de la entrada</p>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $numero_entrada ?: 'Nueva entrada' }}
                            </h3>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-200">
                            Borrador
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                        <div class="rounded border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs uppercase text-zinc-500">Bodega destino</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $bodegaSeleccionada?->nombre ?? 'Seleccione bodega' }}</p>
                        </div>
                        <div class="rounded border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs uppercase text-zinc-500">Fecha de entrada</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $fechaEntradaVista }}</p>
                        </div>
                        <div class="rounded border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs uppercase text-zinc-500">Proveedor</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $proveedor ?: 'Sin proveedor' }}</p>
                        </div>
                        <div class="rounded border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs uppercase text-zinc-500">Factura</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $numero_factura ?: 'Sin factura' }}</p>
                        </div>
                        <div class="rounded border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs uppercase text-zinc-500">Fecha de factura</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $fechaFacturaVista }}</p>
                        </div>
                        <div class="rounded border border-zinc-200 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <p class="text-xs uppercase text-zinc-500">Orden de compra</p>
                            <p class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $orden_compra_referencia ?: '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                    <label>
                        <span class="mb-1 block text-sm font-medium">Número de entrada</span>
                        <x-input wire:model="numero_entrada" class="{{ $campoEntradaClass }}" />
                    </label>
                    <label class="md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">Bodega destino <span class="text-red-500">*</span></span>
                        <select
                            wire:model="bodega_id"
                            class="{{ $campoEntradaClass }}"
                        >
                            <option value="">{{ $bodegas->isEmpty() ? 'No hay bodegas activas' : 'Seleccione una bodega' }}</option>
                            @foreach($bodegas as $bodega)
                                <option value="{{ $bodega->id }}">
                                    {{ $bodega->nombre }}{{ $bodega->ubicacion ? ' - ' . $bodega->ubicacion : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if($bodegas->isEmpty())
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">Active o registre una bodega antes de crear la entrada.</p>
                        @endif
                        @error('bodega_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">Fecha de entrada</span>
                        <x-input wire:model="fecha_entrada" type="date" class="{{ $campoEntradaClass }}" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">Factura</span>
                        <x-input wire:model="numero_factura" class="{{ $campoEntradaClass }}" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">Proveedor</span>
                        <x-input wire:model="proveedor" class="{{ $campoEntradaClass }}" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">Fecha de factura</span>
                        <x-input wire:model="fecha_factura" type="date" class="{{ $campoEntradaClass }}" />
                    </label>
                    <label>
                        <span class="mb-1 block text-sm font-medium">Orden de compra</span>
                        <x-input wire:model="orden_compra_referencia" class="{{ $campoEntradaClass }}" />
                    </label>
                    <label class="md:col-span-2">
                        <span class="mb-1 block text-sm font-medium">Observación</span>
                        <x-input wire:model="observacion" class="{{ $campoEntradaClass }}" />
                    </label>
                </div>
            </div>
        @endif

        @if($paso === 2)
            <section class="border-t pt-5 dark:border-zinc-700">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Productos de la entrada</h3>
                        <p class="text-sm text-zinc-500">Agregue únicamente productos completos y validados.</p>
                    </div>
                    <x-spinner-button wire:click="openProductoModal" loadingTarget="openProductoModal" :loadingText="__('Abriendo...')">Agregar producto</x-spinner-button>
                </div>
                @if(empty($detalles))
                    <div class="rounded-lg bg-zinc-50 py-12 text-center dark:bg-zinc-800">
                        <p class="font-medium">Sin productos agregados</p>
                        <p class="mt-1 text-sm text-zinc-500">Use “Agregar producto” para registrar el primer producto.</p>
                    </div>
                @else
                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="min-w-full divide-y dark:divide-zinc-700">
                            <thead class="bg-zinc-100 dark:bg-zinc-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Producto</th>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Lote</th>
                                    <th class="px-4 py-3 text-center text-xs uppercase">Cantidad</th>
                                    <th class="px-4 py-3 text-right text-xs uppercase">Costo unit.</th>
                                    <th class="px-4 py-3 text-left text-xs uppercase">Vencimiento</th>
                                    <th class="px-4 py-3 text-right text-xs uppercase">Total</th>
                                    <th class="px-4 py-3 text-center text-xs uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-zinc-700">
                                @foreach($detalles as $index => $detalle)
                                    @php
                                        $producto = $productosPorId->get($detalle['producto_id']);
                                        $total = (float) $detalle['cantidad'] * (float) ($detalle['costo_unitario'] ?: 0);
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium">{{ $producto?->codigo_interno }} - {{ $producto?->nombre }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $detalle['codigo_lote'] ?: 'Sin lote' }}</td>
                                        <td class="px-4 py-3 text-center text-sm">{{ number_format((float) $detalle['cantidad'], 2) }}</td>
                                        <td class="px-4 py-3 text-right text-sm">L {{ number_format((float) ($detalle['costo_unitario'] ?: 0), 2) }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $detalle['fecha_vencimiento'] ?: 'No aplica' }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-blue-600">L {{ number_format($total, 2) }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <button wire:click="removeDetalle({{ $index }})" class="cursor-pointer text-red-600">Quitar</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-zinc-100 dark:bg-zinc-700">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-right font-semibold">Total de la entrada:</td>
                                    <td class="px-4 py-3 text-right font-bold text-blue-600">L {{ number_format(collect($detalles)->sum(fn ($item) => (float) $item['cantidad'] * (float) ($item['costo_unitario'] ?: 0)), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </section>
        @endif

        @if($paso === 3)
            <section class="border-t pt-5 dark:border-zinc-700">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold">Acta de recepción</h3>
                        <p class="text-sm text-zinc-500">Lista para imprimir o descargar.</p>
                    </div>
                    @if($actaRecepcionDownloadUrl)
                        <a href="{{ $actaRecepcionDownloadUrl }}" class="inline-flex items-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-zinc-900 dark:bg-indigo-800 dark:border-indigo-700 dark:hover:bg-indigo-700 dark:focus:bg-indigo-900 dark:focus:ring-offset-indigo-800" target="_blank">Descargar</a>
                    @endif
                </div>

                @if($actaRecepcionUrl)
                    <div class="overflow-hidden rounded-lg border bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950">
                        <iframe src="{{ $actaRecepcionUrl }}" class="h-[78vh] w-full bg-transparent" title="Acta de recepción"></iframe>
                    </div>
                @else
                    <div class="rounded-lg bg-zinc-50 py-12 text-center dark:bg-zinc-800">
                        <p class="font-medium">El acta aparecerá después de generar la entrada.</p>
                    </div>
                @endif
            </section>
        @endif

        <div class="mt-6 flex justify-end gap-2">
            @if($paso === 1)
                <a href="{{ route('inventario.entradas') }}" class="inline-flex items-center rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Cancelar</a>
                <x-spinner-button wire:click="siguientePaso" loadingTarget="siguientePaso" :loadingText="__('Validando...')">Siguiente</x-spinner-button>
            @elseif($paso === 2)
                <x-secondary-button wire:click="pasoAnterior">Atrás</x-secondary-button>
                <x-spinner-button wire:click="save" loadingTarget="save" :loadingText="__('Generando...')">Generar entrada</x-spinner-button>
            @else
                @if($flujoFinalizado)
                    <button type="button" disabled class="inline-flex cursor-not-allowed items-center rounded-md border border-zinc-300 bg-zinc-100 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-zinc-500 opacity-80 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                        Finalizado
                    </button>
                @else
                    <x-spinner-button type="button" wire:click="abrirConfirmacionFinalizar" loadingTarget="abrirConfirmacionFinalizar" :loadingText="__('Abriendo...')">Finalizar</x-spinner-button>
                @endif
            @endif
        </div>

        <x-dialog-modal wire:model="showProductoModal" max-width="2xl">
            <x-slot name="title">Agregar producto a la entrada</x-slot>
            <x-slot name="content">
                <div class="space-y-4">
                    <x-searchable-select wire:model.live="nuevoDetalle.producto_id" wire:key="entrada-nuevo-producto-{{ $nuevoDetalle['producto_id'] ?? 'empty' }}" label="Producto" :required="true" placeholder="Buscar por código o nombre..." defaultText="Seleccione un producto" :options="$productosOptions" :error="$errors->first('nuevoDetalle.producto_id')" />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <label>
                            <span class="mb-1 block text-sm font-medium">Lote</span>
                            <x-input wire:model="nuevoDetalle.codigo_lote" class="h-10 w-full px-3" />
                        </label>
                        <label>
                            <span class="mb-1 block text-sm font-medium">Cantidad *</span>
                            <x-input wire:model="nuevoDetalle.cantidad" type="number" min="0.01" step="0.01" class="h-10 w-full px-3" />
                            @error('nuevoDetalle.cantidad')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
                        </label>
                        <label>
                            <span class="mb-1 block text-sm font-medium">Costo unitario</span>
                            <x-input wire:model="nuevoDetalle.costo_unitario" type="number" min="0" step="0.01" class="h-10 w-full px-3" />
                        </label>
                        <label>
                            <span class="mb-1 block text-sm font-medium">Vencimiento</span>
                            <x-input wire:model="nuevoDetalle.fecha_vencimiento" type="date" class="h-10 w-full px-3" />
                        </label>
                    </div>
                </div>
            </x-slot>
            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showProductoModal', false)">Cancelar</x-secondary-button>
                <x-spinner-button wire:click="agregarProducto" class="ml-2" loadingTarget="agregarProducto" :loadingText="__('Agregando...')">Agregar producto</x-spinner-button>
            </x-slot>
        </x-dialog-modal>

        <x-dialog-modal wire:model="showFinalizarModal" max-width="md">
            <x-slot name="title">Finalizar entrada</x-slot>
            <x-slot name="content">
                <div class="flex gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-zinc-100">¿Está seguro de finalizar esta entrada?</p>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">El acta de recepción ya fue generada y la entrada quedó registrada en inventario.</p>
                    </div>
                </div>
            </x-slot>
            <x-slot name="footer">
                <x-secondary-button wire:click="cerrarConfirmacionFinalizar">Cancelar</x-secondary-button>
                <x-spinner-button wire:click="finalizarFlujo" class="ml-2" loadingTarget="finalizarFlujo" :loadingText="__('Finalizando...')">Finalizar</x-spinner-button>
            </x-slot>
        </x-dialog-modal>

        <x-dialog-modal wire:model="showAdvertenciaModal" max-width="md">
            <x-slot name="title">{{ $advertenciaTitulo ?: 'No se puede continuar' }}</x-slot>
            <x-slot name="content">
                <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $advertenciaMensaje }}</p>
            </x-slot>
            <x-slot name="footer">
                <x-secondary-button wire:click="cerrarAdvertencia">Entendido</x-secondary-button>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>
