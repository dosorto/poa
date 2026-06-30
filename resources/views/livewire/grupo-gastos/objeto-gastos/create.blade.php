<x-dialog-modal maxWidth="md" wire:model="isModalOpen">
    <x-slot name="title">
        {{ $objetoGastoId ? __('Editar Objeto de Gasto') : __('Nuevo Objeto de Gasto') }}
    </x-slot>

    <x-slot name="content">
        <div class="space-y-4">
            <div>
                <x-label for="identificador" :value="__('Identificador')" />
                <x-input id="identificador" type="text" class="mt-1 block w-full" wire:model="identificador" placeholder="Ingrese el identificador del objeto de gasto" />
                <x-input-error for="identificador" class="mt-2" />
            </div>

            <div>
                <x-label for="nombre" :value="__('Nombre')" />
                <x-input id="nombre" type="text" class="mt-1 block w-full" wire:model="nombre" placeholder="Ingrese el nombre del objeto de gasto" />
                <x-input-error for="nombre" class="mt-2" />
            </div>

            <div>
                <x-label for="idgrupo" :value="__('Grupo de gasto')" />
                <x-select
                    id="idgrupo"
                    wire:model="idgrupo"
                    :options="$gruposGasto"
                    placeholder="Seleccione un grupo de gasto"
                    class="mt-1 block w-full"
                />
                <x-input-error for="idgrupo" class="mt-2" />
            </div>

            <div>
                <x-label for="descripcion" :value="__('Descripción')" />
                <x-textarea id="descripcion" class="mt-1 block w-full" wire:model="descripcion" rows="4" placeholder="Ingrese la descripción del objeto de gasto" />
                <x-input-error for="descripcion" class="mt-2" />
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="flex justify-end space-x-2">
            <x-spinner-secondary-button
                wire:click="closeModal"
                type="button"
                loadingTarget="closeModal"
                loadingText="Cerrando...">
                {{ __('Cancelar') }}
            </x-spinner-secondary-button>

            <x-spinner-button
                type="submit"
                wire:click="store"
                loadingTarget="store"
                :loadingText="$objetoGastoId ? 'Actualizando...' : 'Creando...'">
                {{ $objetoGastoId ? __('Actualizar') : __('Crear') }}
            </x-spinner-button>
        </div>
    </x-slot>
</x-dialog-modal>
