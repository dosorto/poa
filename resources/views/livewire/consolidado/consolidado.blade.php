<div
    x-data="{
        copied: false,
        copiedLabel: '',
        copyTimeout: null,
        showCopied(label) {
            this.copied = true;
            this.copiedLabel = label;
            clearTimeout(this.copyTimeout);
            this.copyTimeout = setTimeout(() => {
                this.copied = false;
            }, 1800);
        },
        fallbackCopyText(text, label) {
            const textArea = document.createElement('textarea');
            textArea.value = text ?? '';
            textArea.setAttribute('readonly', '');
            textArea.style.position = 'fixed';
            textArea.style.top = '-9999px';
            textArea.style.left = '-9999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                const copied = document.execCommand('copy');
                if (copied) {
                    this.showCopied(label);
                }
            } finally {
                document.body.removeChild(textArea);
            }
        },
        copyText(text, label = 'Texto copiado') {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    this.showCopied(label);
                }).catch(() => {
                    this.fallbackCopyText(text, label);
                });

                return;
            }

            this.fallbackCopyText(text, label);
        }
    }"
    class="space-y-6"
>
    <div class="mx-auto px-4 bg-white dark:bg-zinc-900 overflow-hidden shadow sm:rounded-lg p-4 sm:p-6">
        <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-zinc-800 dark:text-zinc-200">
                    Consolidado por Unidad Ejecutora
                </h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Consulta centralizada de actividades para copiar información institucional, indicadores, tareas, recursos y montos hacia otros sistemas.
                </p>
            </div>
            <div
                x-show="copied"
                x-transition
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300"
            >
                <span x-text="copiedLabel"></span>
            </div>
        </div>

        <div class="mb-6 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/60">
            <div class="mb-4">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-700 dark:text-zinc-200">
                    Filtros Generales
                </h3>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-searchable-select
                        wire:model="unidadEjecutoraId"
                        label="Unidad Ejecutora"
                        placeholder="Buscar unidad ejecutora..."
                        defaultText="Seleccione una unidad ejecutora"
                        :options="collect($unidadesEjecutoras)->map(fn($unidad) => ['id' => (string) $unidad['id'], 'text' => $unidad['name']])->toArray()"
                    />
                </div>

                <div>
                    <label for="anio" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        Año de Planificación
                    </label>
                    <select
                        id="anio"
                        wire:model.live="anio"
                        class="block w-full rounded-lg border-zinc-300 bg-white text-zinc-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200"
                    >
                        @foreach($anios as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Total Asignado</p>
                <p class="mt-2 text-2xl font-bold text-sky-900 dark:text-sky-100">
                    L {{ number_format($estadisticas['asignado']['monto'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-sm text-sky-700 dark:text-sky-300">
                    {{ $estadisticas['asignado']['actividades'] ?? 0 }} actividad(es) en la UE
                </p>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Total Planificado Aprobado</p>
                <p class="mt-2 text-2xl font-bold text-emerald-900 dark:text-emerald-100">
                    L {{ number_format($estadisticas['aprobado']['monto'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-sm text-emerald-700 dark:text-emerald-300">
                    {{ $estadisticas['aprobado']['actividades'] ?? 0 }} actividad(es) aprobadas
                </p>
            </div>

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">Total en Subsanación</p>
                <p class="mt-2 text-2xl font-bold text-amber-900 dark:text-amber-100">
                    L {{ number_format($estadisticas['subsanacion']['monto'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                    {{ $estadisticas['subsanacion']['actividades'] ?? 0 }} actividad(es) en reformulación
                </p>
            </div>

            <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-800 dark:bg-violet-900/20">
                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Total Pendiente de Revisión</p>
                <p class="mt-2 text-2xl font-bold text-violet-900 dark:text-violet-100">
                    L {{ number_format($estadisticas['revision']['monto'] ?? 0, 2) }}
                </p>
                <p class="mt-1 text-sm text-violet-700 dark:text-violet-300">
                    {{ $estadisticas['revision']['actividades'] ?? 0 }} actividad(es) en revisión
                </p>
            </div>
        </div>

        <div class="mb-6 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-zinc-700 dark:text-zinc-200">
                        Filtros del Consolidado
                    </h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Refina la lista de actividades por dimensión y departamento dentro de la unidad ejecutora seleccionada.
                    </p>
                </div>
                <x-spinner-button
                    wire:click="exportarExcel"
                    loadingTarget="exportarExcel"
                    :loadingText="__('Generando...')"
                    class="justify-center rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700"
                >
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14" />
                    </svg>
                    Descargar en Excel
                </x-spinner-button>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <x-searchable-select
                        wire:model="dimensionId"
                        label="Dimensión"
                        placeholder="Buscar dimensión..."
                        defaultText="Todas las dimensiones"
                        clearText="Todas las dimensiones"
                        :options="$dimensiones->map(fn($dimension) => ['id' => (string) $dimension->id, 'text' => $dimension->nombre])->toArray()"
                    />
                </div>

                <div>
                    <x-searchable-select
                        wire:model="departamentoId"
                        label="Departamento"
                        placeholder="Buscar departamento..."
                        defaultText="Todos los departamentos"
                        clearText="Todos los departamentos"
                        :options="collect($departamentos)->map(fn($departamento) => ['id' => (string) $departamento['id'], 'text' => $departamento['name']])->toArray()"
                    />
                </div>
            </div>
        </div>

        <div class="mb-6 border-b border-zinc-200 dark:border-zinc-700">
            <nav class="-mb-px flex flex-wrap gap-2" aria-label="Tabs">
                @foreach($tabs as $tabKey => $tab)
                    <button
                        type="button"
                        wire:click="$set('activeTab', '{{ $tabKey }}')"
                        class="inline-flex items-center gap-2 rounded-t-xl border-b-2 px-4 py-3 text-sm font-medium transition-colors {{ $activeTab === $tabKey ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
                    >
                        <span>{{ $tab['label'] }}</span>
                        <span class="inline-flex min-w-[1.75rem] items-center justify-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 {{ $activeTab === $tabKey ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : '' }}">
                            {{ $tab['count'] }}
                        </span>
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="space-y-4">
            @forelse($actividades as $actividad)
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-col gap-4 border-b border-zinc-200 px-4 py-4 dark:border-zinc-700 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->getEstadoBadgeClass($actividad->estado) }}">
                                    {{ $actividad->estado ?? 'N/A' }}
                                </span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $actividad->unidadEjecutora->name ?? 'N/A' }} | {{ $actividad->departamento->name ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ ($actividad->correlativo ? $actividad->correlativo . ' - ' : '') . ($actividad->nombre ?? 'N/A') }}
                                </h3>
                                <button
                                    type="button"
                                    x-on:click="copyText(@js(($actividad->correlativo ? $actividad->correlativo . ' - ' : '') . ($actividad->nombre ?? 'N/A')), 'Nombre de actividad copiado')"
                                    class="inline-flex items-center rounded-lg border border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    <svg class="mr-1.5 h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-8-4h8m2 12H6a2 2 0 01-2-2V6a2 2 0 012-2h8l4 4v10a2 2 0 01-2 2z" />
                                    </svg>
                                    Copiar
                                </button>
                            </div>
                            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $actividad->resultadoActividad ?: 'Sin resultado de actividad registrado.' }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Dimensión:</span>
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ $actividad->resultado->area->objetivo->dimension->nombre ?? 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Monto:</span>
                                    <span class="text-zinc-600 dark:text-zinc-400">L {{ number_format($this->getActividadTotalMonto($actividad), 2) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300">SPI:</span>
                                    <span class="text-zinc-600 dark:text-zinc-400">{{ $actividad->uploadedIntoSPI ? 'Listo' : 'Pendiente' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                x-on:click="copyText(@js($this->buildActividadTexto($actividad)), 'Actividad copiada al portapapeles')"
                                class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-8-4h8m2 12H6a2 2 0 01-2-2V6a2 2 0 012-2h8l4 4v10a2 2 0 01-2 2z" />
                                </svg>
                                Copiar actividad
                            </button>

                            <button
                                wire:click="toggleSPI({{ $actividad->id }})"
                                type="button"
                                class="inline-flex items-center rounded-lg px-3 py-2 text-sm font-medium {{ $actividad->uploadedIntoSPI ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}"
                            >
                                {{ $actividad->uploadedIntoSPI ? 'SPI listo' : 'Marcar SPI' }}
                            </button>

                            <button
                                wire:click="toggleExpand({{ $actividad->id }})"
                                type="button"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                {{ $expandedRow === $actividad->id ? 'Ocultar detalle' : 'Ver detalle' }}
                                <svg class="ml-2 h-4 w-4 transition-transform {{ $expandedRow === $actividad->id ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    @if($expandedRow === $actividad->id && $actividadDetalle)
                        <div class="space-y-4 bg-zinc-50 px-4 py-4 dark:bg-zinc-950/40">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h4 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos Institucionales</h4>
                                        <button
                                            type="button"
                                            x-on:click="copyText(@js($this->buildActividadInstitucionalTexto($actividadDetalle)), 'Datos institucionales copiados')"
                                            class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            Copiar
                                        </button>
                                    </div>
                                    <div class="space-y-3 text-sm">
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Dimensión</p>
                                                <button type="button" x-on:click="copyText(@js($actividadDetalle->resultado->area->objetivo->dimension->nombre ?? 'N/A'), 'Dimensión copiada')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->area->objetivo->dimension->nombre ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Objetivo</p>
                                                <button type="button" x-on:click="copyText(@js($actividadDetalle->resultado->area->objetivo->nombre ?? 'N/A'), 'Objetivo copiado')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->area->objetivo->nombre ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Área</p>
                                                <button type="button" x-on:click="copyText(@js($actividadDetalle->resultado->area->nombre ?? 'N/A'), 'Área copiada')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->area->nombre ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Resultado Institucional</p>
                                                <button type="button" x-on:click="copyText(@js($actividadDetalle->resultado->nombre ?? 'N/A'), 'Resultado institucional copiado')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->resultado->nombre ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                        <h4 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Datos Generales</h4>
                                        <button
                                            type="button"
                                            x-on:click="copyText(@js($this->buildActividadGeneralTexto($actividadDetalle)), 'Datos generales copiados')"
                                            class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            Copiar
                                        </button>
                                    </div>
                                    <div class="space-y-3 text-sm">
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Resultado de actividad</p>
                                                <button type="button" x-on:click="copyText(@js($this->getResultadoActividadConCorrelativo($actividadDetalle)), 'Resultado de actividad copiado')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <p class="text-zinc-600 dark:text-zinc-400">{{ $this->getResultadoActividadConCorrelativo($actividadDetalle) }}</p>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Población objetivo</p>
                                                <button type="button" x-on:click="copyText(@js($actividadDetalle->poblacion_objetivo ?? 'N/A'), 'Población objetivo copiada')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->poblacion_objetivo ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Medio de verificación</p>
                                                <button type="button" x-on:click="copyText(@js($actividadDetalle->medio_verificacion ?? 'N/A'), 'Medio de verificación copiado')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->medio_verificacion ?? 'N/A' }}</p>
                                        </div>
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            <div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="font-medium text-zinc-700 dark:text-zinc-300">Categoría</p>
                                                    <button type="button" x-on:click="copyText(@js($actividadDetalle->categoria->categoria ?? 'N/A'), 'Categoría copiada')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                                </div>
                                                <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->categoria->categoria ?? 'N/A' }}</p>
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="font-medium text-zinc-700 dark:text-zinc-300">Tipo de actividad</p>
                                                    <button type="button" x-on:click="copyText(@js($actividadDetalle->tipo->tipo ?? 'N/A'), 'Tipo de actividad copiado')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                                </div>
                                                <p class="text-zinc-600 dark:text-zinc-400">{{ $actividadDetalle->tipo->tipo ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Encargados</p>
                                                <button type="button" x-on:click="copyText(@js($actividadDetalle->empleados->map(fn ($empleado) => trim(($empleado->nombre ?? '') . ' ' . ($empleado->apellido ?? '')) . ' | #' . ($empleado->num_empleado ?? 'N/A'))->implode('; ') ?: 'N/A'), 'Encargados copiados')" class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">Copiar</button>
                                            </div>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @forelse($actividadDetalle->empleados as $empleado)
                                                    <span class="inline-flex rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                        {{ trim(($empleado->nombre ?? '') . ' ' . ($empleado->apellido ?? '')) }} | #{{ $empleado->num_empleado ?? 'N/A' }}
                                                    </span>
                                                @empty
                                                    <span class="text-zinc-500 dark:text-zinc-400">N/A</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($actividadDetalle->indicadores->isNotEmpty())
                                @php
                                    $indicadoresTextoConsolidado = $actividadDetalle->indicadores
                                        ->map(function ($indicador) {
                                            return $this->buildIndicadorTexto($indicador);
                                        })
                                        ->implode(PHP_EOL . PHP_EOL);
                                @endphp
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <h4 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Indicadores y Planificación</h4>
                                        <button
                                            type="button"
                                            x-on:click="copyText(@js($indicadoresTextoConsolidado), 'Indicadores copiados')"
                                            class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            Copiar indicadores
                                        </button>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach($actividadDetalle->indicadores as $indicador)
                                            @php
                                                $trimestres = collect($this->getIndicadorTrimestres($indicador))
                                                    ->filter(function ($trimestre) {
                                                        return ((float) ($trimestre['planificado'] ?? 0)) > 0
                                                            || ((float) ($trimestre['ejecutado'] ?? 0)) > 0;
                                                    })
                                                    ->values();
                                            @endphp
                                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                                <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h5 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $indicador->nombre }}</h5>
                                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $indicador->isPorcentaje ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-900/30 dark:text-fuchsia-300' }}">
                                                                {{ $indicador->isPorcentaje ? 'Porcentaje' : 'Cantidad' }}
                                                            </span>
                                                        </div>
                                                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                                            {{ $indicador->descripcion ?: 'Sin descripción.' }}
                                                        </p>
                                                        <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-3">
                                                            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800">
                                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Meta planificada</p>
                                                                <p class="text-zinc-600 dark:text-zinc-400">{{ number_format($indicador->cantidadPlanificada ?? 0, 2) }}</p>
                                                            </div>
                                                            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800">
                                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Cantidad ejecutada</p>
                                                                <p class="text-zinc-600 dark:text-zinc-400">{{ number_format($indicador->cantidadEjecutada ?? 0, 2) }}</p>
                                                            </div>
                                                            <div class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800">
                                                                <p class="font-medium text-zinc-700 dark:text-zinc-300">Promedio alcanzado</p>
                                                                <p class="text-zinc-600 dark:text-zinc-400">{{ number_format($indicador->promedioAlcanzado ?? 0, 2) }}%</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        x-on:click="copyText(@js($this->buildIndicadorTexto($indicador)), 'Indicador copiado')"
                                                        class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                    >
                                                        Copiar indicador
                                                    </button>
                                                </div>

                                                <div class="grid grid-cols-1 gap-3 xl:grid-cols-4">
                                                    @foreach($trimestres as $trimestre)
                                                        @php
                                                            $planificadoTrimestre = (float) ($trimestre['planificado'] ?? 0);
                                                            $ejecutadoTrimestre = (float) ($trimestre['ejecutado'] ?? 0);
                                                            $avance = $planificadoTrimestre > 0
                                                                ? ($ejecutadoTrimestre / $planificadoTrimestre) * 100
                                                                : 0;
                                                        @endphp
                                                        <div class="rounded-xl bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                                                            <p class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $trimestre['nombre'] }}</p>
                                                            <p class="mt-1 text-zinc-600 dark:text-zinc-400">
                                                                {{ $trimestre['fechaInicio'] ? \Carbon\Carbon::parse($trimestre['fechaInicio'])->format('d/m/Y') : '-' }}
                                                                -
                                                                {{ $trimestre['fechaFin'] ? \Carbon\Carbon::parse($trimestre['fechaFin'])->format('d/m/Y') : '-' }}
                                                            </p>
                                                            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Planificado: {{ number_format($planificadoTrimestre, 2) }}</p>
                                                            <p class="text-zinc-600 dark:text-zinc-400">Ejecutado: {{ number_format($ejecutadoTrimestre, 2) }}</p>
                                                            <p class="font-medium {{ $avance >= 80 ? 'text-green-600 dark:text-green-400' : ($avance >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                                                                Avance: {{ number_format($avance, 1) }}%
                                                            </p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($actividadDetalle->tareas->isNotEmpty())
                                @php
                                    $tareasTextoConsolidado = $actividadDetalle->tareas
                                        ->map(function ($tarea) {
                                            return $this->buildTareaTexto($tarea);
                                        })
                                        ->implode(PHP_EOL . PHP_EOL);
                                @endphp
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <h4 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Tareas, Recursos y Montos</h4>
                                        <button
                                            type="button"
                                            x-on:click="copyText(@js($tareasTextoConsolidado), 'Tareas y recursos copiados')"
                                            class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                        >
                                            Copiar tareas y recursos
                                        </button>
                                    </div>

                                    <div class="space-y-4">
                                        @foreach($actividadDetalle->tareas as $tarea)
                                            <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
                                                <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h5 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $tarea->nombre }}</h5>
                                                            <span class="inline-flex rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                                                {{ $tarea->correlativo ?? 'N/A' }}
                                                            </span>
                                                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $this->getEstadoBadgeClass($tarea->estado) }}">
                                                                {{ $tarea->estado ?? 'N/A' }}
                                                            </span>
                                                        </div>
                                                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                                            {{ $tarea->descripcion ?: 'Sin descripción.' }}
                                                        </p>
                                                    </div>

                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
                                                            Total: L {{ number_format($tarea->presupuestos->sum('total'), 2) }}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            x-on:click="copyText(@js($tarea->nombre ?? 'N/A'), 'Tarea copiada')"
                                                            class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                        >
                                                            Copiar tarea
                                                        </button>
                                                    </div>
                                                </div>

                                                @if($tarea->presupuestos->isNotEmpty())
                                                    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                                                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                                            <thead class="bg-zinc-50 dark:bg-zinc-800">
                                                                <tr>
                                                                    <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Recurso</th>
                                                                    <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Cantidad</th>
                                                                    <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Costo Unitario</th>
                                                                    <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Total</th>
                                                                    <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Objeto del Gasto</th>
                                                                    <th class="px-3 py-2 text-left font-semibold text-zinc-600 dark:text-zinc-300">Fuente / Mes</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                                                @foreach($tarea->presupuestos as $presupuesto)
                                                                    <tr class="bg-white dark:bg-zinc-900">
                                                                        <td class="px-3 py-3 align-top text-zinc-800 dark:text-zinc-200">
                                                                            <div class="flex items-start justify-between gap-2">
                                                                                <div>
                                                                                    <p class="font-medium">{{ $presupuesto->recurso ?? 'N/A' }}</p>
                                                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $presupuesto->detalle_tecnico ?: 'Sin detalle técnico' }}</p>
                                                                                </div>
                                                                                <button
                                                                                    type="button"
                                                                                    x-on:click="copyText(@js(($presupuesto->recurso ?? 'N/A') . ' | ' . ($presupuesto->detalle_tecnico ?: 'Sin detalle técnico')), 'Recurso copiado')"
                                                                                    class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                                                >
                                                                                    Copiar
                                                                                </button>
                                                                            </div>
                                                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                                                {{ $presupuesto->grupoGasto->nombre ?? 'N/A' }}
                                                                            </p>
                                                                        </td>
                                                                        <td class="px-3 py-3 align-top text-zinc-600 dark:text-zinc-400">
                                                                            <div class="flex items-start justify-between gap-2">
                                                                                <span>{{ number_format($presupuesto->cantidad ?? 0, 2) }}</span>
                                                                                <button
                                                                                    type="button"
                                                                                    x-on:click="copyText(@js(number_format($presupuesto->cantidad ?? 0, 2, '.', ',')), 'Cantidad copiada')"
                                                                                    class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                                                >
                                                                                    Copiar
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-3 py-3 align-top text-zinc-600 dark:text-zinc-400">
                                                                            <div class="flex items-start justify-between gap-2">
                                                                                <span>L {{ number_format($presupuesto->costounitario ?? 0, 2) }}</span>
                                                                                <button
                                                                                    type="button"
                                                                                    x-on:click="copyText(@js('L ' . number_format($presupuesto->costounitario ?? 0, 2, '.', ',')), 'Costo unitario copiado')"
                                                                                    class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                                                >
                                                                                    Copiar
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-3 py-3 align-top font-semibold text-zinc-800 dark:text-zinc-200">
                                                                            <div class="flex items-start justify-between gap-2">
                                                                                <span>L {{ number_format($presupuesto->total ?? 0, 2) }}</span>
                                                                                <button
                                                                                    type="button"
                                                                                    x-on:click="copyText(@js('L ' . number_format($presupuesto->total ?? 0, 2, '.', ',')), 'Total copiado')"
                                                                                    class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                                                >
                                                                                    Copiar
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-3 py-3 align-top text-zinc-600 dark:text-zinc-400">
                                                                            <div class="flex items-start justify-between gap-2">
                                                                                <div>
                                                                                    {{ $presupuesto->objetoGasto->identificador ?? 'N/A' }}<br>
                                                                                    <span class="text-xs">{{ $presupuesto->objetoGasto->nombre ?? 'N/A' }}</span>
                                                                                </div>
                                                                                <button
                                                                                    type="button"
                                                                                    x-on:click="copyText(@js(($presupuesto->objetoGasto->identificador ?? 'N/A') . ' | ' . ($presupuesto->objetoGasto->nombre ?? 'N/A')), 'Objeto del gasto copiado')"
                                                                                    class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                                                >
                                                                                    Copiar
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-3 py-3 align-top text-zinc-600 dark:text-zinc-400">
                                                                            <div class="flex items-start justify-between gap-2">
                                                                                <div>
                                                                                    {{ $presupuesto->fuente->identificador ?? 'N/A' }} - {{ $presupuesto->fuente->nombre ?? 'N/A' }}<br>
                                                                                    <span class="text-xs">{{ $presupuesto->mes->mes ?? 'N/A' }}</span>
                                                                                </div>
                                                                                <button
                                                                                    type="button"
                                                                                    x-on:click="copyText(@js(($presupuesto->fuente->identificador ?? 'N/A') . ' - ' . ($presupuesto->fuente->nombre ?? 'N/A') . ' | ' . ($presupuesto->mes->mes ?? 'N/A')), 'Fuente y mes copiados')"
                                                                                    class="inline-flex items-center rounded-lg border border-zinc-300 px-2 py-1 text-[11px] font-medium text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                                                                >
                                                                                    Copiar
                                                                                </button>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <div class="rounded-xl border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                                        Esta tarea no tiene recursos presupuestados.
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-800/40">
                    <p class="text-lg font-semibold text-zinc-700 dark:text-zinc-200">No se encontraron actividades</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        Cambia la Unidad Ejecutora, el año o los filtros inferiores para consultar otra información.
                    </p>
                </div>
            @endforelse
        </div>

        @if($actividades->hasPages())
            <div class="mt-6">
                {{ $actividades->links() }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            Livewire.on('spi-updated', (event) => {
                console.log(event.message);
            });
        </script>
    @endpush
</div>
