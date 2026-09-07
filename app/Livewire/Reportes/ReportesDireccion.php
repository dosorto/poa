<?php

namespace App\Livewire\Reportes;

use App\Models\Actividad\Actividad;
use App\Models\Actas\ActaEntrega;
use App\Models\Departamento\Departamento;
use App\Models\EjecucionPresupuestaria\DetalleEjecucionPresupuestaria;
use App\Models\Poa\Poa;
use App\Models\Presupuestos\Presupuesto;
use App\Models\Requisicion\DetalleRequisicion;
use App\Models\Requisicion\Requisicion;
use App\Models\TechoUes\TechoDepto;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReportesDireccion extends Component
{
    public $anioSeleccionado = null;
    public $departamentoSeleccionado = '';
    public $poas = [];
    public $departamentos = [];
    public $resumen = [];
    public $actividadesPorEstado = [];
    public $requisicionesPorEstado = [];
    public $topDepartamentos = [];
    public $alertasPlanificacion = [];
    public $alertasRequisiciones = [];
    public $seguimiento = [];
    public $finanzas = [];
    public $poaActual = null;
    public $estadoActividadSeleccionado = null;
    public $estadoRequisicionSeleccionado = null;
    public $detalleActividadesEstado = [];
    public $detalleRequisicionesEstado = [];
    public $actividadRecursosSeleccionada = null;
    public $paginaActividadesDetalle = 1;
    public $porPaginaActividadesDetalle = 6;
    public $totalActividadesDetalle = 0;
    public $requisicionRecursosSeleccionada = null;
    public $paginaRequisicionesDetalle = 1;
    public $porPaginaRequisicionesDetalle = 6;
    public $totalRequisicionesDetalle = 0;

    public function mount(): void
    {
        abort_unless($this->puedeVerReporteDireccion(), 403);

        $this->poas = Poa::orderBy('anio', 'desc')
            ->get(['id', 'anio', 'name', 'activo'])
            ->map(fn ($poa) => [
                'id' => $poa->id,
                'anio' => $poa->anio,
                'name' => $poa->name,
                'activo' => $poa->activo,
            ])
            ->toArray();

        $this->departamentos = Departamento::orderBy('name')
            ->get(['id', 'name', 'siglas'])
            ->map(fn ($depto) => [
                'id' => $depto->id,
                'nombre' => $depto->name,
                'siglas' => $depto->siglas,
            ])
            ->toArray();

        $poaActivo = Poa::where('activo', true)->orderBy('anio', 'desc')->first();
        $this->anioSeleccionado = $poaActivo?->anio ?? ($this->poas[0]['anio'] ?? null);

        $this->cargarDatos();
    }

    public function updatedAnioSeleccionado(): void
    {
        $this->cargarDatos();
        $this->dispatch('direccion-charts-update');
    }

    public function updatedDepartamentoSeleccionado(): void
    {
        $this->cargarDatos();
        $this->dispatch('direccion-charts-update');
    }

    public function cargarDatos(): void
    {
        $this->poaActual = Poa::where('anio', $this->anioSeleccionado)->first();

        if (!$this->poaActual) {
            $this->limpiarDatos();
            return;
        }

        $this->cargarPlanificacion();
        $this->cargarRequisiciones();
        $this->cargarSeguimiento();
        $this->cargarFinanzas();
        $this->cargarTopDepartamentos();
        $this->cargarResumen();
        $this->cargarDetallesSeleccionados();
    }

    public function seleccionarEstadoActividad(string $estado): void
    {
        $this->estadoActividadSeleccionado = $estado;
        $this->paginaActividadesDetalle = 1;
        $this->actividadRecursosSeleccionada = null;
        $this->cargarDetalleActividadesEstado();
    }

    public function seleccionarEstadoRequisicion(string $estado): void
    {
        $this->estadoRequisicionSeleccionado = $estado;
        $this->paginaRequisicionesDetalle = 1;
        $this->requisicionRecursosSeleccionada = null;
        $this->cargarDetalleRequisicionesEstado();
    }

    public function cerrarDetalleActividad(): void
    {
        $this->estadoActividadSeleccionado = null;
        $this->detalleActividadesEstado = [];
        $this->actividadRecursosSeleccionada = null;
        $this->paginaActividadesDetalle = 1;
        $this->totalActividadesDetalle = 0;
    }

    public function cerrarDetalleRequisicion(): void
    {
        $this->estadoRequisicionSeleccionado = null;
        $this->detalleRequisicionesEstado = [];
        $this->requisicionRecursosSeleccionada = null;
        $this->paginaRequisicionesDetalle = 1;
        $this->totalRequisicionesDetalle = 0;
    }

    public function alternarRecursosActividad(int $actividadId): void
    {
        $this->actividadRecursosSeleccionada = $this->actividadRecursosSeleccionada === $actividadId ? null : $actividadId;
    }

    public function alternarRecursosRequisicion(int $requisicionId): void
    {
        $this->requisicionRecursosSeleccionada = $this->requisicionRecursosSeleccionada === $requisicionId ? null : $requisicionId;
    }

    public function paginaAnteriorActividades(): void
    {
        if ($this->paginaActividadesDetalle <= 1) {
            return;
        }

        $this->paginaActividadesDetalle--;
        $this->actividadRecursosSeleccionada = null;
        $this->cargarDetalleActividadesEstado();
    }

    public function paginaSiguienteActividades(): void
    {
        if ($this->paginaActividadesDetalle >= $this->totalPaginasActividadesDetalle()) {
            return;
        }

        $this->paginaActividadesDetalle++;
        $this->actividadRecursosSeleccionada = null;
        $this->cargarDetalleActividadesEstado();
    }

    public function paginaAnteriorRequisiciones(): void
    {
        if ($this->paginaRequisicionesDetalle <= 1) {
            return;
        }

        $this->paginaRequisicionesDetalle--;
        $this->requisicionRecursosSeleccionada = null;
        $this->cargarDetalleRequisicionesEstado();
    }

    public function paginaSiguienteRequisiciones(): void
    {
        if ($this->paginaRequisicionesDetalle >= $this->totalPaginasRequisicionesDetalle()) {
            return;
        }

        $this->paginaRequisicionesDetalle++;
        $this->requisicionRecursosSeleccionada = null;
        $this->cargarDetalleRequisicionesEstado();
    }

    private function puedeVerReporteDireccion(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->email === 'celeo.arias@unah.edu.hn'
            || $user->hasRole('Dirección')
            || $user->hasRole('direccion')
            || $user->hasRole('super_admin')
            || $user->can('reportes.direccion.ver');
    }

    private function actividadBase()
    {
        return Actividad::query()
            ->where('idPoa', $this->poaActual->id)
            ->when($this->departamentoSeleccionado, fn ($query) => $query->where('idDeptartamento', $this->departamentoSeleccionado));
    }

    private function requisicionBase()
    {
        return Requisicion::query()
            ->where('idPoa', $this->poaActual->id)
            ->when($this->departamentoSeleccionado, fn ($query) => $query->where('idDepartamento', $this->departamentoSeleccionado));
    }

    private function cargarPlanificacion(): void
    {
        $estados = ['FORMULACION', 'REFORMULACION', 'REVISION', 'APROBADO', 'RECHAZADO'];
        $conteos = $this->actividadBase()
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $this->actividadesPorEstado = collect($estados)
            ->map(fn ($estado) => [
                'estado' => $estado,
                'label' => $this->labelEstadoActividad($estado),
                'total' => (int) ($conteos[$estado] ?? 0),
                'color' => $this->colorEstadoActividad($estado),
            ])
            ->values()
            ->toArray();

        $this->alertasPlanificacion = $this->actividadBase()
            ->with(['departamento:id,name,siglas', 'creador:id,name,email'])
            ->whereIn('estado', ['FORMULACION', 'REFORMULACION', 'REVISION', 'RECHAZADO'])
            ->orderByRaw("FIELD(estado, 'RECHAZADO', 'REVISION', 'REFORMULACION', 'FORMULACION')")
            ->orderBy('updated_at', 'asc')
            ->limit(8)
            ->get()
            ->map(fn ($actividad) => [
                'nombre' => $actividad->nombre,
                'correlativo' => $actividad->correlativo_formateado,
                'departamento' => $actividad->departamento?->siglas ?? $actividad->departamento?->name ?? 'Sin departamento',
                'estado' => $this->labelEstadoActividad($actividad->estado),
                'estadoColor' => $this->colorEstadoActividad($actividad->estado),
                'dias' => $actividad->updated_at ? (int) $actividad->updated_at->diffInDays(now()) : 0,
                'responsable' => $actividad->creador?->name ?? 'Sin responsable',
            ])
            ->toArray();
    }

    private function cargarRequisiciones(): void
    {
        $this->requisicionesPorEstado = $this->requisicionBase()
            ->join('estado_requisicion', 'requisicion.idEstado', '=', 'estado_requisicion.id')
            ->select('estado_requisicion.estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado_requisicion.estado')
            ->orderBy('estado_requisicion.estado')
            ->get()
            ->map(fn ($item) => [
                'estado' => $item->estado,
                'total' => (int) $item->total,
                'color' => $this->colorEstadoRequisicion($item->estado),
            ])
            ->toArray();

        $this->alertasRequisiciones = $this->requisicionBase()
            ->with(['departamento:id,name,siglas', 'estado:id,estado', 'creador:id,name,email'])
            ->orderBy('updated_at', 'asc')
            ->limit(8)
            ->get()
            ->map(fn ($requisicion) => [
                'correlativo' => $requisicion->correlativo,
                'departamento' => $requisicion->departamento?->siglas ?? $requisicion->departamento?->name ?? 'Sin departamento',
                'estado' => $requisicion->estado?->estado ?? 'Sin estado',
                'estadoColor' => $this->colorEstadoRequisicion($requisicion->estado?->estado),
                'dias' => $requisicion->updated_at ? (int) $requisicion->updated_at->diffInDays(now()) : 0,
                'solicitante' => $requisicion->creador?->name ?? 'Sin solicitante',
                'fecha' => $requisicion->fechaSolicitud
                    ? Carbon::parse($requisicion->fechaSolicitud)->format('d/m/Y')
                    : $requisicion->created_at?->format('d/m/Y'),
            ])
            ->toArray();
    }

    private function cargarSeguimiento(): void
    {
        $requisicionIds = $this->requisicionBase()->pluck('id');
        $detalles = DetalleRequisicion::whereIn('idRequisicion', $requisicionIds);
        $totalDetalles = (clone $detalles)->count();
        $entregados = (clone $detalles)->where('entregado', true)->count();
        $pendientes = max($totalDetalles - $entregados, 0);
        $actas = ActaEntrega::whereIn('idRequisicion', $requisicionIds)->count();

        $this->seguimiento = [
            'detalles' => $totalDetalles,
            'entregados' => $entregados,
            'pendientes' => $pendientes,
            'actas' => $actas,
            'porcentajeEntrega' => $totalDetalles > 0 ? round(($entregados / $totalDetalles) * 100, 1) : 0,
        ];
    }

    private function cargarFinanzas(): void
    {
        $presupuestoAsignado = TechoDepto::where('idPoa', $this->poaActual->id)
            ->when($this->departamentoSeleccionado, fn ($query) => $query->where('idDepartamento', $this->departamentoSeleccionado))
            ->sum('monto');

        $presupuestoPlanificado = Presupuesto::whereHas('tarea', function ($query) {
            $query->where('idPoa', $this->poaActual->id)
                ->when($this->departamentoSeleccionado, fn ($q) => $q->where('idDeptartamento', $this->departamentoSeleccionado));
        })->sum('total');

        $presupuestoEjecutado = DetalleEjecucionPresupuestaria::whereHas('presupuesto.tarea', function ($query) {
            $query->where('idPoa', $this->poaActual->id)
                ->when($this->departamentoSeleccionado, fn ($q) => $q->where('idDeptartamento', $this->departamentoSeleccionado));
        })->sum('monto_total_ejecutado');

        $this->finanzas = [
            'asignado' => (float) $presupuestoAsignado,
            'planificado' => (float) $presupuestoPlanificado,
            'ejecutado' => (float) $presupuestoEjecutado,
            'disponible' => (float) ($presupuestoAsignado - $presupuestoPlanificado),
            'porcentajePlanificado' => $presupuestoAsignado > 0 ? round(($presupuestoPlanificado / $presupuestoAsignado) * 100, 1) : 0,
            'porcentajeEjecutado' => $presupuestoPlanificado > 0 ? round(($presupuestoEjecutado / $presupuestoPlanificado) * 100, 1) : 0,
        ];
    }

    private function cargarTopDepartamentos(): void
    {
        $this->topDepartamentos = Departamento::query()
            ->select('departamentos.id', 'departamentos.name', 'departamentos.siglas')
            ->withCount([
                'actividades as total_actividades' => fn ($query) => $query->where('idPoa', $this->poaActual->id),
                'actividades as pendientes' => fn ($query) => $query->where('idPoa', $this->poaActual->id)->whereIn('estado', ['FORMULACION', 'REFORMULACION', 'REVISION', 'RECHAZADO']),
                'actividades as aprobadas' => fn ($query) => $query->where('idPoa', $this->poaActual->id)->where('estado', 'APROBADO'),
            ])
            ->when($this->departamentoSeleccionado, fn ($query) => $query->where('departamentos.id', $this->departamentoSeleccionado))
            ->orderByDesc('pendientes')
            ->orderByDesc('total_actividades')
            ->limit(6)
            ->get()
            ->map(function ($departamento) {
                $avance = $departamento->total_actividades > 0
                    ? round(($departamento->aprobadas / $departamento->total_actividades) * 100, 1)
                    : 0;

                return [
                    'nombre' => $departamento->name,
                    'siglas' => $departamento->siglas,
                    'total' => (int) $departamento->total_actividades,
                    'pendientes' => (int) $departamento->pendientes,
                    'aprobadas' => (int) $departamento->aprobadas,
                    'avance' => $avance,
                ];
            })
            ->toArray();
    }

    private function cargarResumen(): void
    {
        $totalActividades = array_sum(array_column($this->actividadesPorEstado, 'total'));
        $actividadesAprobadas = collect($this->actividadesPorEstado)->firstWhere('estado', 'APROBADO')['total'] ?? 0;
        $totalRequisiciones = array_sum(array_column($this->requisicionesPorEstado, 'total'));

        $this->resumen = [
            'actividades' => $totalActividades,
            'actividadesAprobadas' => $actividadesAprobadas,
            'avancePlanificacion' => $totalActividades > 0 ? round(($actividadesAprobadas / $totalActividades) * 100, 1) : 0,
            'requisiciones' => $totalRequisiciones,
            'entrega' => $this->seguimiento['porcentajeEntrega'] ?? 0,
            'ejecucion' => $this->finanzas['porcentajeEjecutado'] ?? 0,
        ];
    }

    private function cargarDetallesSeleccionados(): void
    {
        $this->cargarDetalleActividadesEstado();
        $this->cargarDetalleRequisicionesEstado();
    }

    private function cargarDetalleActividadesEstado(): void
    {
        if (!$this->poaActual || !$this->estadoActividadSeleccionado) {
            $this->detalleActividadesEstado = [];
            $this->totalActividadesDetalle = 0;
            return;
        }

        $query = $this->actividadBase()
            ->with([
                'departamento:id,name,siglas',
                'creador:id,name,email',
                'tareas:id,nombre,idActividad',
                'tareas.presupuestos:id,cantidad,costounitario,total,recurso,idtarea,idunidad,idobjeto',
                'tareas.presupuestos.unidadMedida:id,nombre',
                'tareas.presupuestos.objetoGasto:id,identificador,descripcion',
            ])
            ->withCount('tareas')
            ->where('estado', $this->estadoActividadSeleccionado)
            ->orderBy('updated_at', 'desc');

        $this->totalActividadesDetalle = (clone $query)->count();

        if ($this->paginaActividadesDetalle > $this->totalPaginasActividadesDetalle()) {
            $this->paginaActividadesDetalle = $this->totalPaginasActividadesDetalle();
        }

        $this->detalleActividadesEstado = $query
            ->skip(($this->paginaActividadesDetalle - 1) * $this->porPaginaActividadesDetalle)
            ->take($this->porPaginaActividadesDetalle)
            ->get()
            ->map(fn ($actividad) => [
                'id' => $actividad->id,
                'nombre' => $actividad->nombre,
                'correlativo' => $actividad->correlativo_formateado,
                'departamento' => $actividad->departamento?->siglas ?? $actividad->departamento?->name ?? 'Sin departamento',
                'responsable' => $actividad->creador?->name ?? 'Sin responsable',
                'tareas' => (int) $actividad->tareas_count,
                'actualizado' => $actividad->updated_at?->format('d/m/Y'),
                'dias' => $actividad->updated_at ? (int) $actividad->updated_at->diffInDays(now()) : 0,
                'recursos' => $actividad->tareas
                    ->flatMap(fn ($tarea) => $tarea->presupuestos->map(fn ($presupuesto) => [
                        'tarea' => $tarea->nombre,
                        'recurso' => $presupuesto->recurso ?: 'Recurso sin nombre',
                        'cantidad' => (float) $presupuesto->cantidad,
                        'unidad' => $presupuesto->unidadMedida?->nombre ?? '-',
                        'costo' => (float) $presupuesto->costounitario,
                        'total' => (float) $presupuesto->total,
                        'objeto' => $presupuesto->objetoGasto?->descripcion ?? $presupuesto->idobjeto ?? '-',
                    ]))
                    ->values()
                    ->toArray(),
            ])
            ->toArray();
    }

    public function totalPaginasActividadesDetalle(): int
    {
        return max((int) ceil($this->totalActividadesDetalle / $this->porPaginaActividadesDetalle), 1);
    }

    private function cargarDetalleRequisicionesEstado(): void
    {
        if (!$this->poaActual || !$this->estadoRequisicionSeleccionado) {
            $this->detalleRequisicionesEstado = [];
            $this->totalRequisicionesDetalle = 0;
            return;
        }

        $query = $this->requisicionBase()
            ->with([
                'departamento:id,name,siglas',
                'estado:id,estado',
                'creador:id,name,email',
                'detalleRequisiciones:id,cantidad,entregado,idRequisicion,idPresupuesto,idRecurso,idUnidadMedida',
                'detalleRequisiciones.presupuesto:id,cantidad,costounitario,total,recurso,idobjeto',
                'detalleRequisiciones.presupuesto.objetoGasto:id,identificador,descripcion',
                'detalleRequisiciones.recurso:id,nombre',
                'detalleRequisiciones.unidadMedida:id,nombre',
            ])
            ->withCount('detalleRequisiciones')
            ->whereHas('estado', fn ($query) => $query->where('estado', $this->estadoRequisicionSeleccionado))
            ->orderBy('updated_at', 'desc');

        $this->totalRequisicionesDetalle = (clone $query)->count();

        if ($this->paginaRequisicionesDetalle > $this->totalPaginasRequisicionesDetalle()) {
            $this->paginaRequisicionesDetalle = $this->totalPaginasRequisicionesDetalle();
        }

        $this->detalleRequisicionesEstado = $query
            ->skip(($this->paginaRequisicionesDetalle - 1) * $this->porPaginaRequisicionesDetalle)
            ->take($this->porPaginaRequisicionesDetalle)
            ->get()
            ->map(fn ($requisicion) => [
                'id' => $requisicion->id,
                'correlativo' => $requisicion->correlativo,
                'descripcion' => $requisicion->descripcion,
                'departamento' => $requisicion->departamento?->siglas ?? $requisicion->departamento?->name ?? 'Sin departamento',
                'solicitante' => $requisicion->creador?->name ?? 'Sin solicitante',
                'detalles' => (int) $requisicion->detalle_requisiciones_count,
                'fecha' => $requisicion->fechaSolicitud
                    ? Carbon::parse($requisicion->fechaSolicitud)->format('d/m/Y')
                    : $requisicion->created_at?->format('d/m/Y'),
                'actualizado' => $requisicion->updated_at?->format('d/m/Y'),
                'dias' => $requisicion->updated_at ? (int) $requisicion->updated_at->diffInDays(now()) : 0,
                'recursos' => $requisicion->detalleRequisiciones
                    ->map(fn ($detalle) => [
                        'recurso' => $detalle->presupuesto?->recurso
                            ?: $detalle->recurso?->nombre
                            ?: 'Recurso sin nombre',
                        'cantidad' => (float) $detalle->cantidad,
                        'unidad' => $detalle->unidadMedida?->nombre ?? '-',
                        'costo' => (float) ($detalle->presupuesto?->costounitario ?? 0),
                        'total' => (float) ($detalle->presupuesto?->total ?? 0),
                        'objeto' => $detalle->presupuesto?->objetoGasto?->descripcion
                            ?? $detalle->presupuesto?->idobjeto
                            ?? '-',
                        'entregado' => (bool) $detalle->entregado,
                    ])
                    ->values()
                    ->toArray(),
            ])
            ->toArray();
    }

    public function totalPaginasRequisicionesDetalle(): int
    {
        return max((int) ceil($this->totalRequisicionesDetalle / $this->porPaginaRequisicionesDetalle), 1);
    }

    private function limpiarDatos(): void
    {
        $this->resumen = [];
        $this->actividadesPorEstado = [];
        $this->requisicionesPorEstado = [];
        $this->topDepartamentos = [];
        $this->alertasPlanificacion = [];
        $this->alertasRequisiciones = [];
        $this->seguimiento = [];
        $this->finanzas = [];
        $this->detalleActividadesEstado = [];
        $this->detalleRequisicionesEstado = [];
        $this->actividadRecursosSeleccionada = null;
        $this->paginaActividadesDetalle = 1;
        $this->totalActividadesDetalle = 0;
        $this->requisicionRecursosSeleccionada = null;
        $this->paginaRequisicionesDetalle = 1;
        $this->totalRequisicionesDetalle = 0;
    }

    private function labelEstadoActividad(?string $estado): string
    {
        return match ($estado) {
            'FORMULACION' => 'Formulación',
            'REFORMULACION' => 'Reformulación',
            'REVISION' => 'Revisión',
            'APROBADO' => 'Aprobado',
            'RECHAZADO' => 'Rechazado',
            default => 'Sin estado',
        };
    }

    private function colorEstadoActividad(?string $estado): string
    {
        return match ($estado) {
            'FORMULACION' => 'blue',
            'REFORMULACION' => 'amber',
            'REVISION' => 'violet',
            'APROBADO' => 'emerald',
            'RECHAZADO' => 'rose',
            default => 'zinc',
        };
    }

    private function colorEstadoRequisicion(?string $estado): string
    {
        $estado = mb_strtolower($estado ?? '');

        return match (true) {
            str_contains($estado, 'rechaz') => 'rose',
            str_contains($estado, 'apro') || str_contains($estado, 'recib') || str_contains($estado, 'final') => 'emerald',
            str_contains($estado, 'revision') || str_contains($estado, 'revisión') => 'violet',
            str_contains($estado, 'present') || str_contains($estado, 'solicit') => 'blue',
            str_contains($estado, 'compra') || str_contains($estado, 'proceso') => 'amber',
            default => 'zinc',
        };
    }

    public function render()
    {
        return view('livewire.reportes.reportes-direccion');
    }
}
