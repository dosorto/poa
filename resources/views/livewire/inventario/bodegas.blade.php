<div class="mx-auto mt-6">
    <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-6">
        @if (session()->has('message')) <div class="mb-4 text-green-700">{{ session('message') }}</div> @endif
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <h2 class="text-xl font-semibold">Bodegas</h2>
            <div class="flex gap-2">
                <input wire:model.live="search" class="border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Buscar bodega">
                @can('inventario.bodegas.crear')
                    <button wire:click="create" class="cursor-pointer bg-blue-600 text-white px-4 py-2 rounded transition active:translate-y-px">Nueva</button>
                @endcan
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="text-left border-b dark:border-zinc-700"><th class="py-2">Nombre</th><th>Ubicacion</th><th>Responsable</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                    @forelse ($bodegas as $bodega)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $bodega->nombre }}</td>
                            <td>{{ $bodega->ubicacion }}</td>
                            <td>{{ $bodega->responsable?->name ?? 'Sin asignar' }}</td>
                            <td>{{ $bodega->activo ? 'Activa' : 'Inactiva' }}</td>
                            <td class="text-right">
                                @can('inventario.bodegas.editar')
                                    <button wire:click="edit({{ $bodega->id }})" class="cursor-pointer text-blue-600 transition active:translate-y-px">Editar</button>
                                    <button wire:click="toggleActivo({{ $bodega->id }})" class="ml-3 cursor-pointer text-zinc-600 transition active:translate-y-px">Cambiar estado</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-zinc-500">Sin bodegas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $bodegas->links() }}</div>
    </div>

    @if ($showModal)
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 w-full max-w-xl">
                <h3 class="font-semibold mb-4">Bodega</h3>
                <div class="space-y-3">
                    <input wire:model="nombre" class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Nombre">
                    @error('nombre') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    <input wire:model="ubicacion" class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700" placeholder="Ubicacion">
                    <select wire:model="responsable_id" class="w-full border rounded px-3 py-2 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="">Responsable</option>
                        @foreach ($usuarios as $usuario) <option value="{{ $usuario->id }}">{{ $usuario->name }}</option> @endforeach
                    </select>
                    <label class="flex items-center gap-2"><input type="checkbox" wire:model="activo"> Activa</label>
                </div>
                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="closeModal" class="cursor-pointer px-4 py-2 border rounded transition active:translate-y-px">Cancelar</button>
                    <button wire:click="save" class="cursor-pointer px-4 py-2 bg-blue-600 text-white rounded transition active:translate-y-px">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
