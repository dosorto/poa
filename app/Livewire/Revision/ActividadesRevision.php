<?php

namespace App\Livewire\Revision;

use Livewire\Component;
use App\Models\Actividad\Actividad;
use App\Models\Poa\PoaDepto;
use App\Models\Tareas\Tarea;
use App\Models\Presupuestos\Presupuesto;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ActividadesRevision extends Component
{
    private const ESTADOS_REVISION = ['REVISION', 'REFORMULACION', 'APROBADO', 'RECHAZADO'];

    public $departamentoId;
    public $resumen = [];
    public $poaYears = [];
    public $buscarActividad = '';
    public $poaYear = '';
    

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
    }

    public function updatedPoaYear()
    {
        $this->cargarResumen();
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
        $fuentesResumen = collect([
            ['identificador' => '11', 'monto' => 0, 'actividades' => 0],
            ['identificador' => '12', 'monto' => 0, 'actividades' => 0],
            ['identificador' => '12B', 'monto' => 0, 'actividades' => 0],
        ])->keyBy('identificador');

        if ($poaDepto) {
            $presupuesto = $poaDepto->techoDeptos->sum('monto');

            $actividades = Actividad::where('idPoaDepto', $poaDepto->id)
                ->whereIn('estado', self::ESTADOS_REVISION)
                ->get();

            $numActividades = $actividades->count();

            $idTareas = Tarea::whereIn('idActividad', $actividades->pluck('id'))
                ->where('isPresupuesto', true)
                ->whereHas('actividad', function ($query) {
                    $query->whereIn('estado', self::ESTADOS_REVISION);
                })
                ->pluck('id');

            $planificado = Presupuesto::whereIn('idtarea', $idTareas)
                ->whereHas('tarea', function ($query) {
                    $query->whereHas('actividad', function ($actividadQuery) {
                        $actividadQuery->whereIn('estado', self::ESTADOS_REVISION);
                    });
                })
                ->sum('total');

            $fuentesPlanificadas = Presupuesto::query()
                ->join('fuente', 'presupuestos.idfuente', '=', 'fuente.id')
                ->join('tareas', 'presupuestos.idtarea', '=', 'tareas.id')
                ->join('actividads', 'tareas.idActividad', '=', 'actividads.id')
                ->whereIn('actividads.id', $actividades->pluck('id'))
                ->whereIn('fuente.identificador', ['11', '12', '12B'])
                ->groupBy('fuente.identificador')
                ->selectRaw('fuente.identificador as identificador, COALESCE(SUM(presupuestos.total), 0) as monto, COUNT(DISTINCT actividads.id) as actividades')
                ->get();

            foreach ($fuentesPlanificadas as $fuentePlanificada) {
                $fuentesResumen->put($fuentePlanificada->identificador, [
                    'identificador' => $fuentePlanificada->identificador,
                    'monto' => (float) $fuentePlanificada->monto,
                    'actividades' => (int) $fuentePlanificada->actividades,
                ]);
            }

            $porcentaje = $presupuesto > 0 ? round(($planificado * 100) / $presupuesto, 1) : 0;
        }

        $this->resumen = compact('nombreDepartamento', 'presupuesto', 'planificado', 'numActividades', 'porcentaje');
        $this->resumen['fuentes'] = $fuentesResumen->values()->all();
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
            ->get();

        return view('livewire.Revision.actividades-revision', [
            'actividades' => $actividades,
            'resumen' => $this->resumen,
            'poaYears' => $this->poaYears,
        ]);
    }
}
