<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioKardex;
use App\Models\Inventario\InventarioProducto;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Kardex extends Component
{
    use WithPagination;

    public ?string $fecha_inicio = null;
    public ?string $fecha_fin = null;
    public ?int $producto_id = null;
    public ?int $bodega_id = null;
    public string $tipo_movimiento = '';
    public string $referencia = '';
    public int $perPage = 10;

    public function updating($name): void
    {
        if (in_array($name, ['fecha_inicio', 'fecha_fin', 'producto_id', 'bodega_id', 'tipo_movimiento', 'referencia', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $perPage = in_array($this->perPage, [10, 15, 25, 50], true) ? $this->perPage : 10;

        return view('livewire.inventario.kardex', [
            'movimientos' => InventarioKardex::with(['bodega', 'producto', 'lote', 'usuario'])
                ->when($this->fecha_inicio, fn ($query) => $query->whereDate('fecha_movimiento', '>=', $this->fecha_inicio))
                ->when($this->fecha_fin, fn ($query) => $query->whereDate('fecha_movimiento', '<=', $this->fecha_fin))
                ->when($this->producto_id, fn ($query) => $query->where('producto_id', $this->producto_id))
                ->when($this->bodega_id, fn ($query) => $query->where('bodega_id', $this->bodega_id))
                ->when($this->tipo_movimiento, fn ($query) => $query->where('tipo_movimiento', $this->tipo_movimiento))
                ->when($this->referencia, fn ($query) => $query->where('referencia', 'like', '%' . $this->referencia . '%'))
                ->latest('fecha_movimiento')
                ->paginate($perPage),
            'bodegas' => InventarioBodega::orderBy('nombre')->get(),
            'productos' => InventarioProducto::orderBy('nombre')->get(),
        ]);
    }
}
