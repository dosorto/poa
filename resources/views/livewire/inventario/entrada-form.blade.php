<div class="mx-auto mt-6">
    <div class="bg-white p-6 shadow sm:rounded-lg dark:bg-zinc-900">
        <div class="mb-6 flex items-center justify-between">
            <div><h2 class="text-xl font-semibold">{{ $entradaId ? 'Editar entrada' : 'Nueva entrada' }}</h2><p class="text-sm text-zinc-500">Registre el ingreso físico antes de confirmar el movimiento.</p></div>
            <a href="{{ route('inventario.entradas') }}" class="rounded border px-4 py-2">Volver</a>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <label><span class="mb-1 block text-sm font-medium">Número de entrada</span><input wire:model="numero_entrada" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
            <label>
                <span class="mb-1 block text-sm font-medium">Bodega destino</span>
                <select wire:model="bodega_id" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <option value="">Seleccione una bodega</option>
                    @foreach($bodegas as $bodega)
                        <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                    @endforeach
                </select>
                @error('bodega_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </label>
            <label><span class="mb-1 block text-sm font-medium">Fecha de entrada</span><input wire:model="fecha_entrada" type="date" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
            <label><span class="mb-1 block text-sm font-medium">Factura</span><input wire:model="numero_factura" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
            <label><span class="mb-1 block text-sm font-medium">Proveedor</span><input wire:model="proveedor" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
            <label><span class="mb-1 block text-sm font-medium">Fecha de factura</span><input wire:model="fecha_factura" type="date" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
            <label><span class="mb-1 block text-sm font-medium">Orden de compra</span><input wire:model="orden_compra_referencia" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
            <label><span class="mb-1 block text-sm font-medium">Requisición opcional</span><select wire:model="requisicion_id" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"><option value="">Sin requisición</option>@foreach($requisiciones as $requisicion)<option value="{{ $requisicion->id }}">{{ $requisicion->correlativo }}</option>@endforeach</select></label>
            <label><span class="mb-1 block text-sm font-medium">Observación</span><input wire:model="observacion" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
        </div>

        <section class="mt-7 border-t pt-5 dark:border-zinc-700">
            <div class="mb-4 flex items-center justify-between">
                <div><h3 class="text-lg font-semibold">Productos de la entrada</h3><p class="text-sm text-zinc-500">Agregue únicamente productos completos y validados.</p></div>
                <x-spinner-button wire:click="openProductoModal" loadingTarget="openProductoModal" :loadingText="__('Abriendo...')">Agregar producto</x-spinner-button>
            </div>
            @if(empty($detalles))
                <div class="rounded-lg bg-zinc-50 py-12 text-center dark:bg-zinc-800"><p class="font-medium">Sin productos agregados</p><p class="mt-1 text-sm text-zinc-500">Use “Agregar producto” para registrar el primer producto.</p></div>
            @else
                <div class="overflow-x-auto rounded-lg border dark:border-zinc-700"><table class="min-w-full divide-y dark:divide-zinc-700">
                    <thead class="bg-zinc-100 dark:bg-zinc-700"><tr><th class="px-4 py-3 text-left text-xs uppercase">Producto</th><th class="px-4 py-3 text-left text-xs uppercase">Lote</th><th class="px-4 py-3 text-center text-xs uppercase">Cantidad</th><th class="px-4 py-3 text-right text-xs uppercase">Costo unit.</th><th class="px-4 py-3 text-left text-xs uppercase">Vencimiento</th><th class="px-4 py-3 text-right text-xs uppercase">Total</th><th class="px-4 py-3 text-center text-xs uppercase">Acciones</th></tr></thead>
                    <tbody class="divide-y dark:divide-zinc-700">@foreach($detalles as $index => $detalle)@php $producto=$productosPorId->get($detalle['producto_id']); $total=(float)$detalle['cantidad']*(float)($detalle['costo_unitario'] ?: 0); @endphp
                        <tr><td class="px-4 py-3 text-sm font-medium">{{ $producto?->codigo_interno }} - {{ $producto?->nombre }}</td><td class="px-4 py-3 text-sm">{{ $detalle['codigo_lote'] ?: 'Sin lote' }}</td><td class="px-4 py-3 text-center text-sm">{{ number_format((float)$detalle['cantidad'], 2) }}</td><td class="px-4 py-3 text-right text-sm">L {{ number_format((float)($detalle['costo_unitario'] ?: 0), 2) }}</td><td class="px-4 py-3 text-sm">{{ $detalle['fecha_vencimiento'] ?: 'No aplica' }}</td><td class="px-4 py-3 text-right font-semibold text-blue-600">L {{ number_format($total, 2) }}</td><td class="px-4 py-3 text-center"><button wire:click="removeDetalle({{ $index }})" class="cursor-pointer text-red-600">Quitar</button></td></tr>
                    @endforeach</tbody>
                    <tfoot class="bg-zinc-100 dark:bg-zinc-700"><tr><td colspan="5" class="px-4 py-3 text-right font-semibold">Total de la entrada:</td><td class="px-4 py-3 text-right font-bold text-blue-600">L {{ number_format(collect($detalles)->sum(fn($item)=>(float)$item['cantidad']*(float)($item['costo_unitario'] ?: 0)), 2) }}</td><td></td></tr></tfoot>
                </table></div>
            @endif
        </section>

        @if($errors->any())<div class="mt-4 rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
        <div class="mt-6 flex justify-end gap-2"><a href="{{ route('inventario.entradas') }}" class="rounded border px-4 py-2">Cancelar</a><x-spinner-button wire:click="save" loadingTarget="save" :loadingText="__('Guardando...')">Guardar borrador</x-spinner-button></div>

        <x-dialog-modal wire:model="showProductoModal" max-width="2xl">
            <x-slot name="title">Agregar producto a la entrada</x-slot>
            <x-slot name="content"><div class="space-y-4">
                <x-searchable-select wire:model.live="nuevoDetalle.producto_id" wire:key="entrada-nuevo-producto-{{ $nuevoDetalle['producto_id'] ?? 'empty' }}" label="Producto" :required="true" placeholder="Buscar por código o nombre..." defaultText="Seleccione un producto" :options="$productosOptions" :error="$errors->first('nuevoDetalle.producto_id')" />
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <label><span class="mb-1 block text-sm font-medium">Lote</span><input wire:model="nuevoDetalle.codigo_lote" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
                    <label><span class="mb-1 block text-sm font-medium">Cantidad *</span><input wire:model="nuevoDetalle.cantidad" type="number" min="0.01" step="0.01" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800">@error('nuevoDetalle.cantidad')<p class="text-xs text-red-500">{{ $message }}</p>@enderror</label>
                    <label><span class="mb-1 block text-sm font-medium">Costo unitario</span><input wire:model="nuevoDetalle.costo_unitario" type="number" min="0" step="0.01" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
                    <label><span class="mb-1 block text-sm font-medium">Vencimiento</span><input wire:model="nuevoDetalle.fecha_vencimiento" type="date" class="h-10 w-full rounded-md border px-3 dark:border-zinc-700 dark:bg-zinc-800"></label>
                </div>
            </div></x-slot>
            <x-slot name="footer"><x-secondary-button wire:click="$set('showProductoModal', false)">Cancelar</x-secondary-button><x-spinner-button wire:click="agregarProducto" class="ml-2" loadingTarget="agregarProducto" :loadingText="__('Agregando...')">Agregar producto</x-spinner-button></x-slot>
        </x-dialog-modal>
    </div>
</div>
