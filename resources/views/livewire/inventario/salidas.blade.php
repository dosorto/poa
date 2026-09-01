<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        @if (session()->has('error')) <div class="mb-4 text-red-700">{{ session('error') }}</div> @endif
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <h2 class="text-xl font-semibold">Salidas de inventario</h2>
            <div class="flex gap-2">
                <input wire:model.live="search" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Buscar salida">
                @can('inventario.salidas.crear')
                    <button wire:click="create" class="cursor-pointer bg-blue-600 text-white px-4 py-2 rounded transition active:translate-y-px">Nueva</button>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b dark:border-zinc-700"><th class="py-2">Numero</th><th>Bodega</th><th>Tipo</th><th>Acta</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($salidas as $salida)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $salida->numero_salida }}</td>
                            <td>{{ $salida->bodega?->nombre }}</td>
                            <td>{{ $salida->tipo_salida }}</td>
                            <td>{{ $salida->actaEntrega?->correlativo ?? 'Manual' }}</td>
                            <td>{{ $salida->fecha_salida?->format('Y-m-d') }}</td>
                            <td>{{ $salida->estado }}</td>
                            <td class="text-right space-x-2">
                                @if ($salida->estado === 'borrador')
                                    @can('inventario.salidas.crear') <button wire:click="edit({{ $salida->id }})" class="cursor-pointer text-blue-600 transition active:translate-y-px">Editar</button> @endcan
                                    @can('inventario.salidas.confirmar') <button wire:click="confirmar({{ $salida->id }})" class="cursor-pointer text-green-700 transition active:translate-y-px">Confirmar</button> @endcan
                                @elseif ($salida->estado === 'confirmado')
                                    @can('inventario.ajustes.crear') <button wire:click="anular({{ $salida->id }})" class="cursor-pointer text-red-700 transition active:translate-y-px">Anular</button> @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-zinc-500">Sin salidas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $salidas->links() }}</div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 overflow-y-auto">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 w-full max-w-5xl my-8">
                <h3 class="font-semibold mb-4">Salida en borrador</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <input wire:model="numero_salida" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Numero de salida">
                    <select wire:model="bodega_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="">Bodega</option>
                        @foreach ($bodegas as $bodega) <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option> @endforeach
                    </select>
                    <input wire:model="fecha_salida" type="date" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                    <select wire:model="tipo_salida" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="manual">Manual</option>
                        <option value="entrega">Entrega</option>
                        <option value="consumo_interno">Consumo interno</option>
                        <option value="ajuste">Ajuste</option>
                        <option value="devolucion_proveedor">Devolucion a proveedor</option>
                        <option value="vencimiento">Vencimiento</option>
                        <option value="dano">Dano</option>
                    </select>
                    <select wire:model="acta_entrega_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="">Acta opcional</option>
                        @foreach ($actas as $acta) <option value="{{ $acta->id }}">{{ $acta->correlativo }}</option> @endforeach
                    </select>
                    <select wire:model="requisicion_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="">Requisicion opcional</option>
                        @foreach ($requisiciones as $requisicion) <option value="{{ $requisicion->id }}">{{ $requisicion->correlativo }}</option> @endforeach
                    </select>
                    <input wire:model="motivo" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Motivo">
                    <select wire:model="departamento_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="">Departamento</option>
                        @foreach ($departamentos as $departamento) <option value="{{ $departamento->id }}">{{ $departamento->nombre }}</option> @endforeach
                    </select>
                    <select wire:model="empleado_recibe_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="">Empleado recibe</option>
                        @foreach ($empleados as $empleado) <option value="{{ $empleado->id }}">{{ $empleado->nombre }} {{ $empleado->apellido }}</option> @endforeach
                    </select>
                    <textarea wire:model="observacion" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700 md:col-span-3" placeholder="Observacion"></textarea>
                </div>
                <div class="mt-5 space-y-3">
                    <div class="flex justify-between items-center">
                        <h4 class="font-medium">Detalle</h4>
                        <button wire:click="addDetalle" class="cursor-pointer px-3 py-1 border rounded transition active:translate-y-px">Agregar producto</button>
                    </div>
                    @foreach ($detalles as $index => $detalle)
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2 border rounded p-3 dark:border-zinc-700">
                            <select wire:model.live="detalles.{{ $index }}.producto_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700 md:col-span-2">
                                <option value="">Producto</option>
                                @foreach ($productos as $producto) <option value="{{ $producto->id }}">{{ $producto->codigo_interno }} - {{ $producto->nombre }}</option> @endforeach
                            </select>
                            <select wire:model="detalles.{{ $index }}.lote_id" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                                <option value="">Lote disponible</option>
                                @foreach ($existencias->where('producto_id', $detalle['producto_id'] ?? null)->where('bodega_id', $bodega_id) as $existencia)
                                    <option value="{{ $existencia->lote_id }}">{{ $existencia->lote?->codigo_lote }} / disp. {{ $existencia->cantidad_disponible }}</option>
                                @endforeach
                            </select>
                            <input wire:model="detalles.{{ $index }}.cantidad" type="number" step="0.01" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Cantidad">
                            <button wire:click="removeDetalle({{ $index }})" class="cursor-pointer text-red-600 text-left transition active:translate-y-px">Quitar</button>
                        </div>
                    @endforeach
                </div>
                @if ($errors->any()) <div class="mt-3 text-sm text-red-600">{{ $errors->first() }}</div> @endif
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeModal" class="cursor-pointer px-4 py-2 border rounded transition active:translate-y-px">Cancelar</button>
                    <button wire:click="save" class="cursor-pointer px-4 py-2 bg-blue-600 text-white rounded transition active:translate-y-px">Guardar borrador</button>
                </div>
            </div>
        </div>
    @endif
</div>
