<div class="mx-auto mt-6 max-w-6xl space-y-5">
    @if (session()->has('message'))
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(320px,420px)_1fr]">
        <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Carga inicial</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Selecciona la bodega y el archivo base del inventario.</p>
            </div>

            <div class="space-y-4">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Bodega destino</span>
                    <select wire:model="bodega_id" class="w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Seleccionar bodega</option>
                        @foreach ($bodegas as $bodega)
                            <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                        @endforeach
                    </select>
                </label>

                <div>
                    <span class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Archivo</span>
                    <label for="inventario-import-file" class="flex min-h-24 cursor-pointer flex-col items-center justify-center rounded-md border border-dashed border-zinc-300 bg-zinc-50 px-4 py-5 text-center transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-zinc-700 dark:bg-zinc-800/70 dark:hover:border-blue-500 dark:hover:bg-blue-950/20">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">
                            {{ $excelFile ? $excelFile->getClientOriginalName() : 'Seleccionar archivo' }}
                        </span>
                        <span class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Excel o CSV: .xlsx, .xls, .csv</span>
                    </label>
                    <input id="inventario-import-file" type="file" wire:model="excelFile" accept=".xlsx,.xls,.csv" class="sr-only">
                </div>

                <div class="flex flex-col gap-2 sm:flex-row">
                    @can('inventario.importar')
                        <button
                            type="button"
                            wire:click="importar"
                            wire:loading.attr="disabled"
                            wire:target="excelFile,importar"
                            class="inline-flex h-10 flex-1 cursor-pointer items-center justify-center rounded-md bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span wire:loading.remove wire:target="excelFile,importar">Importar</span>
                            <span wire:loading wire:target="excelFile">Subiendo...</span>
                            <span wire:loading wire:target="importar">Importando...</span>
                        </button>
                    @endcan

                    <button type="button" wire:click="descargarPlantilla" class="inline-flex h-10 cursor-pointer items-center justify-center rounded-md border border-zinc-300 bg-white px-4 text-sm font-semibold text-zinc-700 shadow-sm transition hover:bg-zinc-50 active:translate-y-px dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        Descargar plantilla
                    </button>
                </div>
            </div>

            <div class="mt-5 rounded-md bg-zinc-50 p-3 text-xs leading-5 text-zinc-600 dark:bg-zinc-800/70 dark:text-zinc-400">
                Columnas esperadas: codigo_interno, codigo_barra, nombre, descripcion, unidad_medida_id, idobjeto, idCubs, codigo_lote, cantidad, fecha_vencimiento, ubicacion, stock_minimo.
            </div>

            @if ($errors->any())
                <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($errores)
                <div class="mt-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
                    <div class="font-medium">Errores detectados</div>
                    @foreach ($errores as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-zinc-900 dark:text-white">Importaciones recientes</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Historial de cargas realizadas.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-left text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            <th class="py-2 pr-4 font-semibold">Archivo</th>
                            <th class="py-2 pr-4 font-semibold">Usuario</th>
                            <th class="py-2 pr-4 font-semibold">Estado</th>
                            <th class="py-2 pr-4 font-semibold">Total</th>
                            <th class="py-2 pr-4 font-semibold">Importadas</th>
                            <th class="py-2 font-semibold">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($importaciones as $importacion)
                            <tr class="text-zinc-700 dark:text-zinc-200">
                                <td class="py-3 pr-4 font-medium text-zinc-900 dark:text-white">{{ $importacion->archivo }}</td>
                                <td class="py-3 pr-4">{{ $importacion->usuario?->name }}</td>
                                <td class="py-3 pr-4">{{ $importacion->estado }}</td>
                                <td class="py-3 pr-4">{{ $importacion->total_filas }}</td>
                                <td class="py-3 pr-4">{{ $importacion->filas_importadas }}</td>
                                <td class="py-3">{{ $importacion->fecha?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    Sin importaciones.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
