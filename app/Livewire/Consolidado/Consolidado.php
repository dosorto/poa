<?php

namespace App\Livewire\Consolidado;

use App\Exports\ConsolidadoUnidadEjecutoraExport;
use App\Models\Actividad\Actividad;
use App\Models\Actividad\Indicador;
use App\Models\Departamento\Departamento;
use App\Models\Dimension\Dimension;
use App\Models\Empleados\Empleado;
use App\Models\Poa\Poa;
use App\Models\Presupuestos\Presupuesto;
use App\Models\Tareas\Tarea;
use App\Models\TechoUes\TechoUe;
use App\Models\UnidadEjecutora\UnidadEjecutora;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Consolidado extends Component
{
    use WithPagination;

    public $anio;
    public $unidadEjecutoraId = '';
    public $dimensionId = '';
    public $departamentoId = '';
    public $expandedRow = null;
    public $activeTab = 'todas';

    public array $anios = [];
    public Collection $dimensiones;
    public array $unidadesEjecutoras = [];
    public array $departamentos = [];

    public function mount(): void
    {
        $this->anios = Poa::query()
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->toArray();

        $this->anio = $this->anios[0] ?? date('Y');

        $this->dimensiones = Dimension::query()
            ->orderBy('nombre')
            ->get();

        $this->cargarUnidadesEjecutoras();
        $this->cargarDepartamentos();
    }

    private function cargarUnidadesEjecutoras(): void
    {
        $this->unidadesEjecutoras = UnidadEjecutora::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (UnidadEjecutora $unidad) => [
                'id' => (string) $unidad->id,
                'name' => $unidad->name,
            ])
            ->toArray();

        $unidadEmpleado = Auth::user()?->empleado?->idUnidadEjecutora;
        $unidadValida = collect($this->unidadesEjecutoras)->contains(
            fn (array $unidad) => (int) $unidad['id'] === (int) $unidadEmpleado
        );

        if ($unidadValida) {
            $this->unidadEjecutoraId = (string) $unidadEmpleado;
            return;
        }

        $this->unidadEjecutoraId = $this->unidadesEjecutoras[0]['id'] ?? '';
    }

    private function cargarDepartamentos(): void
    {
        if (! $this->unidadEjecutoraId) {
            $this->departamentos = [];
            $this->departamentoId = '';
            return;
        }

        $this->departamentos = Departamento::query()
            ->where('idUnidadEjecutora', $this->unidadEjecutoraId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Departamento $departamento) => [
                'id' => (string) $departamento->id,
                'name' => $departamento->name,
            ])
            ->toArray();

        $departamentoValido = collect($this->departamentos)->contains(
            fn (array $departamento) => (string) $departamento['id'] === (string) $this->departamentoId
        );

        if (! $departamentoValido) {
            $this->departamentoId = '';
        }
    }

    public function updatingUnidadEjecutoraId(): void
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatedUnidadEjecutoraId(): void
    {
        $this->cargarDepartamentos();
    }

    public function updatingAnio(): void
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatingDimensionId(): void
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatingDepartamentoId(): void
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatingActiveTab(): void
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function toggleExpand($actividadId): void
    {
        $this->expandedRow = $this->expandedRow === $actividadId ? null : $actividadId;
    }

    public function toggleSPI($actividadId): void
    {
        $actividad = Actividad::find($actividadId);

        if (! $actividad) {
            return;
        }

        $actividad->uploadedIntoSPI = ! $actividad->uploadedIntoSPI;
        $actividad->save();

        $this->dispatch('spi-updated', ['message' => 'Estado SPI actualizado correctamente']);
    }

    public function exportarExcel()
    {
        $export = new ConsolidadoUnidadEjecutoraExport($this->getActividadesExportacion());
        $filename = 'consolidado-ue-' . now()->format('Ymd_His') . '.csv';

        return Response::streamDownload(function () use ($export) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $export->headings());

            foreach ($export->rows() as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function actividadesBaseQuery(bool $conFiltrosInferiores = true): Builder
    {
        $query = Actividad::query()
            ->with([
                'departamento',
                'poa',
                'unidadEjecutora',
                'categoria',
                'tipo',
                'resultado.area.objetivo.dimension',
                'indicadores.planificacions.mes',
                'indicadores.planificacions.seguimientos',
                'tareas.presupuestos.mes',
                'tareas.presupuestos.grupoGasto',
                'tareas.presupuestos.objetoGasto',
                'tareas.presupuestos.fuente',
                'tareas.empleados',
                'empleados',
            ]);

        if ($this->unidadEjecutoraId) {
            $query->where('idUE', $this->unidadEjecutoraId);
        }

        if ($this->anio) {
            $query->whereHas('poa', function (Builder $poaQuery) {
                $poaQuery->where('anio', $this->anio);
            });
        }

        if ($conFiltrosInferiores && $this->dimensionId) {
            $query->whereHas('resultado.area.objetivo.dimension', function (Builder $dimensionQuery) {
                $dimensionQuery->where('id', $this->dimensionId);
            });
        }

        if ($conFiltrosInferiores && $this->departamentoId) {
            $query->where('idDeptartamento', $this->departamentoId);
        }

        return $query;
    }

    protected function getActividadesExportacion(): EloquentCollection
    {
        return $this->actividadesBaseQuery()
            ->where('estado', 'APROBADO')
            ->orderBy('correlativo')
            ->orderBy('nombre')
            ->get();
    }

    protected function actividadesListadoQuery(): Builder
    {
        $query = $this->actividadesBaseQuery()
            ->where('estado', 'APROBADO');

        return match ($this->activeTab) {
            'pendientes_spi' => $query->where(function (Builder $builder) {
                $builder->where('uploadedIntoSPI', false)
                    ->orWhereNull('uploadedIntoSPI');
            }),
            'subidas_spi' => $query->where('uploadedIntoSPI', true),
            default => $query,
        };
    }

    public function getActividadesProperty(): LengthAwarePaginator
    {
        return $this->actividadesListadoQuery()
            ->orderBy('correlativo')
            ->orderBy('nombre')
            ->paginate(15);
    }

    public function getActividadDetalleProperty(): ?Actividad
    {
        if (! $this->expandedRow) {
            return null;
        }

        return $this->actividadesListadoQuery()->find($this->expandedRow);
    }

    public function getTabsProperty(): array
    {
        $baseQuery = $this->actividadesBaseQuery()
            ->where('estado', 'APROBADO');

        $pendientesCount = (clone $baseQuery)
            ->where(function (Builder $query) {
                $query->where('uploadedIntoSPI', false)
                    ->orWhereNull('uploadedIntoSPI');
            })
            ->count();

        $subidasCount = (clone $baseQuery)
            ->where('uploadedIntoSPI', true)
            ->count();

        return [
            'todas' => [
                'label' => 'Todas las aprobadas',
                'count' => (clone $baseQuery)->count(),
            ],
            'pendientes_spi' => [
                'label' => 'Pendientes de SPI',
                'count' => $pendientesCount,
            ],
            'subidas_spi' => [
                'label' => 'Subidas a SPI',
                'count' => $subidasCount,
            ],
        ];
    }

    public function getEstadisticasProperty(): array
    {
        return [
            'asignado' => [
                'monto' => (float) TechoUe::query()
                    ->when($this->unidadEjecutoraId, fn ($query) => $query->where('idUE', $this->unidadEjecutoraId))
                    ->whereHas('poa', fn (Builder $poaQuery) => $poaQuery->where('anio', $this->anio))
                    ->sum('monto'),
                'actividades' => (int) $this->actividadesBaseQuery(false)->count(),
            ],
            'aprobado' => [
                'monto' => $this->sumarPresupuestoPorEstados(['APROBADO']),
                'actividades' => (int) $this->contarActividadesPorEstados(['APROBADO']),
            ],
            'subsanacion' => [
                'monto' => $this->sumarPresupuestoPorEstados(['REFORMULACION']),
                'actividades' => (int) $this->contarActividadesPorEstados(['REFORMULACION']),
            ],
            'revision' => [
                'monto' => $this->sumarPresupuestoPorEstados(['REVISION']),
                'actividades' => (int) $this->contarActividadesPorEstados(['REVISION']),
            ],
        ];
    }

    protected function contarActividadesPorEstados(array $estados): int
    {
        return (int) $this->actividadesBaseQuery(false)
            ->whereIn('estado', $estados)
            ->count();
    }

    protected function sumarPresupuestoPorEstados(array $estados): float
    {
        return (float) Presupuesto::query()
            ->whereHas('tarea.actividad', function (Builder $actividadQuery) use ($estados) {
                $actividadQuery
                    ->when($this->unidadEjecutoraId, fn ($query) => $query->where('idUE', $this->unidadEjecutoraId))
                    ->whereIn('estado', $estados)
                    ->whereHas('poa', fn (Builder $poaQuery) => $poaQuery->where('anio', $this->anio));
            })
            ->sum('total');
    }

    public function getActividadTotalMonto(Actividad $actividad): float
    {
        return (float) $actividad->tareas->sum(function (Tarea $tarea) {
            return $tarea->presupuestos->sum('total');
        });
    }

    public function getEstadoBadgeClass(?string $estado): string
    {
        return match ($estado) {
            'APROBADO' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'REFORMULACION' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            'REVISION' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'RECHAZADO' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300',
        };
    }

    public function getIndicadorResumen(Indicador $indicador): string
    {
        $partes = [];

        foreach ($this->getIndicadorTrimestres($indicador) as $trimestre) {
            if (($trimestre['planificado'] ?? 0) <= 0 && ($trimestre['ejecutado'] ?? 0) <= 0) {
                continue;
            }

            $partes[] = "{$trimestre['nombre']}: P {$this->formatDecimal($trimestre['planificado'])} | E {$this->formatDecimal($trimestre['ejecutado'])}";
        }

        return implode(PHP_EOL, $partes);
    }

    public function getIndicadorTrimestres(Indicador $indicador): array
    {
        $trimestres = [
            'T1' => ['meses' => [1, 2, 3], 'nombre' => 'Trimestre 1', 'planificado' => 0, 'ejecutado' => 0, 'fechaInicio' => null, 'fechaFin' => null],
            'T2' => ['meses' => [4, 5, 6], 'nombre' => 'Trimestre 2', 'planificado' => 0, 'ejecutado' => 0, 'fechaInicio' => null, 'fechaFin' => null],
            'T3' => ['meses' => [7, 8, 9], 'nombre' => 'Trimestre 3', 'planificado' => 0, 'ejecutado' => 0, 'fechaInicio' => null, 'fechaFin' => null],
            'T4' => ['meses' => [10, 11, 12], 'nombre' => 'Trimestre 4', 'planificado' => 0, 'ejecutado' => 0, 'fechaInicio' => null, 'fechaFin' => null],
        ];

        foreach ($indicador->planificacions as $planificacion) {
            $mesId = $planificacion->mes->id ?? null;

            foreach ($trimestres as &$trimestre) {
                if (! $mesId || ! in_array($mesId, $trimestre['meses'], true)) {
                    continue;
                }

                $trimestre['planificado'] += (float) ($planificacion->cantidad ?? 0);
                $trimestre['ejecutado'] += (float) $planificacion->seguimientos->sum('cantidad');

                if ($planificacion->fechaInicio && (! $trimestre['fechaInicio'] || $planificacion->fechaInicio < $trimestre['fechaInicio'])) {
                    $trimestre['fechaInicio'] = $planificacion->fechaInicio;
                }

                if ($planificacion->fechaFin && (! $trimestre['fechaFin'] || $planificacion->fechaFin > $trimestre['fechaFin'])) {
                    $trimestre['fechaFin'] = $planificacion->fechaFin;
                }
            }
        }

        return $trimestres;
    }

    public function buildActividadTexto(Actividad $actividad): string
    {
        $lineas = [
            'CORRELATIVO: ' . ($actividad->correlativo ?? 'N/A'),
            'ACTIVIDAD: ' . ($actividad->nombre ?? 'N/A'),
            'ESTADO: ' . ($actividad->estado ?? 'N/A'),
            'UNIDAD EJECUTORA: ' . ($actividad->unidadEjecutora->name ?? 'N/A'),
            'DEPARTAMENTO: ' . ($actividad->departamento->name ?? 'N/A'),
            'AÑO: ' . ($actividad->poa->anio ?? 'N/A'),
            '',
            $this->buildActividadInstitucionalTexto($actividad),
            '',
            $this->buildActividadGeneralTexto($actividad),
        ];

        if ($actividad->indicadores->isNotEmpty()) {
            $lineas[] = '';
            $lineas[] = 'INDICADORES:';

            foreach ($actividad->indicadores as $indice => $indicador) {
                $lineas[] = ($indice + 1) . '. ' . $this->buildIndicadorTexto($indicador);
            }
        }

        if ($actividad->tareas->isNotEmpty()) {
            $lineas[] = '';
            $lineas[] = 'TAREAS Y RECURSOS:';

            foreach ($actividad->tareas as $indice => $tarea) {
                $lineas[] = ($indice + 1) . '. ' . $this->buildTareaTexto($tarea);
            }
        }

        $lineas[] = '';
        $lineas[] = 'TOTAL ACTIVIDAD: L ' . $this->formatDecimal($this->getActividadTotalMonto($actividad));

        return implode(PHP_EOL, $lineas);
    }

    public function buildActividadInstitucionalTexto(Actividad $actividad): string
    {
        return implode(PHP_EOL, [
            'DATOS INSTITUCIONALES',
            'Dimensión: ' . ($actividad->resultado->area->objetivo->dimension->nombre ?? 'N/A'),
            'Objetivo: ' . ($actividad->resultado->area->objetivo->nombre ?? 'N/A'),
            'Área: ' . ($actividad->resultado->area->nombre ?? 'N/A'),
            'Resultado Institucional: ' . ($actividad->resultado->nombre ?? 'N/A'),
        ]);
    }

    public function buildActividadGeneralTexto(Actividad $actividad): string
    {
        $encargados = $actividad->empleados->map(function (Empleado $empleado) {
            return trim(($empleado->nombre ?? '') . ' ' . ($empleado->apellido ?? ''))
                . ' (#' . ($empleado->num_empleado ?? 'N/A') . ')';
        })->implode(', ');

        return implode(PHP_EOL, [
            'DATOS GENERALES',
            'Resultado de actividad: ' . ($actividad->resultadoActividad ?? 'N/A'),
            'Población objetivo: ' . ($actividad->poblacion_objetivo ?? 'N/A'),
            'Medio de verificación: ' . ($actividad->medio_verificacion ?? 'N/A'),
            'Categoría: ' . ($actividad->categoria->categoria ?? 'N/A'),
            'Tipo de actividad: ' . ($actividad->tipo->tipo ?? 'N/A'),
            'Encargados: ' . ($encargados !== '' ? $encargados : 'N/A'),
        ]);
    }

    public function buildIndicadorTexto(Indicador $indicador): string
    {
        $lineas = [
            'INDICADOR: ' . ($indicador->nombre ?? 'N/A'),
            'Tipo: ' . ($indicador->isPorcentaje ? 'Porcentaje' : 'Cantidad'),
            'Descripción: ' . ($indicador->descripcion ?: 'N/A'),
            'Meta planificada: ' . $this->formatDecimal($indicador->cantidadPlanificada ?? 0),
            'Cantidad ejecutada: ' . $this->formatDecimal($indicador->cantidadEjecutada ?? 0),
        ];

        if ($indicador->promedioAlcanzado !== null) {
            $lineas[] = 'Promedio alcanzado: ' . $this->formatDecimal($indicador->promedioAlcanzado) . '%';
        }

        foreach ($this->getIndicadorTrimestres($indicador) as $trimestre) {
            if (($trimestre['planificado'] ?? 0) <= 0 && ($trimestre['ejecutado'] ?? 0) <= 0) {
                continue;
            }

            $lineas[] = $trimestre['nombre'] . ':'
                . ' Planificado ' . $this->formatDecimal($trimestre['planificado'])
                . ' | Ejecutado ' . $this->formatDecimal($trimestre['ejecutado']);
        }

        return implode(PHP_EOL, $lineas);
    }

    public function buildTareaTexto(Tarea $tarea): string
    {
        $lineas = [
            'TAREA: ' . ($tarea->nombre ?? 'N/A'),
            'Correlativo: ' . ($tarea->correlativo ?? 'N/A'),
            'Descripción: ' . ($tarea->descripcion ?: 'N/A'),
            'Estado: ' . ($tarea->estado ?? 'N/A'),
        ];

        if ($tarea->presupuestos->isNotEmpty()) {
            $lineas[] = 'Recursos:';

            foreach ($tarea->presupuestos as $presupuesto) {
                $lineas[] = '- ' . str_replace(PHP_EOL, ' | ', $this->buildPresupuestoTexto($presupuesto));
            }
        }

        $lineas[] = 'Total tarea: L ' . $this->formatDecimal($tarea->presupuestos->sum('total'));

        return implode(PHP_EOL, $lineas);
    }

    public function buildPresupuestoTexto(Presupuesto $presupuesto): string
    {
        return implode(PHP_EOL, [
            'Recurso: ' . ($presupuesto->recurso ?? 'N/A'),
            'Detalle técnico: ' . ($presupuesto->detalle_tecnico ?: 'N/A'),
            'Cantidad: ' . $this->formatDecimal($presupuesto->cantidad ?? 0),
            'Costo unitario: L ' . $this->formatDecimal($presupuesto->costounitario ?? 0),
            'Total: L ' . $this->formatDecimal($presupuesto->total ?? 0),
            'Grupo de gasto: ' . ($presupuesto->grupoGasto->nombre ?? 'N/A'),
            'Objeto de gasto: ' . ($presupuesto->objetoGasto->nombre ?? 'N/A'),
            'Fuente: ' . ($presupuesto->fuente->nombre ?? 'N/A'),
            'Mes: ' . ($presupuesto->mes->mes ?? 'N/A'),
        ]);
    }

    protected function formatDecimal($valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }

    public function render()
    {
        return view('livewire.consolidado.consolidado', [
            'actividades' => $this->actividades,
            'actividadDetalle' => $this->actividadDetalle,
            'dimensiones' => $this->dimensiones,
            'anios' => $this->anios,
            'unidadesEjecutoras' => $this->unidadesEjecutoras,
            'departamentos' => $this->departamentos,
            'estadisticas' => $this->estadisticas,
            'tabs' => $this->tabs,
        ]);
    }
}
