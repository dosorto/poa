<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioExistencia;
use App\Models\Inventario\InventarioProducto;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Existencias extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $bodega_id = null;
    public ?int $producto_id = null;
    public string $estado = '';

    public function updating($name): void
    {
        if (in_array($name, ['search', 'bodega_id', 'producto_id', 'estado'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $search = '%' . $this->search . '%';

        return view('livewire.inventario.existencias', [
            'existencias' => InventarioExistencia::with(['bodega', 'producto.unidadMedida', 'lote'])
                ->when($this->bodega_id, fn ($query) => $query->where('bodega_id', $this->bodega_id))
                ->when($this->producto_id, fn ($query) => $query->where('producto_id', $this->producto_id))
                ->when($this->estado, fn ($query) => $query->whereHas('lote', fn ($lote) => $lote->where('estado', $this->estado)))
                ->whereHas('producto', fn ($query) => $query->where('nombre', 'like', $search)->orWhere('codigo_interno', 'like', $search))
                ->latest()
                ->paginate(15),
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'productos' => InventarioProducto::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
