<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioEntrada;
use App\Models\Inventario\InventarioExistencia;
use App\Models\Inventario\InventarioKardex;
use App\Models\Inventario\InventarioProducto;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $productos = InventarioProducto::count();

        $productosConStock = InventarioProducto::withSum('existencias as existencia_total', 'cantidad_disponible')
            ->whereNotNull('stock_minimo')
            ->get();

        $productosAgotados = $productosConStock
            ->filter(fn ($producto) => (float) ($producto->existencia_total ?? 0) <= 0)
            ->count();

        $existenciasBajas = $productosConStock
            ->filter(fn ($producto) => (float) ($producto->existencia_total ?? 0) > 0
                && (float) ($producto->existencia_total ?? 0) <= (float) $producto->stock_minimo)
            ->count();

        $proximosAVencer = InventarioExistencia::where('cantidad_disponible', '>', 0)
            ->whereHas('lote', fn ($query) => $query->whereBetween('fecha_vencimiento', [now()->toDateString(), now()->addDays(30)->toDateString()]))
            ->count();

        $vencidos = InventarioExistencia::where('cantidad_disponible', '>', 0)
            ->whereHas('lote', fn ($query) => $query->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', now()->toDateString()))
            ->count();

        $totalDisponible = (float) InventarioExistencia::sum('cantidad_disponible');
        $totalReservado = (float) InventarioExistencia::sum('cantidad_reservada');

        $existenciasPorBodega = InventarioExistencia::select('bodega_id', DB::raw('SUM(cantidad_disponible) as total'))
            ->with('bodega')
            ->groupBy('bodega_id')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn ($existencia) => [
                'label' => $existencia->bodega?->nombre ?? 'Sin bodega',
                'value' => round((float) $existencia->total, 2),
            ])
            ->values();

        $topProductos = InventarioProducto::withSum('existencias as existencia_total', 'cantidad_disponible')
            ->orderByDesc('existencia_total')
            ->limit(5)
            ->get()
            ->map(fn ($producto) => [
                'label' => $producto->nombre,
                'codigo' => $producto->codigo_interno,
                'value' => round((float) ($producto->existencia_total ?? 0), 2),
                'stock_minimo' => $producto->stock_minimo,
            ])
            ->values();

        $movimientosRecientes = InventarioKardex::where('fecha_movimiento', '>=', now()->subDays(6)->startOfDay())
            ->orderBy('fecha_movimiento')
            ->get()
            ->groupBy(fn ($movimiento) => $movimiento->fecha_movimiento?->format('Y-m-d'));

        $movimientosPorDia = collect(range(6, 0))
            ->map(function ($daysAgo) use ($movimientosRecientes) {
                $date = now()->subDays($daysAgo);
                $items = $movimientosRecientes->get($date->format('Y-m-d'), collect());

                return [
                    'label' => $date->format('d/m'),
                    'entradas' => round((float) $items->sum('cantidad_entrada'), 2),
                    'salidas' => round((float) $items->sum('cantidad_salida'), 2),
                ];
            })
            ->values();

        $movimientosPorTipo = InventarioKardex::select('tipo_movimiento', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo_movimiento')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($movimiento) => [
                'label' => str($movimiento->tipo_movimiento)->replace('_', ' ')->title()->toString(),
                'value' => (int) $movimiento->total,
            ])
            ->values();

        $saludInventario = collect([
            ['label' => 'En rango', 'value' => max($productos - $existenciasBajas - $productosAgotados, 0)],
            ['label' => 'Stock bajo', 'value' => $existenciasBajas],
            ['label' => 'Agotados', 'value' => $productosAgotados],
            ['label' => 'Vencidos', 'value' => $vencidos],
        ]);

        return view('livewire.inventario.dashboard', [
            'productos' => $productos,
            'existenciasBajas' => $existenciasBajas,
            'productosAgotados' => $productosAgotados,
            'proximosAVencer' => $proximosAVencer,
            'vencidos' => $vencidos,
            'totalDisponible' => $totalDisponible,
            'totalReservado' => $totalReservado,
            'existenciasPorBodega' => $existenciasPorBodega,
            'topProductos' => $topProductos,
            'movimientosPorDia' => $movimientosPorDia,
            'movimientosPorTipo' => $movimientosPorTipo,
            'saludInventario' => $saludInventario,
            'ultimasEntradas' => InventarioEntrada::with('bodega')->latest()->limit(5)->get(),
            'ultimosMovimientos' => InventarioKardex::with(['producto', 'bodega', 'lote'])->latest()->limit(8)->get(),
        ]);
    }
}
