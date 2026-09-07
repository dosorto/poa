<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioSalida;
use App\Services\Inventario\InventarioService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Salidas extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showConfirmarModal = false;
    public ?int $salidaConfirmarId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function abrirConfirmacion(int $id): void
    {
        $salida = InventarioSalida::findOrFail($id);

        if ($salida->estado !== 'borrador') {
            session()->flash('error', 'Solo se pueden confirmar salidas en borrador.');
            return;
        }

        $this->salidaConfirmarId = $salida->id;
        $this->showConfirmarModal = true;
    }

    public function cerrarConfirmacion(): void
    {
        $this->showConfirmarModal = false;
        $this->salidaConfirmarId = null;
    }

    public function confirmar(InventarioService $service): void
    {
        $service->confirmarSalida(InventarioSalida::findOrFail($this->salidaConfirmarId));
        $this->cerrarConfirmacion();
        session()->flash('message', 'Salida confirmada y registrada en kardex.');
    }

    public function anular(int $id, InventarioService $service): void
    {
        $service->anularSalida(InventarioSalida::findOrFail($id));
        session()->flash('message', 'Salida anulada con movimiento reverso.');
    }

    public function render()
    {
        $search = '%' . $this->search . '%';

        return view('livewire.inventario.salidas', [
            'salidas' => InventarioSalida::with(['bodega', 'actaEntrega', 'detalles.producto'])
                ->where(function ($query) use ($search) {
                    $query->where('numero_salida', 'like', $search)
                        ->orWhere('tipo_salida', 'like', $search);
                })
                ->latest()
                ->paginate(10),
        ]);
    }
}
