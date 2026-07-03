@php
    $badgeClasses = [
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:ring-blue-800',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-900/20 dark:text-violet-300 dark:ring-violet-800',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-800',
        'rose' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-900/20 dark:text-rose-300 dark:ring-rose-800',
        'zinc' => 'bg-zinc-50 text-zinc-700 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700',
    ];

    $barClasses = [
        'blue' => 'bg-blue-500',
        'amber' => 'bg-amber-500',
        'violet' => 'bg-violet-500',
        'emerald' => 'bg-emerald-500',
        'rose' => 'bg-rose-500',
        'zinc' => 'bg-zinc-500',
    ];

    $totalActividadesEstado = max(array_sum(array_column($actividadesPorEstado, 'total')), 1);
    $totalRequisicionesEstado = max(array_sum(array_column($requisicionesPorEstado, 'total')), 1);
@endphp

<div class="mx-auto mt-4 mb-8 space-y-6">
    <div class="overflow-hidden rounded-lg bg-zinc-950 text-white shadow">
        <div class="grid gap-6 p-6 lg:grid-cols-[1fr_360px]">
            <div class="flex flex-col justify-between gap-8">
                <div>
                    <div class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/80 ring-1 ring-white/15">
                        Dirección
                    </div>
                    <h1 class="mt-4 text-2xl font-bold sm:text-3xl">
                        Reportes ejecutivos
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-300">
                        Vista consolidada de planificación, requisiciones, seguimiento y ejecución presupuestaria para toma de decisiones.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-white/10 p-4 ring-1 ring-white/15">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-300">Avance planificación</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $resumen['avancePlanificacion'] ?? 0 }}%</p>
                    </div>
                    <div class="rounded-lg bg-white/10 p-4 ring-1 ring-white/15">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-300">Entrega recursos</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $resumen['entrega'] ?? 0 }}%</p>
                    </div>
                    <div class="rounded-lg bg-white/10 p-4 ring-1 ring-white/15">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-300">Ejecución</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $resumen['ejecucion'] ?? 0 }}%</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-4 text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-100">
                <div class="grid gap-4">
                    <div>
                        <label for="anio" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">POA</label>
                        <select id="anio" wire:model.live="anioSeleccionado" class="block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 sm:text-sm">
                            @foreach($poas as $poa)
                                <option value="{{ $poa['anio'] }}">
                                    {{ $poa['anio'] }}{{ $poa['activo'] ? ' - Activo' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="departamento" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Departamento</label>
                        <select id="departamento" wire:model.live="departamentoSeleccionado" class="block w-full rounded-md border-zinc-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 sm:text-sm">
                            <option value="">Todos los departamentos</option>
                            @foreach($departamentos as $departamento)
                                <option value="{{ $departamento['id'] }}">
                                    {{ $departamento['siglas'] ? $departamento['siglas'] . ' - ' : '' }}{{ $departamento['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-5 rounded-md bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    @if($poaActual)
                        Mostrando información del POA {{ $poaActual->anio }}.
                    @else
                        No hay POA disponible para el filtro seleccionado.
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(!$poaActual)
        <div class="rounded-lg bg-white p-10 text-center shadow dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Sin información disponible</h2>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Seleccione otro año o verifique que exista un POA registrado.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-white p-5 shadow dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Actividades</p>
                <div class="mt-3 flex items-end justify-between gap-4">
                    <p class="text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($resumen['actividades'] ?? 0) }}</p>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-800">
                        {{ number_format($resumen['actividadesAprobadas'] ?? 0) }} aprobadas
                    </span>
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Requisiciones</p>
                <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($resumen['requisiciones'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Presentadas, en revisión, compra, recibidas y demás estados.</p>
            </div>

            <div class="rounded-lg bg-white p-5 shadow dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Recursos entregados</p>
                <p class="mt-3 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($seguimiento['entregados'] ?? 0) }}</p>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ number_format($seguimiento['pendientes'] ?? 0) }} pendientes de entrega.</p>
            </div>

            <div class="rounded-lg bg-white p-5 shadow dark:bg-zinc-900">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Ejecutado</p>
                <p class="mt-3 text-2xl font-bold text-zinc-900 dark:text-zinc-100">L {{ number_format($finanzas['ejecutado'] ?? 0, 2) }}</p>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">De L {{ number_format($finanzas['planificado'] ?? 0, 2) }} planificados.</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1fr_420px]">
            <div class="rounded-lg bg-white p-6 shadow dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Planificación por estado</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Formulación, revisión, rechazos y aprobaciones.</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @foreach($actividadesPorEstado as $estado)
                        @php $width = round(($estado['total'] / $totalActividadesEstado) * 100, 1); @endphp
                        <button
                            type="button"
                            wire:click="seleccionarEstadoActividad(@js($estado['estado']))"
                            class="block w-full rounded-md p-2 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $estadoActividadSeleccionado === $estado['estado'] ? 'bg-zinc-50 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700' : '' }}"
                        >
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $estado['label'] }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ number_format($estado['total']) }}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full {{ $barClasses[$estado['color']] ?? $barClasses['zinc'] }}" style="width: {{ $width }}%"></div>
                            </div>
                        </button>
                    @endforeach
                </div>

            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Presupuesto</h2>
                <div class="mt-5 space-y-5">
                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Planificado</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $finanzas['porcentajePlanificado'] ?? 0 }}%</span>
                        </div>
                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full bg-blue-500" style="width: {{ min($finanzas['porcentajePlanificado'] ?? 0, 100) }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">Ejecutado</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $finanzas['porcentajeEjecutado'] ?? 0 }}%</span>
                        </div>
                        <div class="mt-2 h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full bg-emerald-500" style="width: {{ min($finanzas['porcentajeEjecutado'] ?? 0, 100) }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-md bg-zinc-50 p-3 dark:bg-zinc-800">
                            <p class="text-zinc-500 dark:text-zinc-400">Asignado</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">L {{ number_format($finanzas['asignado'] ?? 0, 2) }}</p>
                        </div>
                        <div class="rounded-md bg-zinc-50 p-3 dark:bg-zinc-800">
                            <p class="text-zinc-500 dark:text-zinc-400">Disponible</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">L {{ number_format($finanzas['disponible'] ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-lg bg-white p-6 shadow dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Requisiciones por estado</h2>
                <div class="mt-5 space-y-4">
                    @forelse($requisicionesPorEstado as $estado)
                        @php $width = round(($estado['total'] / $totalRequisicionesEstado) * 100, 1); @endphp
                        <button
                            type="button"
                            wire:click="seleccionarEstadoRequisicion(@js($estado['estado']))"
                            class="block w-full rounded-md p-2 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $estadoRequisicionSeleccionado === $estado['estado'] ? 'bg-zinc-50 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700' : '' }}"
                        >
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $estado['estado'] }}</span>
                                <span class="text-zinc-500 dark:text-zinc-400">{{ number_format($estado['total']) }}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full {{ $barClasses[$estado['color']] ?? $barClasses['zinc'] }}" style="width: {{ $width }}%"></div>
                            </div>
                        </button>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No hay requisiciones para este filtro.</p>
                    @endforelse
                </div>

            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Seguimiento de entrega</h2>
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-md bg-emerald-50 p-4 ring-1 ring-emerald-100 dark:bg-emerald-900/20 dark:ring-emerald-800">
                        <p class="text-sm text-emerald-700 dark:text-emerald-300">Entregados</p>
                        <p class="mt-2 text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ number_format($seguimiento['entregados'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-md bg-amber-50 p-4 ring-1 ring-amber-100 dark:bg-amber-900/20 dark:ring-amber-800">
                        <p class="text-sm text-amber-700 dark:text-amber-300">Pendientes</p>
                        <p class="mt-2 text-2xl font-bold text-amber-900 dark:text-amber-100">{{ number_format($seguimiento['pendientes'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-md bg-blue-50 p-4 ring-1 ring-blue-100 dark:bg-blue-900/20 dark:ring-blue-800">
                        <p class="text-sm text-blue-700 dark:text-blue-300">Detalles</p>
                        <p class="mt-2 text-2xl font-bold text-blue-900 dark:text-blue-100">{{ number_format($seguimiento['detalles'] ?? 0) }}</p>
                    </div>
                    <div class="rounded-md bg-violet-50 p-4 ring-1 ring-violet-100 dark:bg-violet-900/20 dark:ring-violet-800">
                        <p class="text-sm text-violet-700 dark:text-violet-300">Actas</p>
                        <p class="mt-2 text-2xl font-bold text-violet-900 dark:text-violet-100">{{ number_format($seguimiento['actas'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-lg bg-white p-6 shadow dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Requisiciones con más tiempo sin movimiento</h2>
            <div class="mt-5 overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Correlativo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Departamento</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Solicitante</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Días</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                            @forelse($alertasRequisiciones as $alerta)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $alerta['correlativo'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium ring-1 {{ $badgeClasses[$alerta['estadoColor']] ?? $badgeClasses['zinc'] }}">{{ $alerta['estado'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $alerta['departamento'] }}</td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $alerta['solicitante'] }}</td>
                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $alerta['dias'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">No hay requisiciones para este filtro.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($estadoActividadSeleccionado)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <button type="button" wire:click="cerrarDetalleActividad" class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm"></button>

                <div class="relative w-full max-w-7xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                        <div>
                            <h3 id="modal-title" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                Actividades en {{ collect($actividadesPorEstado)->firstWhere('estado', $estadoActividadSeleccionado)['label'] ?? $estadoActividadSeleccionado }}
                            </h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $totalActividadesDetalle }} registros encontrados para el filtro actual.
                            </p>
                        </div>
                        <button type="button" wire:click="cerrarDetalleActividad" class="rounded-md p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[76vh] overflow-y-auto p-6">
                        <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Actividad</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Depto</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Responsable</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Tareas</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Actualizado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                                        @forelse($detalleActividadesEstado as $actividad)
                                            <tr
                                                wire:click="alternarRecursosActividad({{ $actividad['id'] }})"
                                                class="cursor-pointer transition hover:bg-zinc-50 dark:hover:bg-zinc-800/70"
                                            >
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="h-4 w-4 shrink-0 text-zinc-400 transition {{ $actividadRecursosSeleccionada === $actividad['id'] ? 'rotate-90' : '' }}" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        <p class="max-w-xl truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $actividad['nombre'] }}</p>
                                                    </div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $actividad['correlativo'] }}</p>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $actividad['departamento'] }}</td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $actividad['responsable'] }}</td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $actividad['tareas'] }}</td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">
                                                    {{ $actividad['actualizado'] ?? '-' }}
                                                    <span class="block text-xs text-zinc-400">{{ $actividad['dias'] }} días</span>
                                                </td>
                                            </tr>
                                            @if($actividadRecursosSeleccionada === $actividad['id'])
                                                <tr>
                                                    <td colspan="5" class="bg-zinc-50 px-4 py-4 dark:bg-zinc-950/40">
                                                        <div class="rounded-md border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                                                            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                                                                <div>
                                                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Recursos de la actividad</h4>
                                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($actividad['recursos']) }} recursos/presupuestos asociados</p>
                                                                </div>
                                                            </div>

                                                            @if(count($actividad['recursos']) > 0)
                                                                <div class="overflow-x-auto">
                                                                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                                                                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                                                                            <tr>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Recurso</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Tarea</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Cantidad</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Costo</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Total</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Objeto</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                                                            @foreach($actividad['recursos'] as $recurso)
                                                                                <tr>
                                                                                    <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $recurso['recurso'] }}</td>
                                                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $recurso['tarea'] }}</td>
                                                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ number_format($recurso['cantidad'], 2) }} {{ $recurso['unidad'] }}</td>
                                                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">L {{ number_format($recurso['costo'], 2) }}</td>
                                                                                    <td class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">L {{ number_format($recurso['total'], 2) }}</td>
                                                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $recurso['objeto'] }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                                                    Esta actividad no tiene recursos presupuestados.
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No hay actividades en este estado.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex flex-col gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                    Página {{ $paginaActividadesDetalle }} de {{ $this->totalPaginasActividadesDetalle() }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="paginaAnteriorActividades"
                                        @disabled($paginaActividadesDetalle <= 1)
                                        class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        Anterior
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="paginaSiguienteActividades"
                                        @disabled($paginaActividadesDetalle >= $this->totalPaginasActividadesDetalle())
                                        class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        Siguiente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($estadoRequisicionSeleccionado)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title-requisiciones" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center px-4 py-8">
                <button type="button" wire:click="cerrarDetalleRequisicion" class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm"></button>

                <div class="relative w-full max-w-7xl overflow-hidden rounded-lg bg-white shadow-xl dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                        <div>
                            <h3 id="modal-title-requisiciones" class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                Requisiciones en {{ $estadoRequisicionSeleccionado }}
                            </h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $totalRequisicionesDetalle }} registros encontrados para el filtro actual.
                            </p>
                        </div>
                        <button type="button" wire:click="cerrarDetalleRequisicion" class="rounded-md p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <div class="max-h-[76vh] overflow-y-auto p-6">
                        <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Requisición</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Descripción</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Depto</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Solicitante</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Detalles</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-zinc-500">Actualizado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                                        @forelse($detalleRequisicionesEstado as $requisicion)
                                            <tr
                                                wire:click="alternarRecursosRequisicion({{ $requisicion['id'] }})"
                                                class="cursor-pointer transition hover:bg-zinc-50 dark:hover:bg-zinc-800/70"
                                            >
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center gap-2">
                                                        <svg class="h-4 w-4 shrink-0 text-zinc-400 transition {{ $requisicionRecursosSeleccionada === $requisicion['id'] ? 'rotate-90' : '' }}" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                                        </svg>
                                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $requisicion['correlativo'] }}</p>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <p class="max-w-md text-sm text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($requisicion['descripcion'], 120) }}</p>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $requisicion['departamento'] }}</td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $requisicion['solicitante'] }}</td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $requisicion['detalles'] }}</td>
                                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">
                                                    {{ $requisicion['actualizado'] ?? '-' }}
                                                    <span class="block text-xs text-zinc-400">{{ $requisicion['dias'] }} días</span>
                                                </td>
                                            </tr>
                                            @if($requisicionRecursosSeleccionada === $requisicion['id'])
                                                <tr>
                                                    <td colspan="6" class="bg-zinc-50 px-4 py-4 dark:bg-zinc-950/40">
                                                        <div class="rounded-md border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                                                            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                                                                <div>
                                                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Recursos de la requisición</h4>
                                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($requisicion['recursos']) }} recursos/detalles asociados</p>
                                                                </div>
                                                            </div>

                                                            @if(count($requisicion['recursos']) > 0)
                                                                <div class="overflow-x-auto">
                                                                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                                                                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                                                                            <tr>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Recurso</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Cantidad</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Costo</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Total</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Objeto</th>
                                                                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-zinc-500">Entrega</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                                                            @foreach($requisicion['recursos'] as $recurso)
                                                                                <tr>
                                                                                    <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $recurso['recurso'] }}</td>
                                                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ number_format($recurso['cantidad'], 2) }} {{ $recurso['unidad'] }}</td>
                                                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">L {{ number_format($recurso['costo'], 2) }}</td>
                                                                                    <td class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">L {{ number_format($recurso['total'], 2) }}</td>
                                                                                    <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $recurso['objeto'] }}</td>
                                                                                    <td class="px-4 py-3">
                                                                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium ring-1 {{ $recurso['entregado'] ? $badgeClasses['emerald'] : $badgeClasses['amber'] }}">
                                                                                            {{ $recurso['entregado'] ? 'Entregado' : 'Pendiente' }}
                                                                                        </span>
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                                                    Esta requisición no tiene recursos asociados.
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No hay requisiciones en este estado.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex flex-col gap-3 border-t border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                    Página {{ $paginaRequisicionesDetalle }} de {{ $this->totalPaginasRequisicionesDetalle() }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="paginaAnteriorRequisiciones"
                                        @disabled($paginaRequisicionesDetalle <= 1)
                                        class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        Anterior
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="paginaSiguienteRequisiciones"
                                        @disabled($paginaRequisicionesDetalle >= $this->totalPaginasRequisicionesDetalle())
                                        class="rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    >
                                        Siguiente
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
