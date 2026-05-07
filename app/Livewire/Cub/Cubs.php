<?php

namespace App\Livewire\Cub;

use App\Models\Cubs\Cub;
use App\Models\UnidadEjecutora\UnidadEjecutora;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Cubs extends Component
{
    use WithPagination;

    public ?int $cubId = null;
    public string $IDUNSPSC = '';
    public string $descripcion_esp = '';
    public string $descripcion_regional = '';
    public int|string|null $idUE = null;
    public bool $isModalOpen = false;
    public bool $showDeleteModal = false;
    public bool $showErrorModal = false;
    public string $errorMessage = '';
    public ?Cub $cubToDelete = null;

    public string $search = '';
    public int|string $perPage = 10;
    public string $sortField = 'id';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $rules = [
        'IDUNSPSC' => 'required|max:50',
        'descripcion_esp' => 'required|max:1000',
        'descripcion_regional' => 'nullable|max:1000',
        'idUE' => 'nullable|exists:unidad_ejecutora,id',
    ];

    protected $messages = [
        'IDUNSPSC.required' => 'El código UNSPSC es obligatorio.',
        'IDUNSPSC.max' => 'El código UNSPSC no puede exceder 50 caracteres.',
        'descripcion_esp.required' => 'La descripción en español es obligatoria.',
        'descripcion_esp.max' => 'La descripción en español no puede exceder 1000 caracteres.',
        'descripcion_regional.max' => 'La descripción regional no puede exceder 1000 caracteres.',
        'idUE.exists' => 'La unidad ejecutora seleccionada no existe.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetInputFields(): void
    {
        $this->cubId = null;
        $this->IDUNSPSC = '';
        $this->descripcion_esp = '';
        $this->descripcion_regional = '';
        $this->idUE = null;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function store(): void
    {
        $this->validate();

        Cub::updateOrCreate(['id' => $this->cubId], [
            'IDUNSPSC' => $this->IDUNSPSC,
            'descripcion_esp' => $this->descripcion_esp,
            'descripcion_regional' => $this->descripcion_regional,
            'idUE' => $this->idUE ?: null,
        ]);

        session()->flash('message', $this->cubId
            ? 'CUB actualizado correctamente.'
            : 'CUB creado correctamente.');

        $this->closeModal();
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $cub = Cub::findOrFail($id);

        $this->cubId = $cub->id;
        $this->IDUNSPSC = $cub->IDUNSPSC;
        $this->descripcion_esp = $cub->descripcion_esp;
        $this->descripcion_regional = $cub->descripcion_regional ?? '';
        $this->idUE = $cub->idUE;
        $this->isModalOpen = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->cubToDelete = Cub::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        try {
            if ($this->cubToDelete) {
                $this->cubToDelete->delete();
                session()->flash('message', 'CUB eliminado correctamente.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo eliminar el CUB.');
        }

        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->cubToDelete = null;
    }

    public function closeErrorModal(): void
    {
        $this->showErrorModal = false;
        $this->errorMessage = '';
    }

    public function render()
    {
        $cubs = Cub::query()
            ->with(['unidadEjecutora'])
            ->when($this->search, function ($query) {
                $s = '%' . $this->search . '%';

                $query->where('IDUNSPSC', 'like', $s)
                    ->orWhere('descripcion_esp', 'like', $s)
                    ->orWhere('descripcion_regional', 'like', $s);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate((int) $this->perPage);

        $unidadesEjecutoras = UnidadEjecutora::orderBy('name')->get();

        return view('livewire.cub.cubs', [
            'cubs' => $cubs,
            'unidadesEjecutoras' => $unidadesEjecutoras,
        ]);
    }
}
