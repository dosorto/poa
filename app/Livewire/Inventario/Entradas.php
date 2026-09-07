<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioEntrada;
use App\Services\Inventario\InventarioService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Entradas extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showConfirmarModal = false;
    public ?int $entradaConfirmarId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function abrirConfirmacion(int $id): void
    {
        $entrada = InventarioEntrada::findOrFail($id);

        if ($entrada->estado !== 'borrador') {
            session()->flash('error', 'Solo se pueden confirmar entradas en borrador.');
            return;
        }

        $this->entradaConfirmarId = $entrada->id;
        $this->showConfirmarModal = true;
    }

    public function cerrarConfirmacion(): void
    {
        $this->showConfirmarModal = false;
        $this->entradaConfirmarId = null;
    }

    public function confirmar(InventarioService $service): void
    {
        $service->confirmarEntrada(InventarioEntrada::findOrFail($this->entradaConfirmarId));
        $this->cerrarConfirmacion();
        session()->flash('message', 'Entrada confirmada y registrada en kardex.');
    }

    public function anular(int $id, InventarioService $service): void
    {
        $service->anularEntrada(InventarioEntrada::findOrFail($id));
        session()->flash('message', 'Entrada anulada con movimiento reverso.');
    }

    public function render()
    {
        $search = '%' . $this->search . '%';

        return view('livewire.inventario.entradas', [
            'entradas' => InventarioEntrada::with(['bodega', 'requisicion', 'detalles.producto'])
                ->where(function ($query) use ($search) {
                    $query->where('numero_entrada', 'like', $search)
                        ->orWhere('numero_factura', 'like', $search)
                        ->orWhere('proveedor', 'like', $search);
                })
                ->latest()->paginate(10),
        ]);
    }
}
