{{-- Modal Crear/Editar con Pasos --}}
<x-dialog-modal wire:model="modalOpen" maxWidth="4xl">
    <x-slot name="title">
        {{ $actividadId ? 'Editar Actividad' : 'Nueva Actividad' }}
    </x-slot>

    <x-slot name="content">
        <form wire:submit.prevent="guardar" id="form-actividad">
                <div class="space-y-6">

                    {{-- Indicador de pasos --}}
                    <div class="mb-6">
                        <div class="flex items-center justify-between">
                            @for($i = 1; $i <= $totalSteps; $i++)
                                <div class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}">
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $currentStep >= $i ? 'bg-indigo-600 dark:bg-indigo-500 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' }} font-semibold">
                                        {{ $i }}
                                    </div>
                                    <div class="ml-2">
                                        <p class="text-sm font-medium {{ $currentStep >= $i ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                            @if($i == 1) Datos de Actividad
                                            @elseif($i == 2) Vinculación PEI
                                            @endif
                                        </p>
                                    </div>
                                    @if($i < $totalSteps)
                                        <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $i ? 'bg-indigo-600 dark:bg-indigo-500' : 'bg-zinc-200 dark:bg-zinc-700' }}"></div>
                                    @endif
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Paso 1: Datos de la Actividad --}}
                    @if($currentStep == 1)
                        <div class="space-y-4">
                            {{-- Mensaje de éxito IA --}}
                            @if(session()->has('ia_success'))
                                <div class="bg-green-100 dark:bg-green-900/30 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg flex items-start">
                                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm">{{ session('ia_success') }}</span>
                                </div>
                            @endif

                            {{-- Mensaje de error IA --}}
                            @if(session()->has('error'))
                                <div class="bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg flex items-start">
                                    <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm">{{ session('error') }}</span>
                                </div>
                            @endif

                            {{-- Nombre --}}
                            <div wire:key="actividad-nombre-field">
                                <label for="actividad_nombre" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Nombre de la Actividad *</label>
                                <textarea id="actividad_nombre" wire:model.defer="nombre" placeholder="Ejemplo: Capacitación docente en metodologías activas de enseñanza..." rows="2" class="block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                @error('nombre') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- Descripción --}}
                            <div wire:key="actividad-descripcion-field">
                                <label for="actividad_descripcion" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Descripción *</label>
                                <textarea id="actividad_descripcion" wire:model.defer="descripcion" placeholder="Describe la actividad a realizar" rows="4" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                @error('descripcion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Tipo de Actividad --}}
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Tipo de Actividad *</label>
                                    <select wire:model="idTipo" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Seleccione un tipo</option>
                                        @foreach($tiposActividad as $tipo)
                                            <option value="{{ $tipo->id }}">{{ $tipo->tipo }}</option>
                                        @endforeach
                                    </select>
                                    @error('idTipo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Categoría --}}
                                <div>
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Categoría *</label>
                                    <select wire:model="idCategoria" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Seleccione una categoría</option>
                                        @foreach($categorias as $categoria)
                                            <option value="{{ $categoria->id }}">{{ $categoria->categoria }}</option>
                                        @endforeach
                                    </select>
                                    @error('idCategoria') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @if(!$actividadId)
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-lg border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-900/20">
                                    <p class="text-sm text-purple-900 dark:text-purple-200">
                                        Completa nombre, tipo y categoría antes de generar con IA para obtener una propuesta más precisa.
                                    </p>
                                    <x-spinner-button 
                                        wire:click="generarConIA" 
                                        type="button"
                                        loadingTarget="generarConIA" 
                                        :loadingText="__('Generando...')"
                                        class="bg-purple-600 hover:bg-purple-700 focus:ring-purple-500 whitespace-nowrap"
                                        :disabled="$generandoConIA"
                                    >
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Generar con IA
                                    </x-spinner-button>
                                </div>
                            @endif

                            {{-- Resultado de la Actividad --}}
                            <div wire:key="actividad-resultado-field">
                                <label for="actividad_resultado" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Resultado Esperado *</label>
                                <textarea id="actividad_resultado" wire:model.defer="resultadoActividad" placeholder="Indica resultados de esta actividad" rows="2" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                @error('resultadoActividad') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- Población Objetivo --}}
                            <div wire:key="actividad-poblacion-field">
                                <label for="actividad_poblacion_objetivo" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Población Objetivo *</label>
                                <textarea id="actividad_poblacion_objetivo" wire:model.defer="poblacion_objetivo" placeholder="Indica la población objetivo" rows="2" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                @error('poblacion_objetivo') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            {{-- Medio de Verificación --}}
                            <div wire:key="actividad-medio-field">
                                <label for="actividad_medio_verificacion" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Medio de Verificación *</label>
                                <textarea id="actividad_medio_verificacion" wire:model.defer="medio_verificacion" placeholder="Indica los medios de verificación" rows="2" class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                @error('medio_verificacion') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    @endif

                    {{-- Paso 2: Vinculación con PEI --}}
                    @if($currentStep == 2)
                        <div class="space-y-4">
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mt-0.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div>
                                        <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-300">Vinculación con PEI</h4>
                                        <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-1">
                                            Vincule esta actividad con una dimensión y resultado del Plan Estratégico Institucional (PEI)
                                        </p>
                                    </div>
                                </div>
                            </div>

                       {{-- Dimensión --}}
                            <div>
                             <x-searchable-select
                                wire:model="idDimension"
                                label="Dimensión"
                                :required="true"
                                placeholder="Buscar dimensión..."
                                defaultText="Seleccione una dimensión"
                                :options="$dimensiones->map(fn($d) => ['id' => $d->id, 'text' => $d->nombre])->toArray()"
                                :error="$errors->first('idDimension')"
                            />
                            </div>
                            {{-- Resultado --}}
                <div>
                            {{-- cambio --}}
                            <x-searchable-select
                                wire:model="idResultado"
                                wire:key="resultado-select-{{ $idDimension ?: 'empty' }}"
                                label="Resultado"
                                :required="true"
                                placeholder="Buscar resultado..."
                                :defaultText="$idDimension ? 'Seleccione un resultado' : 'Primero seleccione una dimension'"
                                :options="$resultadosPorDimension"
                                :disabled="!$idDimension"
                                :error="$errors->first('idResultado')"
                            />
                            {{-- fin cambio hecho ok --}}
                            {{--
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">Resultado *</label>
                            <select wire:model="idResultado" 
                                class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                {{ !$idDimension ? 'disabled' : '' }}>
                                <option value="">{{ $idDimension ? 'Seleccione un resultado' : 'Primero seleccione una dimensión' }}</option>
                                @foreach($resultadosPorDimension as $resultado)
                                    <option value="{{ $resultado['id'] }}">{{ $resultado['text'] }}</option>
                                @endforeach
                            </select>
                            @error('idResultado') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            --}}

                    @if($idDimension && count($resultadosPorDimension) == 0)
                        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                            No hay resultados disponibles para esta dimensión
                        </p>
                    @endif
                </div>

                           {{-- Preview de vinculación --}}
                        @if($idResultado)
                            @php
                                $resultadoSeleccionado = collect($resultadosPorDimension)->firstWhere('id', $idResultado);
                            @endphp
                            @if($resultadoSeleccionado)
                                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                    <h5 class="text-sm font-medium text-green-900 dark:text-green-300 mb-2">Vinculación Seleccionada</h5>
                                    <div class="text-xs space-y-1">
                                        <p>
                                            <span class="font-medium text-green-700 dark:text-green-400">Resultado:</span> 
                                            <span class="text-green-900 dark:text-green-200">{{ $resultadoSeleccionado['text'] }}</span>
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endif

                            {{-- Mensajes --}}
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
                        </div>
                    @endif
                </div>
            </form>
    </x-slot>

    <x-slot name="footer">
        <div class="flex justify-between w-full">
            <div>
                @if($currentStep > 1)
                    <x-secondary-button wire:click="previousStep">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Anterior
                    </x-secondary-button>
                @endif
            </div>

            <div class="flex gap-2">
                <x-secondary-button wire:click="$set('modalOpen', false)">
                    Cancelar
                </x-secondary-button>

                @if($currentStep < $totalSteps)
                    <x-spinner-button wire:click="nextStep" loadingTarget="nextStep" :loadingText="__('Cargando...')">
                        Siguiente
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </x-spinner-button>
                @else
                    <x-spinner-button type="submit" form="form-actividad" class="bg-green-600 hover:bg-green-700 focus:ring-green-500" loadingTarget="submit" :loadingText="__('Guardando...')">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $actividadId ? 'Actualizar' : 'Crear' }} Actividad
                    </x-spinner-button>
                @endif
            </div>
        </div>
    </x-slot>
    




</x-dialog-modal>
