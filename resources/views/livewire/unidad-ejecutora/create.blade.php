 <!-- Modal de Crear/Editar -->
    <x-dialog-modal wire:model="isModalOpen" max-width="2xl">
        <x-slot name="title">
            {{ $unidadEjecutoraId ? __('Editar Unidad Ejecutora') : __('Nueva Unidad Ejecutora') }}
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <x-label for="name" value="{{ __('Nombre') }}" />
                    <x-input id="name" type="text" class="mt-1 block w-full" wire:model="name" autocomplete="name" />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div>
                    <x-label for="descripcion" value="{{ __('Descripción') }}" />
                    <x-textarea id="descripcion" class="mt-1 block w-full" wire:model="descripcion" rows="3" />
                    <x-input-error for="descripcion" class="mt-2" />
                </div>

                <div>
                    <x-label for="estructura" value="{{ __('Estructura') }}" />
                    <x-input id="estructura" type="text" class="mt-1 block w-full" wire:model="estructura" 
                        placeholder="Ej: 0-00-00-00" />
                    <x-input-error for="estructura" class="mt-2" />
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Código de estructura organizacional (formato: 0-00-00-00)
                    </p>
                </div>

                <div>
                    <x-label for="idInstitucion" value="{{ __('Institución') }}" />
                    <select 
                        id="idInstitucion" 
                        wire:model="idInstitucion"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="">Selecciona una institución</option>
                        @foreach($instituciones as $institucion)
                            <option value="{{ $institucion->id }}">{{ $institucion->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="idInstitucion" class="mt-2" />
                </div>

                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                        {{ __('Roles (Usuarios)') }}
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Asignación opcional de usuarios por rol dentro de la unidad ejecutora.') }}
                    </p>
                </div>

                <div>
                    <x-label for="idAsistenteEstrategico" value="{{ __('Asistente Estratégico') }}" />
                    <select
                        id="idAsistenteEstrategico"
                        wire:model="idAsistenteEstrategico"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="">{{ __('-- Seleccionar --') }}</option>
                        @foreach($users as $user)
                            @php($nombreEmpleado = preg_replace('/\s+/', ' ', trim($user->empleado->nombre . ' ' . $user->empleado->apellido)))
                            <option value="{{ $user->id }}">{{ $nombreEmpleado }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="idAsistenteEstrategico" class="mt-2" />
                </div>

                <div>
                    <x-label for="idAdministrador" value="{{ __('Administrador') }}" />
                    <select
                        id="idAdministrador"
                        wire:model="idAdministrador"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="">{{ __('-- Seleccionar --') }}</option>
                        @foreach($users as $user)
                            @php($nombreEmpleado = preg_replace('/\s+/', ' ', trim($user->empleado->nombre . ' ' . $user->empleado->apellido)))
                            <option value="{{ $user->id }}">{{ $nombreEmpleado }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="idAdministrador" class="mt-2" />
                </div>

                <div>
                    <x-label for="idEncargadoCompra" value="{{ __('Encargado de Compra') }}" />
                    <select
                        id="idEncargadoCompra"
                        wire:model="idEncargadoCompra"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="">{{ __('-- Seleccionar --') }}</option>
                        @foreach($users as $user)
                            @php($nombreEmpleado = preg_replace('/\s+/', ' ', trim($user->empleado->nombre . ' ' . $user->empleado->apellido)))
                            <option value="{{ $user->id }}">{{ $nombreEmpleado }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="idEncargadoCompra" class="mt-2" />
                </div>

                <div>
                    <x-label for="idDirectorDecano" value="{{ __('Director / Decano') }}" />
                    <select
                        id="idDirectorDecano"
                        wire:model="idDirectorDecano"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    >
                        <option value="">{{ __('-- Seleccionar --') }}</option>
                        @foreach($users as $user)
                            @php($nombreEmpleado = preg_replace('/\s+/', ' ', trim($user->empleado->nombre . ' ' . $user->empleado->apellido)))
                            <option value="{{ $user->id }}">{{ $nombreEmpleado }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="idDirectorDecano" class="mt-2" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="closeModal">
                {{ __('Cancelar') }}
            </x-secondary-button>

            <x-spinner-button wire:click="store" loadingTarget="store" :loadingText="__('Guardando...')" class="ml-3">
                {{ $unidadEjecutoraId ? __('Actualizar') : __('Crear') }}
            </x-spinner-button>
        </x-slot>
    </x-dialog-modal>
