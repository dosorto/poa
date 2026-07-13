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

        <!-- Resumen de revisión y presupuesto -->
        <div class="mb-6">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4">
                <h2 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-2">
                    REVISIÓN PARA {{ $resumen['nombreDepartamento'] ?? '-' }}
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                            L. {{ number_format($resumen['presupuesto'] ?? 0, 2) }}
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Presupuesto</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-green-600">
                            L. {{ number_format($resumen['planificado'] ?? 0, 2) }}
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Planificado</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-blue-600">
                            {{ $resumen['numActividades'] ?? 0 }}
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">Actividades</div>
                    </div>
                    <div class="text-center">
                        <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                            {{ $resumen['porcentaje'] ?? 0 }}%
                        </div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">% Planificado</div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                    @foreach(($resumen['fuentes'] ?? []) as $fuente)
                        <div class="rounded-lg bg-white/80 dark:bg-zinc-800/60 border border-indigo-100 dark:border-indigo-900/30 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Fuente {{ $fuente['identificador'] }}</p>
                                    <p class="mt-1 text-lg font-bold text-zinc-900 dark:text-zinc-100">L. {{ number_format($fuente['monto'] ?? 0, 2) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Actividades</p>
                                    <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-300">{{ $fuente['actividades'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <br>
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
            <h2 class="text-xl font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Actividades en Revisión del Departamento') }}</h2>

            <div class="flex flex-col sm:flex-row w-full sm:w-auto space-y-3 sm:space-y-0 sm:space-x-2">
                <!-- Buscador por nombre de actividad o tarea -->
                <div class="relative w-full sm:w-auto">
                    <x-input wire:model.live="buscarActividad" type="text" placeholder="Buscar actividad..." class="w-full pl-10 pr-4 py-2"/>
                    
                    <div class="absolute left-3 top-2.5">
                        <svg class="h-5 w-5 text-zinc-500 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Filtro por año -->
                <div class="w-full sm:w-auto min-w-[150px] max-w-xs">
                    <select wire:model.live="poaYear" class="block w-full min-w-[180px] max-w-xs rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 text-sm py-2 px-3">
                        @foreach($poaYears as $year)
                            <option value="{{ $year }}">POA {{ $year }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>
        <x-table
            :columns="[
                ['key' => 'nombre', 'label' => 'Nombre'],
                ['key' => 'tipo', 'label' => 'Tipo de Actividad'],
                ['key' => 'categoria', 'label' => 'Categoría'],
                ['key' => 'estado', 'label' => 'Estado'],
                ['key' => 'actions', 'label' => 'Acciones'],
            ]"
            empty-message="No hay actividades en revisión para este departamento."
            class="mt-6"
        >
            <x-slot name="desktop">
                @forelse($actividades as $actividad)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                        <td class="px-6 py-4 text-zinc-900 dark:text-zinc-100 align-top">
                            <div class="max-w-[18rem] xl:max-w-[24rem] whitespace-normal break-words leading-6">
                                {{ $actividad->nombre }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-zinc-100">{{ $actividad->tipo->tipo ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-zinc-900 dark:text-zinc-100">{{ $actividad->categoria->categoria ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @switch($actividad->estado)
                                @case('APROBADO')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @case('RECHAZADO')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @case('REVISION')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @case('REFORMULACION')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @default
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-zinc-100 dark:bg-zinc-900/30 text-zinc-800 dark:text-zinc-300">
                                        {{ $actividad->estado }}
                                    </span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('review-actividad-detalle', $actividad->id) }}" class="inline-flex items-center px-3 py-1 bg-indigo-600 dark:bg-indigo-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Ver Detalles
                                </a>

                                @if($actividad->estado !== 'REFORMULACION')
                                    <button
                                        type="button"
                                        onclick="document.getElementById('revision-reformulacion-form').action='{{ route('revisiones.actividades.reformulacion', $actividad->id) }}'; document.getElementById('revision-reformulacion-actividad').textContent={{ \Illuminate\Support\Js::from($actividad->nombre) }}; document.getElementById('revision-reformulacion-modal').classList.remove('hidden');"
                                        class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Regresar a Reformulación
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-zinc-500 dark:text-zinc-400">
                            No hay actividades en revisión para este departamento.
                        </td>
                    </tr>
                @endforelse
            </x-slot>
            
            <x-slot name="mobile">
                @forelse($actividades as $actividad)
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow-sm border border-zinc-200 dark:border-zinc-700 mb-4">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-300 px-2 py-1 rounded-full text-xs">
                                    {{ $actividad->nombre }}
                                </span>
                            </div>
                            <a href="{{ route('review-actividad-detalle', $actividad->id) }}"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Ver Detalle
                            </a>
                        </div>
                        <div class="text-zinc-600 dark:text-zinc-400 text-sm mb-1">
                            <span class="font-semibold">Tipo:</span> {{ $actividad->tipo->tipo ?? '-' }}<br>
                            <span class="font-semibold">Categoría:</span> {{ $actividad->categoria->categoria ?? '-' }}<br>
                            <span class="font-semibold">Estado:</span> 
                            @switch($actividad->estado)
                                @case('APROBADO')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @case('RECHAZADO')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @case('REVISION')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @case('REFORMULACION')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">
                                        {{ $actividad->estado }}
                                    </span>
                                    @break
                                @default
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-zinc-100 dark:bg-zinc-900/30 text-zinc-800 dark:text-zinc-300">
                                        {{ $actividad->estado }}
                                    </span>
                            @endswitch
                        </div>
                        @if($actividad->estado !== 'REFORMULACION')
                            <div class="mt-3">
                                <button
                                    type="button"
                                    onclick="document.getElementById('revision-reformulacion-form').action='{{ route('revisiones.actividades.reformulacion', $actividad->id) }}'; document.getElementById('revision-reformulacion-actividad').textContent={{ \Illuminate\Support\Js::from($actividad->nombre) }}; document.getElementById('revision-reformulacion-modal').classList.remove('hidden');"
                                    class="inline-flex w-full justify-center items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Regresar a Reformulación
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white dark:bg-zinc-800 p-4 rounded-lg shadow text-center text-zinc-500 dark:text-zinc-400">
                        No hay actividades en revisión para este departamento.
                    </div>
                @endforelse
            </x-slot>
        </x-table>
        <div
            id="revision-reformulacion-modal"
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 hidden"
            onkeydown="if(event.key === 'Escape'){ this.classList.add('hidden'); }"
        >
            <div class="fixed inset-0 bg-zinc-500/75 dark:bg-zinc-900/80" onclick="document.getElementById('revision-reformulacion-modal').classList.add('hidden')"></div>

            <div class="relative mx-auto mt-16 max-w-md">
                <div class="overflow-hidden rounded-lg bg-white shadow-xl dark:bg-zinc-800">
                    <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="rounded-full bg-yellow-100 p-2 dark:bg-yellow-900/30">
                                <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>

                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-medium text-zinc-900 dark:text-zinc-100">
                                    Confirmar regreso a reformulación
                                </h3>

                                <div class="mt-4 space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                                    <p>
                                        La actividad seleccionada volverá al estado <span class="font-semibold text-orange-700 dark:text-orange-300">REFORMULACION</span>.
                                        Después de esto, los planificadores podrán editarla y volver a enviarla para revisión.
                                    </p>

                                    <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800/50">
                                        <p class="font-medium text-zinc-800 dark:text-zinc-100">Actividad</p>
                                        <p class="mt-1" id="revision-reformulacion-actividad"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 bg-zinc-100 px-6 py-4 dark:bg-zinc-700">
                        <button
                            type="button"
                            onclick="document.getElementById('revision-reformulacion-modal').classList.add('hidden')"
                            class="inline-flex items-center px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-md font-semibold text-xs text-zinc-700 dark:text-zinc-300 uppercase tracking-widest shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Cancelar
                        </button>

                        <form id="revision-reformulacion-form" method="POST" onsubmit="var btn=this.querySelector('[type=submit]'); var text=btn.querySelector('.revision-submit-text'); var loading=btn.querySelector('.revision-submit-loading'); btn.disabled=true; text.style.display='none'; loading.style.display='inline-flex';">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <span class="revision-submit-text">Sí, enviar a reformulación</span>
                                <span class="revision-submit-loading items-center gap-2" style="display: none;">
                                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                    </svg>
                                    Procesando...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
