<?php

namespace App\Livewire\Consolidado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Actividad\Actividad;
use App\Models\Dimension\Dimension;
use App\Models\Poa\Poa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Consolidado extends Component
{
    use WithPagination;

    public $anio;
    public $dimensionId;
    public $departamentoId;
    public $expandedRow = null;
    
    public $anios = [];
    public $dimensiones = [];
    public $departamentos = [];
    public $departamentoIds = [];

    public function mount()
    {
        // Obtener años disponibles
        $this->anios = Poa::distinct()->orderBy('anio', 'desc')->pluck('anio')->toArray();
        $this->anio = count($this->anios) > 0 ? $this->anios[0] : date('Y');
        
        // Obtener dimensiones
        $this->dimensiones = Dimension::orderBy('nombre')->get();
        
        $this->cargarDepartamentosEmpleado();
    }

    public function cargarDepartamentosEmpleado()
    {
        $empleado = Auth::user()?->empleado;

        if (!$empleado) {
            $this->departamentos = [];
            $this->departamentoIds = [];
            $this->departamentoId = '';
            return;
        }

        $departamentos = $empleado->departamentos()
            ->orderBy('name')
            ->get(['departamentos.id', 'departamentos.name']);

        $this->departamentos = $departamentos->toArray();
        $this->departamentoIds = $departamentos->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        if (!$this->departamentoId || !in_array((int) $this->departamentoId, $this->departamentoIds, true)) {
            $this->departamentoId = $this->departamentoIds[0] ?? '';
        }
    }

    public function updatingDepartamentoId()
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatedDepartamentoId($value)
    {
        if (!$value || !in_array((int) $value, $this->departamentoIds, true)) {
            $this->departamentoId = $this->departamentoIds[0] ?? '';
        }
    }

    public function updatingAnio()
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatingDimensionId()
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function toggleExpand($actividadId)
    {
        if ($this->expandedRow === $actividadId) {
            $this->expandedRow = null;
        } else {
            $this->expandedRow = $actividadId;
        }
    }

    public function toggleSPI($actividadId)
    {
        $actividad = Actividad::find($actividadId);
        if ($actividad) {
            $actividad->uploadedIntoSPI = !$actividad->uploadedIntoSPI;
            $actividad->save();
            
            $this->dispatch('spi-updated', ['message' => 'Estado SPI actualizado correctamente']);
        }
    }

    public function getActividadesProperty()
    {
        $query = Actividad::with([
            'departamento',
            'poa',
            'unidadEjecutora',
            'categoria',
            'resultado.area.objetivo.dimension',
            'indicadores.planificacions',
            'tareas.presupuestos'
        ]);

        // Filtrar por año
        if ($this->anio) {
            $query->whereHas('poa', function ($q) {
                $q->where('anio', $this->anio);
            });
        }

        // Filtrar por dimensión
        if ($this->dimensionId) {
            $query->whereHas('resultado.area.objetivo.dimension', function ($q) {
                $q->where('id', $this->dimensionId);
            });
        }

        // Limitar siempre a los departamentos asignados al empleado autenticado
        if (empty($this->departamentoIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('idDeptartamento', $this->departamentoIds);
        }

        // Filtrar por departamento seleccionado dentro de los asignados
        if ($this->departamentoId) {
            $query->where('idDeptartamento', $this->departamentoId);
        }

        return $query->orderBy('correlativo')->paginate(20);
    }

    public function getActividadDetalleProperty()
    {
        if (!$this->expandedRow) {
            return null;
        }

        $query = Actividad::with([
            'departamento',
            'poa',
            'unidadEjecutora.institucion',
            'categoria',
            'tipo',
            'resultado.area.objetivo.dimension',
            'indicadores.planificacions.mes',
            'tareas.presupuestos.mes',
            'tareas.presupuestos.grupoGasto',
            'tareas.presupuestos.objetoGasto',
            'tareas.presupuestos.fuente',
            'empleados'
        ]);

        if (empty($this->departamentoIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('idDeptartamento', $this->departamentoIds);
        }

        if ($this->departamentoId) {
            $query->where('idDeptartamento', $this->departamentoId);
        }

        return $query->find($this->expandedRow);
    }

    public function render()
    {
        return view('livewire.consolidado.consolidado', [
            'actividades' => $this->actividades,
            'actividadDetalle' => $this->actividadDetalle,
            'departamentos' => $this->departamentos,
            'dimensiones' => $this->dimensiones,
            'anios' => $this->anios
        ]);
    }
}
