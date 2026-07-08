<div>
    <div class="mx-auto overflow-hidden rounded-lg bg-white px-4 p-4 shadow dark:bg-zinc-900 sm:rounded-lg sm:p-6">
        <div class="mb-3">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">
                        Consolidado de Actividades
                    </h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Vista consolidada de actividades de todos los departamentos asignados al planificador.
                    </p>
                </div>

                <button
                    wire:click="exportarExcel"
                    wire:loading.attr="disabled"
                    wire:target="exportarExcel"
                    type="button"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="exportarExcel">Descargar Excel</span>
                    <span wire:loading wire:target="exportarExcel">Generando archivo...</span>
                </button>
            </div>
        </div>

        <div class="mb-6 bg-white dark:bg-zinc-900">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label for="anio" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Año
                    </label>
                    <select
                        wire:model.live="anio"
                        id="anio"
                        class="block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-700 dark:text-zinc-200"
                    >
                        @foreach($anios as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-searchable-select
                        wire:model="dimensionId"
                        label="Dimensión"
                        placeholder="Buscar dimensión..."
                        defaultText="Todas las dimensiones"
                        clearText="Todas las dimensiones"
                        :options="$dimensiones->map(fn($dimension) => ['id' => $dimension->id, 'text' => $dimension->nombre])->toArray()"
                    />
                </div>

                <div>
                    <x-searchable-select
                        wire:model="departamentoId"
                        label="Departamento"
                        placeholder="Buscar departamento..."
                        defaultText="Todos los departamentos"
                        clearText="Todos los departamentos"
                        :options="collect($departamentos)->map(fn($departamento) => ['id' => $departamento['id'], 'text' => $departamento['name']])->toArray()"
                    />
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">Detalle</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">Correlativo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">Actividad</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-300">Departamento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse($actividades as $actividad)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/70">
                            <td class="px-4 py-3">
                                <button
                                    wire:click="toggleExpand({{ $actividad->id }})"
                                    class="text-indigo-600 transition-transform dark:text-indigo-400 {{ $expandedRow === $actividad->id ? 'rotate-180' : '' }}"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                    {{ $actividad->correlativo }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $actividad->nombre }}</div>
                                @if($actividad->categoria)
                                    <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $actividad->categoria->categoria }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-zinc-100">
                                {{ $actividad->departamento->name ?? 'N/A' }}
                            </td>
                        </tr>

                        @if($expandedRow === $actividad->id && $actividadDetalle)
                            <tr>
                                <td colspan="4" class="bg-zinc-50 px-4 py-4 dark:bg-zinc-800/50">
                                    <div class="space-y-4">
                                        <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-zinc-900">
                                            <h3 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Datos Institucionales</h3>
                                            <div class="space-y-2 text-sm">
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Dimensión:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->area->objetivo->dimension->nombre ?? 'N/A' }}</span></p>
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Objetivo:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->area->objetivo->nombre ?? 'N/A' }}</span></p>
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Área:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->area->nombre ?? 'N/A' }}</span></p>
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Resultado institucional:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->nombre ?? 'N/A' }}</span></p>
                                            </div>
                                        </div>

                                        <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-zinc-900">
                                            <h3 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Datos Generales</h3>
                                            <div class="space-y-2 text-sm">
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Resultado de actividad:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultadoActividad ?? 'N/A' }}</span></p>
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Población objetivo:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->poblacion_objetivo ?? 'N/A' }}</span></p>
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Medio de verificación:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->medio_verificacion ?? 'N/A' }}</span></p>
                                                <p><span class="font-medium text-zinc-700 dark:text-zinc-300">Tipo:</span> <span class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->tipo->tipo ?? 'N/A' }}</span></p>
                                            </div>
                                        </div>

                                        @if($actividadDetalle->indicadores->count() > 0)
                                            <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-zinc-900">
                                                <h3 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Indicadores</h3>
                                                <div class="space-y-3">
                                                    @foreach($actividadDetalle->indicadores as $indicador)
                                                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $indicador->nombre }}</p>
                                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                                Meta: {{ $indicador->cantidadPlanificada ?? '0' }} | Ejecutada: {{ $indicador->cantidadEjecutada ?? '0' }}
                                                            </p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if($actividadDetalle->tareas->count() > 0)
                                            <div class="rounded-lg bg-white p-4 shadow-sm dark:bg-zinc-900">
                                                <h3 class="mb-3 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Tareas y Recursos</h3>
                                                <div class="space-y-3">
                                                    @foreach($actividadDetalle->tareas as $tarea)
                                                        <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $tarea->nombre }}</p>
                                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $tarea->correlativo }}</span>
                                                            </div>
                                                            @if($tarea->descripcion)
                                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $tarea->descripcion }}</p>
                                                            @endif
                                                            @if($tarea->presupuestos->count() > 0)
                                                                <div class="mt-3 overflow-x-auto">
                                                                    <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
                                                                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                                                                            <tr>
                                                                                <th class="px-2 py-2 text-left">Recurso</th>
                                                                                <th class="px-2 py-2 text-left">Cantidad</th>
                                                                                <th class="px-2 py-2 text-left">Total</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                                                            @foreach($tarea->presupuestos as $presupuesto)
                                                                                <tr>
                                                                                    <td class="px-2 py-2 text-zinc-700 dark:text-zinc-300">{{ $presupuesto->recurso ?? 'N/A' }}</td>
                                                                                    <td class="px-2 py-2 text-zinc-700 dark:text-zinc-300">{{ number_format($presupuesto->cantidad ?? 0, 2) }}</td>
                                                                                    <td class="px-2 py-2 font-medium text-zinc-900 dark:text-zinc-100">L {{ number_format($presupuesto->total ?? 0, 2) }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center">
                                <p class="text-lg font-medium text-zinc-500 dark:text-zinc-400">No se encontraron actividades</p>
                                <p class="mt-2 text-sm text-zinc-400 dark:text-zinc-500">Intenta cambiar los filtros de búsqueda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $actividades->links() }}
        </div>
    </div>
</div>
