<?php

namespace App\Livewire\Tarea;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tareas\TareaHistorico;
use App\Models\Tareas\RecursoDetalleTecnico as RecursoDetalleTecnicoModel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class RecursoDetalleTecnico extends Component
{
    use WithPagination;

    public $recursoId;
    public $recursoNombre;
    public $nombre;
    public $estado = true;
    public $detalleId;
    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $showModal = false;
    public $showDeleteModal = false;
    public $detalleToDelete;
    public $errorMessage = '';
    public $showErrorModal = false;
    public $isEditing = false;

    protected $rules = [
        'nombre' => 'required|min:3|max:255',
    ];

    protected $messages = [
        'nombre.required' => 'El nombre del detalle técnico es obligatorio.',
        'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        'nombre.max' => 'El nombre no puede exceder 255 caracteres.',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount($recursoId)
    {
        $recurso = TareaHistorico::findOrFail($recursoId);
        $this->recursoId = $recursoId;
        $this->recursoNombre = $recurso->nombre;
        
        // Verificar que se carguen los detalles
        $detalles = RecursoDetalleTecnicoModel::where('id_tareas_historicos', $recursoId)->count();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetInputFields()
    {
        $this->nombre = '';
        $this->estado = true;
        $this->detalleId = null;
        $this->resetValidation();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInputFields();
    }

    public function store()
    {
        $this->validate();

        try {
            RecursoDetalleTecnicoModel::updateOrCreate(
                ['id' => $this->detalleId],
                [
                    'id_tareas_historicos' => $this->recursoId,
                    'nombre' => $this->nombre,
                    'estado' => $this->estado,
                ]
            );

            $mensaje = $this->isEditing ? 'Detalle técnico actualizado correctamente.' : 'Detalle técnico creado correctamente.';
            session()->flash('message', $mensaje);
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar: ' . $e->getMessage();
            $this->showErrorModal = true;
        }
    }

    public function edit($id)
    {
        $detalle = RecursoDetalleTecnicoModel::findOrFail($id);
        $this->detalleId = $detalle->id;
        $this->nombre = $detalle->nombre;
        $this->estado = $detalle->estado;
        $this->isEditing = true;
        $this->openModal();
    }

    public function confirmDelete($id)
    {
        $this->detalleToDelete = RecursoDetalleTecnicoModel::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        try {
            $this->detalleToDelete->delete();
            session()->flash('message', 'Detalle técnico eliminado correctamente.');
            $this->showDeleteModal = false;
            $this->resetPage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar: ' . $e->getMessage();
            $this->showDeleteModal = false;
            $this->showErrorModal = true;
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->detalleToDelete = null;
    }

    public function closeErrorModal()
    {
        $this->showErrorModal = false;
    }

    public function render()
    {
        $detalles = RecursoDetalleTecnicoModel::where('id_tareas_historicos', $this->recursoId)
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.tarea.recurso-detalle-tecnico', [
            'detalles' => $detalles,
        ]);
    }
}
