<x-dialog-modal wire:model="showImportModal" maxWidth="lg">
    <x-slot name="title">
        {{ __('Importar Recursos desde CSV') }}
    </x-slot>

    <x-slot name="content">
        <div class="space-y-4">
            <div>
                <x-label for="csvFile" :value="__('Archivo CSV')" />
                <input
                    id="csvFile"
                    type="file"
                    wire:model="csvFile"
                    accept=".csv,text/csv"
                    class="mt-1 block w-full text-sm text-zinc-700 dark:text-zinc-200 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-100"
                />
                <x-input-error for="csvFile" class="mt-2" />
            </div>

            <div class="rounded-md bg-zinc-50 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                <p class="font-medium">{{ __('Formato requerido') }}</p>
                <p class="mt-1 font-mono text-xs">nombre,idobjeto,idunidad,idProcesoCompra,idCubs</p>
            </div>

            @if (! empty($importErrors))
                <div class="max-h-48 overflow-y-auto rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <p class="mb-2 font-medium">{{ __('Filas omitidas') }}</p>
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </x-slot>

    <x-slot name="footer">
        <x-spinner-secondary-button wire:click="closeImportModal" type="button" loadingTarget="closeImportModal" loadingText="Cerrando...">
            {{ __('Cancelar') }}
        </x-spinner-secondary-button>

        <x-spinner-button class="ml-3" type="button" wire:click="importCsv" loadingTarget="importCsv" loadingText="Importando...">
            {{ __('Importar') }}
        </x-spinner-button>
    </x-slot>
</x-dialog-modal>
