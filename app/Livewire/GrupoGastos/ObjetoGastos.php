<?php

namespace App\Livewire\GrupoGastos;

use App\Models\GrupoGastos\GrupoGasto;
use App\Models\GrupoGastos\ObjetoGasto;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ObjetoGastos extends Component
{
    use WithPagination;

    public $nombre;
    public $descripcion;
    public $identificador;
    public $idgrupo;
    public $objetoGastoId;
    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $isModalOpen = false;
    public $showDeleteModal = false;
    public $objetoGastoToDelete;
    public $errorMessage = '';
    public $showErrorModal = false;

    protected $rules = [
        'nombre' => 'required|min:3|max:255',
        'descripcion' => 'required|min:3',
        'identificador' => 'required|max:255',
        'idgrupo' => 'required|exists:grupogastos,id',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

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

    public function create()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function resetInputFields()
    {
        $this->objetoGastoId = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->identificador = '';
        $this->idgrupo = '';
        $this->resetErrorBag();
    }

    public function store()
    {
        $this->validate();

        try {
            $query = ObjetoGasto::where('identificador', $this->identificador);

            if ($this->objetoGastoId) {
                $query->where('id', '!=', $this->objetoGastoId);
            }

            if ($query->exists()) {
                $this->addError('identificador', 'El identificador ya está en uso.');
                return;
            }

            ObjetoGasto::updateOrCreate(['id' => $this->objetoGastoId], [
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'identificador' => $this->identificador,
                'idgrupo' => $this->idgrupo,
            ]);

            session()->flash('message', $this->objetoGastoId
                ? 'Objeto de gasto actualizado correctamente.'
                : 'Objeto de gasto creado correctamente.');

            $this->isModalOpen = false;
            $this->resetInputFields();
        } catch (\Exception $e) {
            $this->showError('Error al guardar el objeto de gasto: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $objetoGasto = ObjetoGasto::findOrFail($id);
            $this->objetoGastoId = $id;
            $this->nombre = $objetoGasto->nombre;
            $this->descripcion = $objetoGasto->descripcion;
            $this->identificador = $objetoGasto->identificador;
            $this->idgrupo = $objetoGasto->idgrupo;

            $this->isModalOpen = true;
        } catch (\Exception $e) {
            $this->showError('Error al cargar el objeto de gasto: ' . $e->getMessage());
        }
    }

    public function confirmDelete($id)
    {
        try {
            $this->objetoGastoToDelete = ObjetoGasto::findOrFail($id);
            $this->showDeleteModal = true;
        } catch (\Exception $e) {
            $this->showError('Error al cargar el objeto de gasto: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        try {
            if ($this->objetoGastoToDelete) {
                $this->objetoGastoToDelete->delete();
                session()->flash('message', 'Objeto de gasto eliminado correctamente.');
            }
        } catch (\Exception $e) {
            $this->showError('No se pudo eliminar el objeto de gasto: ' . $e->getMessage());
        }

        $this->closeDeleteModal();
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->objetoGastoToDelete = null;
    }

    public function showError($message)
    {
        $this->errorMessage = $message;
        $this->showErrorModal = true;
    }

    public function hideError()
    {
        $this->showErrorModal = false;
        $this->errorMessage = '';
    }

    public function render()
    {
        $objetosGasto = ObjetoGasto::with('grupoGasto')
            ->where(function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->search . '%')
                    ->orWhere('identificador', 'like', '%' . $this->search . '%')
                    ->orWhereHas('grupoGasto', function ($grupoQuery) {
                        $grupoQuery->where('nombre', 'like', '%' . $this->search . '%')
                            ->orWhere('identificador', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $gruposGasto = GrupoGasto::orderBy('identificador')->get()->map(function ($grupo) {
            return [
                'value' => $grupo->id,
                'text' => $grupo->identificador . ' - ' . $grupo->nombre,
            ];
        })->toArray();

        return view('livewire.grupo-gastos.objeto-gastos.objeto-gastos', [
            'objetosGasto' => $objetosGasto,
            'gruposGasto' => $gruposGasto,
        ]);
    }
}
