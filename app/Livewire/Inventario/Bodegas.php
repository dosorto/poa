<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioBodega;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Bodegas extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $bodegaId = null;
    public string $nombre = '';
    public ?string $ubicacion = null;
    public ?int $responsable_id = null;
    public bool $activo = true;

    protected function rules(): array
    {
        return [
            'nombre' => 'required|string|min:2|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'responsable_id' => 'nullable|exists:users,id',
            'activo' => 'boolean',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $bodega = InventarioBodega::findOrFail($id);
        $this->bodegaId = $bodega->id;
        $this->nombre = $bodega->nombre;
        $this->ubicacion = $bodega->ubicacion;
        $this->responsable_id = $bodega->responsable_id;
        $this->activo = (bool) $bodega->activo;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        InventarioBodega::updateOrCreate(['id' => $this->bodegaId], [
            'nombre' => $this->nombre,
            'ubicacion' => $this->ubicacion,
            'responsable_id' => $this->responsable_id,
            'activo' => $this->activo,
        ]);

        session()->flash('message', 'Bodega guardada correctamente.');
        $this->closeModal();
    }

    public function toggleActivo(int $id): void
    {
        $bodega = InventarioBodega::findOrFail($id);
        $bodega->update(['activo' => ! $bodega->activo]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['bodegaId', 'nombre', 'ubicacion', 'responsable_id']);
        $this->activo = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.inventario.bodegas', [
            'bodegas' => InventarioBodega::with('responsable')
                ->where('nombre', 'like', '%' . $this->search . '%')
                ->orWhere('ubicacion', 'like', '%' . $this->search . '%')
                ->latest()
                ->paginate(10),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
