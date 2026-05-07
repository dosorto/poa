<x-dialog-modal maxWidth="md" wire:model="isModalOpen">
    <x-slot name="title">
        {{ $cubId ? __('Editar CUB') : __('Nuevo CUB') }}
    </x-slot>

    <x-slot name="content">
        <div class="space-y-4">
            <div>
                <x-label for="IDUNSPSC" :value="__('Código UNSPSC')" />
                <x-input id="IDUNSPSC" type="text" class="mt-1 block w-full" wire:model="IDUNSPSC" placeholder="Ingrese el código UNSPSC" />
                <x-input-error for="IDUNSPSC" class="mt-2" />
            </div>

            <div>
                <x-label for="descripcion_esp" :value="__('Descripción en español')" />
                <x-textarea id="descripcion_esp" class="mt-1 block w-full" wire:model="descripcion_esp" placeholder="Ingrese la descripción en español" rows="4" />
                <x-input-error for="descripcion_esp" class="mt-2" />
            </div>

            <div>
                <x-label for="descripcion_regional" :value="__('Descripción regional')" />
                <x-textarea id="descripcion_regional" class="mt-1 block w-full" wire:model="descripcion_regional" placeholder="Ingrese la descripción regional" rows="4" />
                <x-input-error for="descripcion_regional" class="mt-2" />
            </div>

            <div>
                <x-label for="idUE" :value="__('Unidad Ejecutora (opcional)')" />
                <x-select id="idUE" wire:model="idUE" :options="$unidadesEjecutoras->map(fn($ue) => ['value' => $ue->id, 'text' => $ue->name])->toArray()" placeholder="Seleccione una unidad ejecutora" class="mt-1 w-full" />
                <x-input-error for="idUE" class="mt-2" />
            </div>
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="flex justify-end space-x-2">
            <x-spinner-secondary-button 
                wire:click="closeModal"
                type="button"
                loadingTarget="closeModal"
                loadingText="Cerrando..."
            >
                {{ __('Cancelar') }}
            </x-spinner-secondary-button>

            <x-spinner-button 
                type="button"
                wire:click="store"
                loadingTarget="store"
                :loadingText="$cubId ? __('Actualizando...') : __('Guardando...')"
            >
                {{ $cubId ? __('Actualizar') : __('Guardar') }}
            </x-spinner-button>
        </div>
    </x-slot>
</x-dialog-modal>
