<div class="mx-auto mt-6 mb-8 space-y-6">
    @php
        $stockTotal = $totalDisponible + $totalReservado;
        $disponibilidad = $stockTotal > 0 ? round(($totalDisponible / $stockTotal) * 100) : 0;
    @endphp

    <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-lg border border-zinc-200 dark:border-zinc-800 p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-400">Inventario / Bodega</p>
                <h1 class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">Panel de control de inventario</h1>
                <p class="mt-2 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
                    Resumen operativo de existencias, alertas de stock, vencimientos y movimientos recientes.
                </p>
            </div>

            <div class="grid w-full grid-cols-2 gap-3 sm:w-auto">
                <a href="{{ route('inventario.existencias') }}" class="inline-flex items-center justify-center rounded-md border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                    Existencias
                </a>
                <a href="{{ route('inventario.entradas') }}" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                    Entradas
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Productos registrados</p>
                    <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($productos) }}</p>
                </div>
                <div class="rounded-full bg-indigo-100 p-3 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">Catálogo disponible para bodega.</p>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Unidades disponibles</p>
                    <p class="mt-2 text-3xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($totalDisponible, 2) }}</p>
                </div>
                <div class="rounded-full bg-emerald-100 p-3 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                </div>
            </div>
            <div class="mt-4 h-2 rounded-full bg-zinc-100 dark:bg-zinc-800">
                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ min($disponibilidad, 100) }}%"></div>
            </div>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $disponibilidad }}% del stock no reservado.</p>
        </div>

        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm transition-shadow hover:shadow-md dark:border-amber-900/60 dark:bg-amber-950/30">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-amber-700 dark:text-amber-300">Alertas de stock</p>
                    <p class="mt-2 text-3xl font-bold text-amber-900 dark:text-amber-100">{{ number_format($existenciasBajas + $productosAgotados) }}</p>
                </div>
                <div class="rounded-full bg-amber-100 p-3 text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">{{ $existenciasBajas }} bajo minimo / {{ $productosAgotados }} agotados.</p>
        </div>

        <div class="rounded-lg border border-rose-200 bg-rose-50 p-5 shadow-sm transition-shadow hover:shadow-md dark:border-rose-900/60 dark:bg-rose-950/30">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-rose-700 dark:text-rose-300">Control de vencimiento</p>
                    <p class="mt-2 text-3xl font-bold text-rose-900 dark:text-rose-100">{{ number_format($proximosAVencer + $vencidos) }}</p>
                </div>
                <div class="rounded-full bg-rose-100 p-3 text-rose-700 dark:bg-rose-900 dark:text-rose-200">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-rose-700 dark:text-rose-300">{{ $proximosAVencer }} proximos / {{ $vencidos }} vencidos.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 xl:col-span-2">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Movimientos de los ultimos 7 dias</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Entradas y salidas registradas por fecha.</p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ number_format($totalReservado, 2) }} reservado</span>
            </div>
            <div id="inventarioMovimientosChart" class="mt-5 h-72 w-full"></div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Salud del inventario</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Distribucion de riesgos operativos.</p>
            <div id="inventarioSaludChart" class="mt-5 h-72 w-full"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Existencias por bodega</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Bodegas con mayor disponibilidad.</p>
            <div id="inventarioBodegasChart" class="mt-5 h-72 w-full"></div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Tipos de movimiento</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Frecuencia por categoria de kardex.</p>
            <div id="inventarioTiposChart" class="mt-5 h-72 w-full"></div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Productos con mas stock</h2>
            <div class="mt-5 space-y-4">
                @forelse ($topProductos as $producto)
                    @php
                        $maxTop = max($topProductos->max('value') ?? 0, 1);
                        $percent = min(round(($producto['value'] / $maxTop) * 100), 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $producto['label'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $producto['codigo'] }}</p>
                            </div>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($producto['value'], 2) }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-2 rounded-full bg-cyan-500" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Sin existencias registradas.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ultimas entradas</h2>
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Recepcion</span>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($ultimasEntradas as $entrada)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $entrada->numero_entrada }}</p>
                            <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $entrada->bodega?->nombre ?? 'Sin bodega' }} / {{ $entrada->estado }}</p>
                        </div>
                        <span class="shrink-0 text-sm text-zinc-500 dark:text-zinc-400">{{ $entrada->fecha_entrada?->format('Y-m-d') ?? 'N/A' }}</span>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Sin entradas registradas.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ultimos movimientos</h2>
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Kardex</span>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($ultimosMovimientos as $movimiento)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $movimiento->producto?->nombre ?? 'Producto no disponible' }}</p>
                            <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ str($movimiento->tipo_movimiento)->replace('_', ' ')->title() }} / {{ $movimiento->bodega?->nombre ?? 'Sin bodega' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Saldo {{ number_format((float) $movimiento->saldo_nuevo, 2) }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $movimiento->fecha_movimiento?->format('Y-m-d') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="rounded-md border border-dashed border-zinc-300 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Sin movimientos en kardex.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function renderInventarioDashboardCharts() {
        if (typeof ApexCharts === 'undefined') return;

        window.inventarioDashboardCharts = window.inventarioDashboardCharts || {};
        Object.values(window.inventarioDashboardCharts).forEach(chart => chart && chart.destroy());

        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#d4d4d8' : '#3f3f46';
        const gridColor = isDark ? '#27272a' : '#e4e4e7';
        const tooltipTheme = isDark ? 'dark' : 'light';
        const palette = ['#4f46e5', '#0891b2', '#10b981', '#f59e0b', '#e11d48', '#7c3aed'];

        const movimientos = @json($movimientosPorDia);
        const bodegas = @json($existenciasPorBodega);
        const tipos = @json($movimientosPorTipo);
        const salud = @json($saludInventario);

        const common = {
            chart: {
                toolbar: { show: false },
                background: 'transparent',
                fontFamily: 'Inter, ui-sans-serif, system-ui, sans-serif'
            },
            dataLabels: { enabled: false },
            grid: { borderColor: gridColor, strokeDashArray: 4 },
            legend: { labels: { colors: textColor } },
            tooltip: { theme: tooltipTheme },
            theme: { mode: isDark ? 'dark' : 'light' }
        };

        const movimientosEl = document.querySelector('#inventarioMovimientosChart');
        if (movimientosEl) {
            window.inventarioDashboardCharts.movimientos = new ApexCharts(movimientosEl, {
                ...common,
                series: [
                    { name: 'Entradas', data: movimientos.map(item => item.entradas) },
                    { name: 'Salidas', data: movimientos.map(item => item.salidas) }
                ],
                chart: { ...common.chart, type: 'area', height: '100%' },
                colors: ['#10b981', '#e11d48'],
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
                xaxis: {
                    categories: movimientos.map(item => item.label),
                    labels: { style: { colors: textColor } }
                },
                yaxis: { labels: { style: { colors: textColor } } }
            });
            window.inventarioDashboardCharts.movimientos.render();
        }

        const saludEl = document.querySelector('#inventarioSaludChart');
        if (saludEl) {
            window.inventarioDashboardCharts.salud = new ApexCharts(saludEl, {
                ...common,
                series: salud.map(item => item.value),
                labels: salud.map(item => item.label),
                chart: { ...common.chart, type: 'donut', height: '100%' },
                colors: ['#10b981', '#f59e0b', '#64748b', '#e11d48'],
                plotOptions: { pie: { donut: { size: '68%' } } },
                stroke: { colors: [isDark ? '#18181b' : '#ffffff'] },
                legend: { position: 'bottom', labels: { colors: textColor } }
            });
            window.inventarioDashboardCharts.salud.render();
        }

        const bodegasEl = document.querySelector('#inventarioBodegasChart');
        if (bodegasEl) {
            window.inventarioDashboardCharts.bodegas = new ApexCharts(bodegasEl, {
                ...common,
                series: [{ name: 'Disponible', data: bodegas.map(item => item.value) }],
                chart: { ...common.chart, type: 'bar', height: '100%' },
                colors: ['#0891b2'],
                plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
                xaxis: {
                    categories: bodegas.map(item => item.label),
                    labels: { rotate: -25, style: { colors: textColor } }
                },
                yaxis: { labels: { style: { colors: textColor } } }
            });
            window.inventarioDashboardCharts.bodegas.render();
        }

        const tiposEl = document.querySelector('#inventarioTiposChart');
        if (tiposEl) {
            window.inventarioDashboardCharts.tipos = new ApexCharts(tiposEl, {
                ...common,
                series: [{ name: 'Movimientos', data: tipos.map(item => item.value) }],
                chart: { ...common.chart, type: 'bar', height: '100%' },
                colors: palette,
                plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '55%' } },
                xaxis: { labels: { style: { colors: textColor } } },
                yaxis: {
                    categories: tipos.map(item => item.label),
                    labels: { style: { colors: textColor } }
                }
            });
            window.inventarioDashboardCharts.tipos.render();
        }
    }

    document.addEventListener('DOMContentLoaded', renderInventarioDashboardCharts);
    document.addEventListener('livewire:navigated', renderInventarioDashboardCharts);
</script>
@endpush
