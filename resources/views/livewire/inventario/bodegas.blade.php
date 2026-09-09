<div class="mx-auto mt-6">
    <div class="overflow-hidden bg-white p-6 shadow sm:rounded-lg dark:bg-zinc-900">
        @if (session()->has('message'))
            <div class="mb-4 text-green-700 dark:text-green-400">{{ session('message') }}</div>
        @endif

        <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-semibold">Bodegas</h2>
            <div class="flex gap-2">
                <x-input wire:model.live="search" class="px-3 py-2" placeholder="Buscar bodega" />
                @can('inventario.bodegas.crear')
                    <x-spinner-button type="button" wire:click="create" loadingTarget="create" :loadingText="__('Abriendo...')">Nueva</x-spinner-button>
                @endcan
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b text-left dark:border-zinc-700">
                        <th class="py-2">Nombre</th>
                        <th>Ubicación</th>
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bodegas as $bodega)
                        <tr class="border-b dark:border-zinc-700">
                            <td class="py-2">{{ $bodega->nombre }}</td>
                            <td>{{ $bodega->ubicacion }}</td>
                            <td>{{ $bodega->responsable?->name ?? 'Sin asignar' }}</td>
                            <td>{{ $bodega->activo ? 'Activa' : 'Inactiva' }}</td>
                            <td class="space-x-3 text-right">
                                @can('inventario.bodegas.editar')
                                    <button type="button" wire:click="edit({{ $bodega->id }})" class="cursor-pointer text-blue-600 transition active:translate-y-px dark:text-blue-400">Editar</button>
                                    <button type="button" wire:click="toggleActivo({{ $bodega->id }})" class="cursor-pointer text-zinc-600 transition active:translate-y-px dark:text-zinc-300">Cambiar estado</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-zinc-500">Sin bodegas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $bodegas->links() }}</div>
    </div>

    <x-dialog-modal wire:model="showModal" max-width="xl">
        <x-slot name="title">Bodega</x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Nombre</span>
                    <x-input wire:model="nombre" class="h-10 w-full px-3" />
                    @error('nombre') <x-input-error for="nombre" class="mt-1" /> @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Ubicación</span>
                    <x-input wire:model="ubicacion" class="h-10 w-full px-3" />
                    @error('ubicacion') <x-input-error for="ubicacion" class="mt-1" /> @enderror
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Responsable</span>
                    <x-select wire:model="responsable_id" class="h-10 px-3">
                        <option value="">Responsable</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                        @endforeach
                    </x-select>
                    @error('responsable_id') <x-input-error for="responsable_id" class="mt-1" /> @enderror
                </label>

                <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    <x-checkbox wire:model="activo" />
                    <span>Activa</span>
                </label>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeModal">Cancelar</x-secondary-button>
            <x-spinner-button wire:click="save" class="ml-2" loadingTarget="save" :loadingText="__('Guardando...')">Guardar</x-spinner-button>
        </x-slot>
    </x-dialog-modal>
</div>
