<?php

namespace App\Livewire\Revision;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Actividad\Actividad;
use App\Models\Poa\PoaDepto;
use App\Models\Tareas\Tarea;
use App\Models\Presupuestos\Presupuesto;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ActividadesRevision extends Component
{
    use WithPagination;

    private const ESTADOS_REVISION = ['REVISION', 'REFORMULACION', 'APROBADO', 'RECHAZADO'];
    private const PAGE_NAME = 'actividadesRevisionPage';

    public $departamentoId;
    public $resumen = [];
    public $poaYears = [];
    public $buscarActividad = '';
    public $poaYear = '';
    public $perPage = 10;
    

    public function mount($departamentoId, $poaYear = null)
    {
        $this->departamentoId = $departamentoId;

        $this->poaYears = PoaDepto::where('idDepartamento', $departamentoId)
            ->join('poas', 'poa_deptos.idPoa', '=', 'poas.id')
            ->pluck('poas.anio')
            ->unique()
            ->sortDesc()
            ->toArray();
        
        $this->poaYear = $poaYear ?? ($this->poaYears[0] ?? '');
        $this->cargarResumen();
    }

    public function updatingBuscarActividad()
    {
        $this->resetPage(self::PAGE_NAME);
    }

    public function updatedPoaYear()
    {
        $this->resetPage(self::PAGE_NAME);
        $this->cargarResumen();
    }

    public function updatedPerPage()
    {
        $this->resetPage(self::PAGE_NAME);
    }

    public function cargarResumen()
    {
        $poaDepto = PoaDepto::where('idDepartamento', $this->departamentoId)
            ->when($this->poaYear, function ($query) {
                $query->whereHas('poa', fn ($poa) => $poa->where('anio', $this->poaYear));
            })
            ->first();
        $nombreDepartamento = $poaDepto?->departamento?->name ?? '-';
        $presupuesto = $planificado = $numActividades = $porcentaje = 0;

        if ($poaDepto) {
            $presupuesto = $poaDepto->techoDeptos->sum('monto');

            $actividades = Actividad::where('idPoaDepto', $poaDepto->id)
                ->whereIn('estado', self::ESTADOS_REVISION)
                ->get();

            $numActividades = $actividades->count();

            $idTareas = Tarea::whereIn('idActividad', $actividades->pluck('id'))
                ->where('isPresupuesto', true)
                ->pluck('id');

            $planificado = Presupuesto::whereIn('idtarea', $idTareas)->sum('total');
            $porcentaje = $presupuesto > 0 ? round(($planificado * 100) / $presupuesto, 1) : 0;
        }

        $this->resumen = compact('nombreDepartamento', 'presupuesto', 'planificado', 'numActividades', 'porcentaje');
    }

  public function render()
    {
        $actividades = Actividad::with(['tipo', 'categoria'])
            ->where('idDeptartamento', $this->departamentoId)
            ->whereIn('estado', self::ESTADOS_REVISION)
            ->when($this->buscarActividad, fn($q) =>
                $q->where('nombre', 'like', '%' . $this->buscarActividad . '%')
            )
            ->when($this->poaYear, function($q) {  
                $q->whereHas('poa', function($q2) {
                    $q2->where('anio', $this->poaYear);
                });
            })
            ->orderBy('nombre')
            ->paginate($this->perPage, pageName: self::PAGE_NAME);

        return view('livewire.Revision.actividades-revision', [
            'actividades' => $actividades,
            'resumen' => $this->resumen,
            'poaYears' => $this->poaYears,
        ]);
    }
}
