<?php

namespace App\Livewire\UnidadEjecutora;

use App\Models\UnidadEjecutora\UnidadEjecutora;
use App\Models\Instituciones\Institucion;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class UnidadesEjecutoras extends Component
{
    use WithPagination;

    protected string $layout = 'layouts.app';

    public $name;
    public $descripcion;
    public $estructura;
    public $idInstitucion;
    public $idAsistenteEstrategico;
    public $idAdministrador;
    public $idEncargadoCompra;
    public $idDirectorDecano;
    public $unidadEjecutoraId;
    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $isModalOpen = false;
    public $showDeleteModal = false;
    public $unidadEjecutoraToDelete;
    public $errorMessage = '';
    public $showErrorModal = false;
    public $usersIniciales = [];

    protected $rules = [
        'name' => 'required|min:3|max:255',
        'descripcion' => 'nullable|max:1000',
        'estructura' => 'required|max:50',
        'idInstitucion' => 'required|exists:institucions,id',
        'idAsistenteEstrategico' => 'nullable|exists:users,id',
        'idAdministrador' => 'nullable|exists:users,id',
        'idEncargadoCompra' => 'nullable|exists:users,id',
        'idDirectorDecano' => 'nullable|exists:users,id',
    ];

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'name.min' => 'El nombre debe tener al menos 3 caracteres.',
        'name.max' => 'El nombre no puede exceder 255 caracteres.',
        'descripcion.max' => 'La descripción no puede exceder 1000 caracteres.',
        'estructura.required' => 'La estructura es obligatoria.',
        'estructura.max' => 'La estructura no puede exceder 50 caracteres.',
        'idInstitucion.required' => 'La institución es obligatoria.',
        'idInstitucion.exists' => 'La institución seleccionada no existe.',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->usersIniciales = $this->searchUsers('');
    }

    public function searchUsers($search = '')
    {
        return User::query()
            ->whereNotNull('idEmpleado')
            ->with(['empleado:id,nombre,apellido'])
            ->when($search, function ($query) use ($search) {
                $s = '%' . $search . '%';
                $query->whereHas('empleado', function ($q) use ($s) {
                    $q->where('nombre', 'like', $s)
                        ->orWhere('apellido', 'like', $s);
                })->orWhere('name', 'like', $s)
                  ->orWhere('email', 'like', $s);
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'email', 'idEmpleado'])
            ->map(function ($user) {
                $empleado = $user->empleado;
                $nombreEmpleado = $empleado
                    ? preg_replace('/\s+/', ' ', trim($empleado->nombre . ' ' . $empleado->apellido))
                    : $user->name;

                return [
                    'id' => $user->id,
                    'text' => $nombreEmpleado,
                ];
            })
            ->values()
            ->toArray();
    }

    public function updatedName($value)
    {
        $this->name = is_array($value) ? '' : $value;
    }

    public function updatedDescripcion($value)
    {
        $this->descripcion = is_array($value) ? '' : $value;
    }

    public function updatedEstructura($value)
    {
        $this->estructura = is_array($value) ? '' : $value;
    }

    public function updatedIdInstitucion($value)
    {
        $this->idInstitucion = is_array($value) ? null : $value;
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
        $this->name = '';
        $this->descripcion = '';
        $this->estructura = '';
        $this->idInstitucion = '';
        $this->idAsistenteEstrategico = null;
        $this->idAdministrador = null;
        $this->idEncargadoCompra = null;
        $this->idDirectorDecano = null;
        $this->unidadEjecutoraId = null;
        $this->resetValidation();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function openModal()
    {
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }

    public function store()
    {
        // Asegurar que los campos sean strings
        $this->name = is_array($this->name) ? '' : trim($this->name ?? '');
        $this->descripcion = is_array($this->descripcion) ? '' : trim($this->descripcion ?? '');
        $this->estructura = is_array($this->estructura) ? '' : trim($this->estructura ?? '');
        $this->idInstitucion = is_array($this->idInstitucion) ? null : $this->idInstitucion;
        $this->idAsistenteEstrategico = is_array($this->idAsistenteEstrategico) ? null : ($this->idAsistenteEstrategico ?: null);
        $this->idAdministrador = is_array($this->idAdministrador) ? null : ($this->idAdministrador ?: null);
        $this->idEncargadoCompra = is_array($this->idEncargadoCompra) ? null : ($this->idEncargadoCompra ?: null);
        $this->idDirectorDecano = is_array($this->idDirectorDecano) ? null : ($this->idDirectorDecano ?: null);

        $this->validate();

        try {
            UnidadEjecutora::updateOrCreate(['id' => $this->unidadEjecutoraId], [
                'name' => $this->name,
                'descripcion' => $this->descripcion,
                'estructura' => $this->estructura,
                'idInstitucion' => $this->idInstitucion,
                'idAsistenteEstrategico' => $this->idAsistenteEstrategico,
                'idAdministrador' => $this->idAdministrador,
                'idEncargadoCompra' => $this->idEncargadoCompra,
                'idDirectorDecano' => $this->idDirectorDecano,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            session()->flash('message', 
                $this->unidadEjecutoraId ? 'Unidad Ejecutora actualizada correctamente.' : 'Unidad Ejecutora creada correctamente.'
            );

            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar la Unidad Ejecutora: ' . $e->getMessage();
            $this->showErrorModal = true;
        }
    }

    public function edit($id)
    {
        $unidadEjecutora = UnidadEjecutora::findOrFail($id);
        $this->unidadEjecutoraId = $id;
        $this->name = $unidadEjecutora->name;
        $this->descripcion = $unidadEjecutora->descripcion;
        $this->estructura = $unidadEjecutora->estructura;
        $this->idInstitucion = $unidadEjecutora->idInstitucion;
        $this->idAsistenteEstrategico = $unidadEjecutora->idAsistenteEstrategico ?? null;
        $this->idAdministrador = $unidadEjecutora->idAdministrador ?? null;
        $this->idEncargadoCompra = $unidadEjecutora->idEncargadoCompra ?? null;
        $this->idDirectorDecano = $unidadEjecutora->idDirectorDecano ?? null;

        $this->openModal();
    }

    public function confirmDelete($id)
    {
        $this->unidadEjecutoraToDelete = UnidadEjecutora::with('institucion')->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        try {
            // Verificar si tiene empleados asociados
            if ($this->unidadEjecutoraToDelete->empleados()->exists()) {
                $this->errorMessage = 'No se puede eliminar esta Unidad Ejecutora porque tiene empleados asociados.';
                $this->showDeleteModal = false;
                $this->showErrorModal = true;
                return;
            }

            // Verificar si tiene departamentos asociados
            if ($this->unidadEjecutoraToDelete->departamentos()->exists()) {
                $this->errorMessage = 'No se puede eliminar esta Unidad Ejecutora porque tiene departamentos asociados.';
                $this->showDeleteModal = false;
                $this->showErrorModal = true;
                return;
            }

            // Verificar si tiene techos asignados
            if ($this->unidadEjecutoraToDelete->techoUes()->exists()) {
                $this->errorMessage = 'No se puede eliminar esta Unidad Ejecutora porque tiene techos presupuestarios asignados.';
                $this->showDeleteModal = false;
                $this->showErrorModal = true;
                return;
            }

            $this->unidadEjecutoraToDelete->delete();
            
            session()->flash('message', 'Unidad Ejecutora eliminada correctamente.');
            $this->showDeleteModal = false;
            $this->resetPage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar la Unidad Ejecutora: ' . $e->getMessage();
            $this->showDeleteModal = false;
            $this->showErrorModal = true;
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->unidadEjecutoraToDelete = null;
    }

    public function closeErrorModal()
    {
        $this->showErrorModal = false;
        $this->errorMessage = '';
    }

    public function render()
    {
        // Obtener institución del usuario autenticado
        $user = auth()->user();
        $userInstitucionId = $user->empleado?->unidadEjecutora?->idInstitucion;
        $userUE = $user->empleado?->idUnidadEjecutora;

        $unidadesEjecutoras = UnidadEjecutora::with([
                'institucion',
                'asistenteEstrategico',
                'administrador',
                'encargadoCompra',
                'directorDecano',
            ])
            // Filtrar por institución del usuario (solo muestra UEs de su institución)
            ->when($userInstitucionId, function ($query) use ($userInstitucionId) {
                $query->where('idInstitucion', $userInstitucionId);
            })
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('descripcion', 'like', '%' . $this->search . '%')
                      ->orWhere('estructura', 'like', '%' . $this->search . '%')
                      ->orWhereHas('institucion', function ($q) {
                          $q->where('nombre', 'like', '%' . $this->search . '%');
                      });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // Filtrar instituciones (solo la del usuario)
        $instituciones = $userInstitucionId 
            ? Institucion::where('id', $userInstitucionId)->orderBy('nombre')->get()
            : Institucion::orderBy('nombre')->get();

        return view('livewire.unidad-ejecutora.unidades-ejecutoras', [
            'unidadesEjecutoras' => $unidadesEjecutoras,
            'instituciones' => $instituciones,
        ]);
    }
}
