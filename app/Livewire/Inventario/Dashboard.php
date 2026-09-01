<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioEntrada;
use App\Models\Inventario\InventarioExistencia;
use App\Models\Inventario\InventarioKardex;
use App\Models\Inventario\InventarioProducto;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $productos = InventarioProducto::count();
        $existenciasBajas = InventarioProducto::withSum('existencias as existencia_total', 'cantidad_disponible')
            ->whereNotNull('stock_minimo')
            ->get()
            ->filter(fn ($producto) => (float) ($producto->existencia_total ?? 0) <= (float) $producto->stock_minimo)
            ->count();

        $proximosAVencer = InventarioExistencia::where('cantidad_disponible', '>', 0)
            ->whereHas('lote', fn ($query) => $query->whereBetween('fecha_vencimiento', [now()->toDateString(), now()->addDays(30)->toDateString()]))
            ->count();

        $vencidos = InventarioExistencia::where('cantidad_disponible', '>', 0)
            ->whereHas('lote', fn ($query) => $query->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', now()->toDateString()))
            ->count();

        return view('livewire.inventario.dashboard', [
            'productos' => $productos,
            'existenciasBajas' => $existenciasBajas,
            'proximosAVencer' => $proximosAVencer,
            'vencidos' => $vencidos,
            'ultimasEntradas' => InventarioEntrada::with('bodega')->latest()->limit(5)->get(),
            'ultimosMovimientos' => InventarioKardex::with(['producto', 'bodega', 'lote'])->latest()->limit(8)->get(),
        ]);
    }
}
