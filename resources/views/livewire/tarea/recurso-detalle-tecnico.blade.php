<div>
    <div class="mx-auto rounded-lg mt-8 sm:mt-6 lg:mt-4 mb-6">
        <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-4 sm:p-6">

            @if (session()->has('message'))
                @include('rk.default.notifications.notification-alert', [
                    'type' => 'success',
                    'dismissible' => true,
                    'icon' => true,
                    'duration' => 5,
                    'slot' => session('message')
                ])
            @endif

            @if (session()->has('error'))
                @include('rk.default.notifications.notification-alert', [
                    'type' => 'error',
                    'dismissible' => true,
                    'icon' => true,
                    'duration' => 8,
                    'slot' => session('error')
                ])
            @endif

            <!-- Encabezado con navegación -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <a href="{{ route('recursos') }}" class="inline-flex items-center text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 mb-2">
                        <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a Recursos
                    </a>
                    <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">
                        Detalles Técnicos: <span class="text-indigo-600 dark:text-indigo-400">{{ $recursoNombre }}</span>
                    </h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        Recurso ID: {{ $recursoId }} | Gestione los detalles técnicos de este recurso
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row w-full sm:w-auto space-y-3 sm:space-y-0 sm:space-x-2">
                    <div class="relative w-full sm:w-auto">
                        <x-input wire:model.live="search" type="text" placeholder="Buscar detalles..." class="w-full pl-10 pr-4 py-2"/>
                        <div class="absolute left-3 top-2.5">
                            <svg class="h-5 w-5 text-zinc-500 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                    <x-spinner-button wire:click="create()" loadingTarget="create()" :loadingText="__('Abriendo...')">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Nuevo Detalle') }}
                    </x-spinner-button>
                </div>
            </div>

            <!-- Tabla de detalles -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('nombre')">
                                <div class="flex items-center gap-2">
                                    Nombre
                                    @if ($sortField === 'nombre')
                                        @if ($sortDirection === 'asc')
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Creado
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($detalles as $detalle)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $detalle->nombre }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($detalle->estado)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                            Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $detalle->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">
                                    <button wire:click="edit({{ $detalle->id }})"
                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4 inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z" />
                                            <path fill-rule="evenodd" d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $detalle->id }})"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 inline-flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-zinc-500 dark:text-zinc-400">
                                    No se encontraron detalles técnicos
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($detalles->hasPages())
                <div class="mt-4">
                    {{ $detalles->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal para crear/editar -->
    <x-dialog-modal wire:model="showModal" maxWidth="lg">
        <x-slot name="title">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $isEditing ? __('Editar Detalle Técnico') : __('Crear Detalle Técnico') }}
                </h3>
                <button wire:click="closeModal" type="button"
                    class="text-zinc-400 bg-transparent hover:bg-zinc-200 hover:text-zinc-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-zinc-600 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </x-slot>

        <x-slot name="content">
            <form wire:submit="store">
                <div class="mb-4">
                    <x-label for="nombre" value="{{ __('Nombre del Detalle Técnico') }}" />
                    <x-input id="nombre" type="text" class="mt-1 block w-full" wire:model="nombre"
                        placeholder="Ej: Papel Bond tamaño carta base 20" />
                    <x-input-error for="nombre" class="mt-2" />
                </div>

                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="estado" class="rounded dark:bg-zinc-700 border-zinc-300 dark:border-zinc-600" />
                        <span class="ml-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('Estado Activo') }}</span>
                    </label>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeModal" type="button">
                {{ __('Cancelar') }}
            </x-secondary-button>

            <x-button class="ml-3" wire:click="store" wire:loading.attr="disabled">
                {{ $isEditing ? __('Actualizar') : __('Crear') }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Modal de confirmación para eliminar -->
    <x-confirmation-modal wire:model="showDeleteModal" maxWidth="lg">
        <x-slot name="title">
            {{ __('Eliminar Detalle Técnico') }}
        </x-slot>

        <x-slot name="content">
            {{ __('¿Está seguro de que desea eliminar este detalle técnico? Esta acción no se puede deshacer.') }}
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeDeleteModal" type="button">
                {{ __('Cancelar') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="delete">
                {{ __('Eliminar') }}
            </x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Modal de errores -->
    <x-error-modal :show="$showErrorModal" :message="$errorMessage" wire:click="closeErrorModal" />
</div>
