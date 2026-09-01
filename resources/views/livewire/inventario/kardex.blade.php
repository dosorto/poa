<div class="mx-auto mt-6 max-w-7xl space-y-5">
    <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Kardex de inventario</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Consulta entradas, salidas y saldos por producto, lote o referencia.</p>
            </div>
            <label class="w-full sm:w-40">
                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Por pagina</span>
                <select wire:model.live="perPage" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="10">10 filas</option>
                    <option value="15">15 filas</option>
                    <option value="25">25 filas</option>
                    <option value="50">50 filas</option>
                </select>
            </label>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Desde</span>
                <input wire:model.live="fecha_inicio" type="date" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Hasta</span>
                <input wire:model.live="fecha_fin" type="date" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Bodega</span>
                <select wire:model.live="bodega_id" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Todas</option>
                    @foreach ($bodegas as $bodega)
                        <option value="{{ $bodega->id }}">{{ $bodega->nombre }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Producto</span>
                <select wire:model.live="producto_id" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Todos</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}">{{ $producto->nombre }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Tipo</span>
                <select wire:model.live="tipo_movimiento" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Todos</option>
                    <option value="saldo_inicial">Saldo inicial</option>
                    <option value="entrada">Entrada</option>
                    <option value="salida">Salida</option>
                    <option value="ajuste_positivo">Ajuste positivo</option>
                    <option value="ajuste_negativo">Ajuste negativo</option>
                    <option value="devolucion">Devolucion</option>
                </select>
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">Referencia</span>
                <input wire:model.live="referencia" class="h-10 w-full rounded-md border border-zinc-300 bg-white px-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" placeholder="Buscar">
            </label>
        </div>
    </section>

    <section class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 text-left text-xs uppercase text-zinc-500 dark:bg-zinc-800/80 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 font-semibold">Bodega</th>
                        <th class="px-4 py-3 font-semibold">Producto</th>
                        <th class="px-4 py-3 font-semibold">Lote</th>
                        <th class="px-4 py-3 font-semibold">Tipo</th>
                        <th class="px-4 py-3 text-right font-semibold">Entrada</th>
                        <th class="px-4 py-3 text-right font-semibold">Salida</th>
                        <th class="px-4 py-3 text-right font-semibold">Saldo ant.</th>
                        <th class="px-4 py-3 text-right font-semibold">Saldo nuevo</th>
                        <th class="px-4 py-3 font-semibold">Referencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($movimientos as $movimiento)
                        @php
                            $tipo = $movimiento->tipo_movimiento;
                            $tipoClasses = match ($tipo) {
                                'entrada', 'ajuste_positivo', 'devolucion' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-400/30',
                                'salida', 'ajuste_negativo' => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-400/30',
                                default => 'bg-zinc-100 text-zinc-700 ring-zinc-600/20 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-500/30',
                            };
                        @endphp
                        <tr class="text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-200 dark:hover:bg-zinc-800/60">
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $movimiento->fecha_movimiento?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">{{ $movimiento->bodega?->nombre }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $movimiento->producto?->nombre }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $movimiento->producto?->codigo_interno }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $movimiento->lote?->codigo_lote ?? 'SIN-LOTE' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $tipoClasses }}">
                                    {{ str_replace('_', ' ', ucfirst($tipo)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-emerald-600 dark:text-emerald-300">{{ number_format((float) $movimiento->cantidad_entrada, 2) }}</td>
                            <td class="px-4 py-3 text-right font-medium text-red-600 dark:text-red-300">{{ number_format((float) $movimiento->cantidad_salida, 2) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ number_format((float) $movimiento->saldo_anterior, 2) }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-zinc-900 dark:text-white">{{ number_format((float) $movimiento->saldo_nuevo, 2) }}</td>
                            <td class="max-w-52 truncate px-4 py-3 text-zinc-600 dark:text-zinc-400" title="{{ $movimiento->referencia }}">{{ $movimiento->referencia }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Sin movimientos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-col gap-3 border-t border-zinc-200 px-4 py-3 dark:border-zinc-800 lg:flex-row lg:items-center lg:justify-between">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                @if ($movimientos->total() > 0)
                    Mostrando {{ $movimientos->firstItem() }}-{{ $movimientos->lastItem() }} de {{ $movimientos->total() }} movimientos
                @else
                    Sin movimientos para mostrar
                @endif
            </div>

            @if ($movimientos->hasPages())
                @php
                    $currentPage = $movimientos->currentPage();
                    $lastPage = $movimientos->lastPage();
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($lastPage, $currentPage + 2);
                @endphp

                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        wire:click="previousPage"
                        @disabled($movimientos->onFirstPage())
                        class="h-9 rounded-md border border-zinc-300 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-45 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800 {{ $movimientos->onFirstPage() ? '' : 'cursor-pointer' }}"
                    >
                        Anterior
                    </button>

                    @if ($startPage > 1)
                        <button type="button" wire:click="gotoPage(1)" class="h-9 min-w-9 cursor-pointer rounded-md border border-zinc-300 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 active:translate-y-px dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">1</button>
                        @if ($startPage > 2)
                            <span class="px-2 text-zinc-400">...</span>
                        @endif
                    @endif

                    @for ($page = $startPage; $page <= $endPage; $page++)
                        <button
                            type="button"
                            wire:click="gotoPage({{ $page }})"
                            class="h-9 min-w-9 cursor-pointer rounded-md border px-3 text-sm font-semibold transition active:translate-y-px {{ $page === $currentPage ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-zinc-300 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                        >
                            {{ $page }}
                        </button>
                    @endfor

                    @if ($endPage < $lastPage)
                        @if ($endPage < $lastPage - 1)
                            <span class="px-2 text-zinc-400">...</span>
                        @endif
                        <button type="button" wire:click="gotoPage({{ $lastPage }})" class="h-9 min-w-9 cursor-pointer rounded-md border border-zinc-300 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 active:translate-y-px dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">{{ $lastPage }}</button>
                    @endif

                    <button
                        type="button"
                        wire:click="nextPage"
                        @disabled(!$movimientos->hasMorePages())
                        class="h-9 rounded-md border border-zinc-300 px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 active:translate-y-px disabled:cursor-not-allowed disabled:opacity-45 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800 {{ $movimientos->hasMorePages() ? 'cursor-pointer' : '' }}"
                    >
                        Siguiente
                    </button>
                </div>
            @endif
        </div>
    </section>
</div>
