<?php

namespace App\Livewire\Requisicion;

use App\Models\Requisicion\Requisicion as RequisicionModel;
use App\Services\RequisicionCorreoService;
use Illuminate\Support\Facades\Validator;
use App\Models\Requisicion\EstadoRequisicion;
use App\Models\Empleados\Empleado;
use App\Models\Empleados\EmpleadoDepto;
use App\Models\Tareas\TareaHistorico;
use App\Models\Presupuestos\Presupuesto;
use App\Models\Departamento\Departamento;
use App\Models\Requisicion\DetalleRequisicion;
use App\Models\Tareas\Tarea;
use App\Models\ProcesoCompras\ProcesoCompra;
use App\Models\Poa\Poa;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Requisicion extends Component
{
    use WithPagination;
    protected string $layout = 'layouts.app';
    protected $paginationTheme = 'tailwind';
    public $buscarActividad = '';
    public $correlativo;
    public $descripcion;
    public $observacion;
    public $created_by;
    public $approved_by;
    public $idPoa;
    public $idDepartamento;
    public $idEstado;
    public $fechaSolicitud;
    public $fechaRequerido;
    public $requisicionId;
    public $search = '';
    public $busqueda = '';
    public $estado = 0;
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $showModal = false;
    public $showDeleteModal = false;
    public $showSumarioModal = false;
    public $requisicionToDelete;
    public $errorMessage = '';
    public $showErrorModal = false;
    public $isEditing = false;
    public $successMessage = '';
    public $diasRestantes = null;

    public $mostrarSelector = false;
    public $departamentosUsuario = [];
    public $departamentoSeleccionado;
    public $detalleRequisiciones = [];
    public $presupuestosSeleccionados = [];
    public $recursosSeleccionados = [];
    public array $cantidadesInput = [];
    public array $erroresCantidad = [];

    public $poaYear = null;
	public $poaYears = [];
    public $detalleRecursos = [];
    public $showDetalleRecursosModal = false;

    public $showOrdenCombustibleModal = false;
    public $ordenCombustibleRecursoId;
    public $ordenCombustibleRecursoNombre;
    public $ordenCombustibleData = [
        'modelo_vehiculo' => '',
        'placa' => '',
        'lugar_salida' => '',
        'lugar_destino' => '',
        'recorrido_km' => 0,
        'fecha_actividad' => '',
        'responsable' => '',
        'actividades_realizar' => '',
        'monto' => 0,
        'monto_en_letras' => '',
    ];
    public $empleados = [];

    public $estadoRequisicion;
    public $montoTotalRequisicion = 0;
    public $montoEjecutadoRequisicion = 0;

    public $puedeCrearRequisicion = false;
    public $mensajePlazoRequisicion = '';
    public bool $showModalRequisicion = false;
    public int $pasoActual = 1;
    public $fechaRequerida = '';
    public array $ordenesCombustible = [];
    public string $modalRequisicionError = '';
    public bool $modalCantidad = false;
    public ?array $recursoEnModalCantidad = null;
    public $cantidadTemporal = 1;
    public ?int $combustibleEnModal = null;
    public ?int $cantidadDisponibleCombustibleModal = null;
    public ?string $modoOrdenCombustible = 'agregar';

    protected $rules = [
        'correlativo' => 'required|min:3',
        'descripcion' => 'required',
        'observacion' => 'required',
        'approved_by' => 'nullable|exists:users,id',
        'idPoa' => 'required|exists:poas,id',
        'fechaSolicitud' => 'required|date',
        'fechaRequerido' => 'required|date',
    ];

    protected $messages = [
        'correlativo.required' => 'El correlativo es obligatorio.',
        'correlativo.min' => 'El correlativo debe tener al menos 3 caracteres.',
        'descripcion.required' => 'La descripción es obligatoria.',
        'idPoa.required' => 'El POA es obligatorio.',
        'fechaSolicitud.required' => 'La fecha de solicitud es obligatoria.',
        'fechaRequerido.required' => 'La fecha requerida es obligatoria.',
        'observacion.required' => 'La observación es obligatoria.',
    ];


    public function crearRequisicionDesdeSumario()
    {

        if (!$this->puedeCrearRequisicion) {
            session()->flash('error', $this->mensajePlazoRequisicion);
            return;
        }

        $this->validate([
            'descripcion' => 'required',
            'fechaRequerido' => 'required|date',
            'departamentoSeleccionado' => 'required|exists:departamentos,id', // Validar que el departamento seleccionado sea válido
        ]);

        $user = Auth::user();

        if (!$this->idPoa && !empty($this->recursosSeleccionados)) {
            $primerRecurso = $this->recursosSeleccionados[0];
            $presupuesto = Presupuesto::find($primerRecurso['id']);
            if ($presupuesto && $presupuesto->idtarea) {
                $tarea = Tarea::find($presupuesto->idtarea);
                if ($tarea && $tarea->idPoa) {
                    $this->idPoa = $tarea->idPoa;
                }
            }
        }

        $poa = $this->idPoa ? Poa::find($this->idPoa) : null;

        // Usar el departamento seleccionado
        $this->idDepartamento = $this->departamentoSeleccionado;

        $departamento = $this->idDepartamento ? Departamento::find($this->idDepartamento) : null;
        $ultimo = \App\Models\Requisicion\Requisicion::orderBy('id', 'desc')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        $tipoDepto = $departamento->tipo ?? '';
        $nombreDepto = $departamento->name ?? '';
        $anio = $poa ? $poa->anio : date('Y');
        $correlativo = \App\Helpers\CorrelativoHelper::generarCorrelativo($tipoDepto, $nombreDepto, $anio, $numero);

        $this->idEstado = $this->getEstadoPresentadoId();

        try {
            $data = [
                'correlativo' => $correlativo,
                'descripcion' => $this->descripcion,
                'observacion' => $this->observacion,
                'created_by' => $user->id,
                'approved_by' => null,
                'idPoa' => $this->idPoa,
                'idDepartamento' => $this->idDepartamento, // Asociar el departamento seleccionado
                'idEstado' => $this->idEstado,
                'fechaSolicitud' => now(),
                'fechaRequerido' => $this->fechaRequerido,
            ];

            $requisicion = \App\Models\Requisicion\Requisicion::create($data);

            if (!$requisicion) {
                throw new \Exception('No se pudo crear la requisición.');
            }

            foreach ($this->recursosSeleccionados as $recurso) {
                $presupuesto = Presupuesto::find($recurso['id']);
                if ($presupuesto) {
                    $detalle = DetalleRequisicion::create([
                        'idRequisicion' => $requisicion->id,
                        'idPoa' => $this->idPoa,
                        'idPresupuesto' => $presupuesto->id,
                        'idRecurso' => $presupuesto->idHistorico,
                        'cantidad' => $recurso['cantidad_seleccionada'],
                        'idUnidadMedida' => $presupuesto->idunidad,
                        'entregado' => false,
                        'created_by' => $user->id,
                    ]);

                    if (!empty($recurso['orden_combustible_creada'])) {
                        $this->vincularOrdenCombustibleConDetalle($detalle, $user->id);
                    }
                }
            }

            $this->enviarCorreoRequisicionCreada($requisicion);

            $this->showSumarioModal = false;
            $this->resetInputFields();
            session()->flash('message', 'Requisición creada correctamente.');
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar: ' . $e->getMessage();
            $this->showErrorModal = true;
        }

        session()->forget('recursosSeleccionados');
        $this->recursosSeleccionados = [];
        $this->presupuestosSeleccionados = [];
        $this->cantidadesInput = [];
        $this->erroresCantidad = [];
        $this->cerrarModalCantidad();
    }

    private function vincularOrdenCombustibleConDetalle(DetalleRequisicion $detalle, int $userId): void
    {
        DB::table('orden_combustible')
            ->where('idPoa', $detalle->idPoa)
            ->where('idRecurso', $detalle->idRecurso)
            ->where('created_by', $userId)
            ->orderByDesc('id')
            ->limit(1)
            ->update([
                'idDetalleRequisicion' => $detalle->id,
                'updated_at' => now(),
            ]);
    }

    private function enviarCorreoRequisicionCreada(RequisicionModel $requisicion): void
    {
        app(RequisicionCorreoService::class)->enviarCreada($requisicion);
    }

    public function agregarRecursoAlSumario($recurso)
    {
        if (!collect($this->recursosSeleccionados)->contains('id', $recurso['id'])) {
            $this->recursosSeleccionados[] = $recurso;
        }
    }

    // Quitar recurso del sumario
    public function quitarRecursoDelSumario($recursoId)
    {
        $this->recursosSeleccionados = collect($this->recursosSeleccionados)
            ->reject(fn($item) => $item['id'] == $recursoId)
            ->values()
            ->toArray();

        if (isset($this->presupuestosSeleccionados[$recursoId])) {
            unset($this->presupuestosSeleccionados[$recursoId]);
        }

        unset($this->cantidadesInput[$recursoId], $this->erroresCantidad[$recursoId]);
        unset($this->ordenesCombustible[$recursoId]);
    }


    // Detectar cambios en las cantidades solicitadas
    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'presupuestosSeleccionados.')) {
            $presupuestoId = (int) (explode('.', $propertyName)[1] ?? 0);
            if ($presupuestoId > 0) {
                $this->actualizarCantidadRecursoSeleccionado($presupuestoId);
            }
        }

        if (str_starts_with($propertyName, 'cantidadesInput.')) {
            $presupuestoId = (int) (explode('.', $propertyName)[1] ?? 0);
            if ($presupuestoId > 0) {
                if ($this->combustibleEnModal === $presupuestoId) {
                    $this->validarCantidadInput($presupuestoId);
                    $this->actualizarMontoOrdenCombustible($presupuestoId);
                }
            }
        }

        if (str_starts_with($propertyName, 'ordenesCombustible.')) {
            $partes = explode('.', $propertyName);
            $presupuestoId = $partes[1] ?? null;
            $campo = $partes[2] ?? null;

            if ($presupuestoId && $campo !== 'confirmada' && isset($this->ordenesCombustible[$presupuestoId])) {
                $this->ordenesCombustible[$presupuestoId]['confirmada'] = false;
            }
        }
    }

    // Actualizar el sumario de recursos seleccionados
    public function actualizarSumario()
    {
        $this->recursosSeleccionados = [];

        foreach ($this->presupuestosSeleccionados as $presupuestoId => $cantidad) {
            if ($cantidad === null || $cantidad === '') {
                continue;
            }

            $presupuesto = Presupuesto::with(['unidadMedida'])->find($presupuestoId);
            if (!$presupuesto) {
                continue;
            }

            $tarea = $presupuesto->idtarea ? Tarea::with('actividad')->find($presupuesto->idtarea) : null;

            if ($this->departamentoSeleccionado && $tarea && $tarea->idDeptartamento != $this->departamentoSeleccionado) {
                continue;
            }

            if ($this->poaYear && $tarea && $tarea->poa && $tarea->poa->anio != $this->poaYear) {
                continue;
            }

            $this->recursosSeleccionados[] = $this->construirRecursoSeleccionado($presupuesto, (int) $cantidad);
        }
    }

    private function esRecursoCombustible(array $presupuesto): bool
    {
        return str_contains(strtoupper($presupuesto['nombre'] ?? ''), 'GASOLINA')
            || str_contains(strtoupper($presupuesto['nombre'] ?? ''), 'DIESEL');
    }

    public function agregarRecurso(int $presupuestoId)
    {
        if (isset($this->presupuestosSeleccionados[$presupuestoId])) {
            unset($this->erroresCantidad[$presupuestoId]);
            return;
        }

        $presupuesto = Presupuesto::with(['unidadMedida'])->find($presupuestoId);
        if (!$presupuesto) {
            $this->erroresCantidad[$presupuestoId] = 'No se encontro el recurso seleccionado.';
            return;
        }

        $disponible = $this->obtenerCantidadDisponible($presupuestoId);
        if ($disponible <= 0) {
            $this->erroresCantidad[$presupuestoId] = 'Este recurso esta agotado.';
            return;
        }

        $cantidad = (int) ($this->cantidadesInput[$presupuestoId] ?? 0);
        $recurso = $this->construirRecursoSeleccionado($presupuesto, $cantidad);

        if ($this->esRecursoCombustible($recurso)) {
            $this->combustibleEnModal = $presupuestoId;
            $this->cantidadDisponibleCombustibleModal = $disponible;
            $this->modoOrdenCombustible = 'agregar';
            $this->ordenCombustibleRecursoId = $presupuestoId;
            $this->ordenCombustibleRecursoNombre = $recurso['nombre'] ?? '';
            $this->cantidadesInput[$presupuestoId] = $cantidad > 0 ? $cantidad : 1;
            $this->ordenCombustibleData = $this->ordenCombustibleDataDesdeOrden($this->ordenesCombustible[$presupuestoId] ?? []);
            $this->actualizarMontoOrdenCombustible($presupuestoId);
            $this->cargarEmpleadosOrdenCombustible($this->ordenCombustibleData['responsable'] ?? null);
            $this->showOrdenCombustibleModal = true;
            return;
        }

        $this->abrirModalCantidad($presupuestoId);
    }

    public function abrirModalCantidad(int $id)
    {
        $presupuesto = Presupuesto::with(['unidadMedida'])->find($id);
        if (!$presupuesto) {
            $this->erroresCantidad[$id] = 'No se encontro el recurso seleccionado.';
            return;
        }

        $disponible = $this->obtenerCantidadDisponible($id);
        if ($disponible <= 0) {
            $this->erroresCantidad[$id] = 'Este recurso esta agotado.';
            return;
        }

        $recurso = $this->construirRecursoSeleccionado($presupuesto, (int) ($this->presupuestosSeleccionados[$id] ?? 1));
        $this->recursoEnModalCantidad = $recurso;
        $this->cantidadTemporal = max(1, (int) ($this->presupuestosSeleccionados[$id] ?? 1));
        unset($this->erroresCantidad[$id]);
        $this->modalCantidad = true;
    }

    public function confirmarCantidad()
    {
        if (!$this->recursoEnModalCantidad || empty($this->recursoEnModalCantidad['id'])) {
            return;
        }

        $presupuestoId = (int) $this->recursoEnModalCantidad['id'];
        $cantidad = (int) $this->cantidadTemporal;
        $disponible = $this->obtenerCantidadDisponible($presupuestoId);

        unset($this->erroresCantidad[$presupuestoId]);

        if ($cantidad <= 0) {
            $this->erroresCantidad[$presupuestoId] = 'Ingrese una cantidad mayor a 0.';
            return;
        }

        if ($cantidad > $disponible) {
            $this->erroresCantidad[$presupuestoId] = "La cantidad no puede superar el disponible ({$disponible}).";
            return;
        }

        $presupuesto = Presupuesto::with(['unidadMedida'])->find($presupuestoId);
        if (!$presupuesto) {
            $this->erroresCantidad[$presupuestoId] = 'No se encontro el recurso seleccionado.';
            return;
        }

        $this->presupuestosSeleccionados[$presupuestoId] = $cantidad;
        $recurso = $this->construirRecursoSeleccionado($presupuesto, $cantidad);

        $index = collect($this->recursosSeleccionados)->search(fn($item) => (int) $item['id'] === $presupuestoId);
        if ($index === false) {
            $this->recursosSeleccionados[] = $recurso;
        } else {
            $this->recursosSeleccionados[$index] = array_merge($this->recursosSeleccionados[$index], $recurso);
        }

        $this->cerrarModalCantidad();
    }

    public function cerrarModalCantidad()
    {
        $presupuestoId = $this->recursoEnModalCantidad['id'] ?? null;

        if ($presupuestoId) {
            unset($this->erroresCantidad[$presupuestoId]);
            unset($this->cantidadesInput[$presupuestoId]);
        }

        $this->recursoEnModalCantidad = null;
        $this->cantidadTemporal = null;
        $this->modalCantidad = false;
        $this->resetValidation();
    }

    public function updatedCantidadTemporal()
    {
        if (!$this->recursoEnModalCantidad || empty($this->recursoEnModalCantidad['id'])) {
            return;
        }

        $presupuestoId = (int) $this->recursoEnModalCantidad['id'];
        $disponible = $this->obtenerCantidadDisponible($presupuestoId);
        $cantidad = $this->cantidadTemporal;

        unset($this->erroresCantidad[$presupuestoId]);

        if ($cantidad === null || $cantidad === '' || (int) $cantidad <= 0) {
            $this->erroresCantidad[$presupuestoId] = 'Ingrese una cantidad mayor a 0.';
            return;
        }

        if ($disponible > 0 && (int) $cantidad > $disponible) {
            $this->erroresCantidad[$presupuestoId] = "La cantidad no puede superar el disponible ({$disponible}).";
        }
    }

    public function confirmarOrdenYAgregar()
    {
        if (!$this->combustibleEnModal) {
            return;
        }

        $presupuestoId = $this->combustibleEnModal;

        if (!$this->validarCantidadInput($presupuestoId)) {
            return;
        }

        $this->validarOrdenCombustibleData();
        $this->ordenesCombustible[$presupuestoId] = $this->ordenDesdeOrdenCombustibleData();
        $this->presupuestosSeleccionados[$presupuestoId] = (int) $this->cantidadesInput[$presupuestoId];
        $this->actualizarSumario();
        $this->cerrarModalOrdenCombustibleActual();
    }

    public function editarOrdenCombustible(int $presupuestoId)
    {
        if (!isset($this->presupuestosSeleccionados[$presupuestoId])) {
            return;
        }

        $recurso = collect($this->recursosSeleccionados)->firstWhere('id', $presupuestoId);
        if (!$recurso || empty($recurso['es_combustible'])) {
            return;
        }

        $this->combustibleEnModal = $presupuestoId;
        $this->cantidadDisponibleCombustibleModal = $recurso['cantidad_disponible'] ?? $this->obtenerCantidadDisponible($presupuestoId);
        $this->modoOrdenCombustible = 'editar';
        $this->ordenCombustibleRecursoId = $presupuestoId;
        $this->ordenCombustibleRecursoNombre = $recurso['nombre'] ?? '';
        $this->cantidadesInput[$presupuestoId] = $recurso['cantidad_seleccionada'] ?? ($this->cantidadesInput[$presupuestoId] ?? 1);
        $this->ordenCombustibleData = $this->ordenCombustibleDataDesdeOrden($this->ordenesCombustible[$presupuestoId] ?? []);
        $this->actualizarMontoOrdenCombustible($presupuestoId);
        $this->cargarEmpleadosOrdenCombustible($this->ordenCombustibleData['responsable'] ?? null);
        $this->showOrdenCombustibleModal = true;
    }

    public function confirmarEdicionOrden()
    {
        if (!$this->combustibleEnModal) {
            return;
        }

        if (!$this->validarCantidadInput($this->combustibleEnModal)) {
            return;
        }

        $this->validarOrdenCombustibleData();
        $this->ordenesCombustible[$this->combustibleEnModal] = $this->ordenDesdeOrdenCombustibleData();
        $this->presupuestosSeleccionados[$this->combustibleEnModal] = (int) $this->cantidadesInput[$this->combustibleEnModal];
        $this->actualizarSumario();
        $this->cerrarModalOrdenCombustibleActual();
    }

    public function cerrarModalOrdenCombustibleActual()
    {
        $presupuestoId = $this->combustibleEnModal;

        if ($presupuestoId) {
            unset($this->erroresCantidad[$presupuestoId]);
            unset($this->cantidadesInput[$presupuestoId]);
        }

        $this->combustibleEnModal = null;
        $this->cantidadDisponibleCombustibleModal = null;
        $this->modoOrdenCombustible = null;
        $this->ordenCombustibleRecursoId = null;
        $this->ordenCombustibleRecursoNombre = null;
        $this->ordenCombustibleData = null;
        $this->showOrdenCombustibleModal = false;
        $this->resetValidation();
    }

    private function validarCantidadInput(int $presupuestoId): bool
    {
        unset($this->erroresCantidad[$presupuestoId]);

        $cantidad = $this->cantidadesInput[$presupuestoId] ?? null;
        if ($cantidad === null || $cantidad === '' || (int) $cantidad <= 0) {
            $this->erroresCantidad[$presupuestoId] = 'Ingrese una cantidad mayor a 0.';
            return false;
        }

        $disponible = $this->obtenerCantidadDisponible($presupuestoId);
        if ((int) $cantidad > $disponible) {
            $this->erroresCantidad[$presupuestoId] = "La cantidad no puede superar el disponible ({$disponible}).";
            return false;
        }

        return true;
    }

    private function obtenerCantidadDisponible(int $presupuestoId): int
    {
        $presupuesto = Presupuesto::find($presupuestoId);
        if (!$presupuesto) {
            return 0;
        }

        $cantidadComprometida = DetalleRequisicion::where('idPresupuesto', $presupuesto->id)
            ->whereHas('requisicion', function ($query) {
                $query->whereHas('estado', function ($estado) {
                    $estado->whereIn('estado', ['Presentado', 'Recibido', 'En Proceso de Compra']);
                });
            })
            ->sum('cantidad');

        return max(0, (int) ($presupuesto->cantidad ?? 0) - (int) $cantidadComprometida);
    }

    private function actualizarMontoOrdenCombustible(int $presupuestoId): void
    {
        $presupuesto = Presupuesto::find($presupuestoId);
        $cantidad = (int) ($this->cantidadesInput[$presupuestoId] ?? 0);
        $monto = max(0, $cantidad) * (float) ($presupuesto->costounitario ?? 0);

        $this->ordenCombustibleData['monto'] = $monto;
        $this->ordenCombustibleData['monto_en_letras'] = $this->numeroALetras($monto);
    }

    private function actualizarCantidadRecursoSeleccionado(int $presupuestoId): void
    {
        $recurso = collect($this->recursosSeleccionados)->firstWhere('id', $presupuestoId);
        if (!$recurso || !empty($recurso['es_combustible'])) {
            return;
        }

        $disponible = $this->obtenerCantidadDisponible($presupuestoId);
        $cantidad = (int) ($this->presupuestosSeleccionados[$presupuestoId] ?? 1);

        unset($this->erroresCantidad[$presupuestoId]);

        if ($cantidad <= 0) {
            $cantidad = 1;
            $this->erroresCantidad[$presupuestoId] = 'Ingrese una cantidad mayor a 0.';
        }

        if ($disponible > 0 && $cantidad > $disponible) {
            $cantidad = $disponible;
            $this->erroresCantidad[$presupuestoId] = "La cantidad no puede superar el disponible ({$disponible}).";
        }

        $this->presupuestosSeleccionados[$presupuestoId] = $cantidad;

        foreach ($this->recursosSeleccionados as $index => $item) {
            if ((int) $item['id'] !== $presupuestoId) {
                continue;
            }

            $this->recursosSeleccionados[$index]['cantidad_seleccionada'] = $cantidad;
            $this->recursosSeleccionados[$index]['total'] = $cantidad * (float) ($item['precio_unitario'] ?? 0);
            $this->recursosSeleccionados[$index]['cantidad_disponible'] = $disponible;
            break;
        }
    }

    private function construirRecursoSeleccionado(Presupuesto $presupuesto, int $cantidad): array
    {
        $tarea = $presupuesto->idtarea ? Tarea::with('actividad')->find($presupuesto->idtarea) : null;

        $recursoSeleccionado = [
            'id'                    => $presupuesto->id,
            'nombre'                => $presupuesto->recurso,
            'idHistorico'           => $presupuesto->idHistorico,
            'actividad'             => $tarea
                ? (($tarea->actividad->nombre ?? '-') . ' / ' . ($tarea->nombre ?? '-'))
                : '-',
            'proceso_compra'        => $presupuesto->tareaHistorico && $presupuesto->tareaHistorico->procesoCompra
                ? $presupuesto->tareaHistorico->procesoCompra->nombre_proceso
                : '-',
            'cantidad_seleccionada' => $cantidad,
            'unidad_medida'         => $presupuesto->unidadMedida->nombre ?? '-',
            'precio_unitario'       => $presupuesto->costounitario ?? 0,
            'total'                 => $cantidad * ($presupuesto->costounitario ?? 0),
            'idPoa'                 => $tarea?->idPoa,
            'cantidad_disponible'   => $this->obtenerCantidadDisponible($presupuesto->id),
        ];

        $recursoSeleccionado['es_combustible'] = $this->esRecursoCombustible($recursoSeleccionado);

        return $recursoSeleccionado;
    }

    private function validarOrdenCombustibleData(): void
    {
        $this->validate([
            'ordenCombustibleData.modelo_vehiculo' => 'required|string|max:255',
            'ordenCombustibleData.placa' => 'required|string|max:255',
            'ordenCombustibleData.lugar_salida' => 'required|string|max:255',
            'ordenCombustibleData.lugar_destino' => 'required|string|max:255',
            'ordenCombustibleData.recorrido_km' => 'required|numeric|min:0',
            'ordenCombustibleData.fecha_actividad' => 'required|date',
            'ordenCombustibleData.responsable' => 'required|exists:empleados,id',
            'ordenCombustibleData.actividades_realizar' => 'required|string|max:1000',
        ], [
            'ordenCombustibleData.*.required' => 'Este campo es obligatorio.',
            'ordenCombustibleData.responsable.exists' => 'Seleccione un empleado valido.',
        ]);
    }

    private function ordenDesdeOrdenCombustibleData(): array
    {
        return [
            'confirmada' => true,
            'modelo_vehiculo' => $this->ordenCombustibleData['modelo_vehiculo'] ?? '',
            'placa' => $this->ordenCombustibleData['placa'] ?? '',
            'lugar_salida' => $this->ordenCombustibleData['lugar_salida'] ?? '',
            'lugar_destino' => $this->ordenCombustibleData['lugar_destino'] ?? '',
            'recorrido_km' => $this->ordenCombustibleData['recorrido_km'] ?? 0,
            'fecha_realizar' => $this->ordenCombustibleData['fecha_actividad'] ?? '',
            'idEmpleado' => $this->ordenCombustibleData['responsable'] ?? null,
            'actividades' => $this->ordenCombustibleData['actividades_realizar'] ?? '',
        ];
    }

    private function ordenCombustibleDataDesdeOrden(array $orden): array
    {
        return [
            'modelo_vehiculo' => $orden['modelo_vehiculo'] ?? '',
            'placa' => $orden['placa'] ?? '',
            'lugar_salida' => $orden['lugar_salida'] ?? '',
            'lugar_destino' => $orden['lugar_destino'] ?? '',
            'recorrido_km' => $orden['recorrido_km'] ?? 0,
            'fecha_actividad' => $orden['fecha_realizar'] ?? '',
            'responsable' => $orden['idEmpleado'] ?? '',
            'actividades_realizar' => $orden['actividades'] ?? '',
            'monto' => $orden['monto'] ?? 0,
            'monto_en_letras' => $orden['monto_en_letras'] ?? '',
        ];
    }

    public function abrirModalRequisicion()
    {
        if (!$this->puedeCrearRequisicion) {
            $this->modalRequisicionError = $this->mensajePlazoRequisicion;
            return;
        }

        $this->modalRequisicionError = '';

        if (empty($this->recursosSeleccionados)) {
            $this->modalRequisicionError = 'Seleccione al menos un recurso para revisar el sumario.';
            return;
        }

        $this->pasoActual = 1;
        $this->showModalRequisicion = true;
    }

    public function cerrarModalRequisicion()
    {
        $this->showModalRequisicion = false;
        $this->modalRequisicionError = '';
    }

    public function confirmarOrdenCombustible($presupuestoId)
    {
        $this->validate([
            "ordenesCombustible.$presupuestoId.modelo_vehiculo" => 'required|string|max:255',
            "ordenesCombustible.$presupuestoId.placa" => 'required|string|max:255',
            "ordenesCombustible.$presupuestoId.lugar_salida" => 'required|string|max:255',
            "ordenesCombustible.$presupuestoId.lugar_destino" => 'required|string|max:255',
            "ordenesCombustible.$presupuestoId.recorrido_km" => 'required|numeric|min:0',
            "ordenesCombustible.$presupuestoId.fecha_realizar" => 'required|date',
            "ordenesCombustible.$presupuestoId.idEmpleado" => 'required|exists:empleados,id',
            "ordenesCombustible.$presupuestoId.actividades" => 'required|string|max:1000',
        ], [
            "ordenesCombustible.$presupuestoId.*.required" => 'Este campo es obligatorio.',
            "ordenesCombustible.$presupuestoId.idEmpleado.exists" => 'Seleccione un empleado valido.',
        ]);

        $this->ordenesCombustible[$presupuestoId]['confirmada'] = true;
        $this->modalRequisicionError = '';
    }

    public function siguientePaso()
    {
        $this->modalRequisicionError = '';

        if ($this->pasoActual === 1) {
            $this->pasoActual = 2;
        }
    }

    public function anteriorPaso()
    {
        $this->modalRequisicionError = '';

        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
    }

    public function confirmarRequisicion()
    {
        $this->modalRequisicionError = '';

        $this->validate([
            'descripcion' => 'required|string|max:500',
            'fechaRequerida' => 'required|date|after_or_equal:today',
            'observacion' => 'nullable|string|max:1000',
        ], [
            'descripcion.required' => 'La descripcion es obligatoria.',
            'fechaRequerida.required' => 'La fecha requerida es obligatoria.',
            'fechaRequerida.after_or_equal' => 'La fecha requerida no puede ser anterior a hoy.',
        ]);

        try {
            $requisicionCreada = null;

            DB::transaction(function () use (&$requisicionCreada) {
                if (empty($this->recursosSeleccionados)) {
                    throw new \Exception('No hay recursos seleccionados para crear la requisicion.');
                }

                foreach ($this->recursosSeleccionados as $recurso) {
                    $this->validarDisponibilidadRecurso($recurso);
                }

                if (!$this->todasLasOrdenesCombustibleConfirmadas()) {
                    throw new \Exception('Debe confirmar todas las ordenes de combustible antes de crear la requisicion.');
                }

                $user = Auth::user();
                $this->idPoa = $this->idPoa ?: ($this->recursosSeleccionados[0]['idPoa'] ?? null);

                if (!$this->idPoa) {
                    $presupuesto = Presupuesto::find($this->recursosSeleccionados[0]['id']);
                    $tarea = $presupuesto?->idtarea ? Tarea::find($presupuesto->idtarea) : null;
                    $this->idPoa = $tarea?->idPoa;
                }

                $poa = $this->idPoa ? Poa::find($this->idPoa) : null;
                $this->idDepartamento = $this->departamentoSeleccionado ?: (Auth::user()->idDepartamento ?? null);

                if (!$this->idDepartamento) {
                    $empleadoDepto = DB::table('empleado_deptos')
                        ->where('idEmpleado', Auth::user()->idEmpleado ?? Auth::id())
                        ->whereNull('deleted_at')
                        ->first();
                    $this->idDepartamento = $empleadoDepto?->idDepto;
                }

                if (!$this->idPoa || !$this->idDepartamento) {
                    throw new \Exception('No se pudo determinar el POA o departamento para la requisicion.');
                }

                $departamento = Departamento::find($this->idDepartamento);
                $ultimo = RequisicionModel::orderBy('id', 'desc')->first();
                $numero = $ultimo ? $ultimo->id + 1 : 1;
                $correlativo = \App\Helpers\CorrelativoHelper::generarCorrelativo(
                    $departamento->tipo ?? '',
                    $departamento->name ?? '',
                    $poa?->anio ?? date('Y'),
                    $numero
                );

                $requisicion = RequisicionModel::create([
                    'correlativo' => $correlativo,
                    'descripcion' => $this->descripcion,
                    'observacion' => $this->observacion ?: '',
                    'created_by' => $user->id,
                    'approvedBy' => null,
                    'idPoa' => $this->idPoa,
                    'idDepartamento' => $this->idDepartamento,
                    'idEstado' => $this->getEstadoPresentadoId(),
                    'fechaSolicitud' => now(),
                    'fechaRequerido' => $this->fechaRequerida,
                ]);
                $requisicionCreada = $requisicion;

                foreach ($this->recursosSeleccionados as $recurso) {
                    $presupuesto = Presupuesto::find($recurso['id']);

                    if (!$presupuesto) {
                        throw new \Exception("No se encontro el presupuesto del recurso {$recurso['nombre']}.");
                    }

                    $detalle = DetalleRequisicion::create([
                        'idRequisicion' => $requisicion->id,
                        'idPoa' => $this->idPoa,
                        'idPresupuesto' => $presupuesto->id,
                        'idRecurso' => $presupuesto->idHistorico,
                        'cantidad' => $recurso['cantidad_seleccionada'],
                        'idUnidadMedida' => $presupuesto->idunidad,
                        'entregado' => false,
                        'created_by' => $user->id,
                    ]);

                    if (!empty($recurso['es_combustible'])) {
                        $orden = $this->ordenesCombustible[$recurso['id']] ?? null;

                        if (!$orden || empty($orden['confirmada'])) {
                            throw new \Exception("Falta confirmar la orden de combustible para {$recurso['nombre']}.");
                        }

                        $this->crearOrdenesCombustibleParaDetalle($orden, $recurso, $presupuesto, $detalle, $user->id);
                    }
                }
            });

            if ($requisicionCreada) {
                $this->enviarCorreoRequisicionCreada($requisicionCreada);
            }

            $this->limpiarEstadoModal();
            session()->flash('message', 'Requisicion creada correctamente.');
            return redirect()->route('requisicion');
        } catch (\Throwable $e) {
            $this->modalRequisicionError = $e->getMessage();
            $this->showModalRequisicion = true;
        }
    }

    public function limpiarEstadoModal()
    {
        $this->recursosSeleccionados = [];
        $this->presupuestosSeleccionados = [];
        $this->cantidadesInput = [];
        $this->erroresCantidad = [];
        $this->ordenesCombustible = [];
        $this->descripcion = '';
        $this->fechaRequerida = '';
        $this->observacion = '';
        $this->pasoActual = 1;
        $this->showModalRequisicion = false;
        $this->modalRequisicionError = '';
        $this->cerrarModalCantidad();
        session()->forget('recursosSeleccionados');
        session()->forget('departamentoSeleccionado');
        session()->forget('poaYearSeleccionado');
    }

    private function validarDisponibilidadRecurso(array $recurso): void
    {
        $presupuesto = Presupuesto::find($recurso['id']);

        if (!$presupuesto) {
            throw new \Exception("No se encontro el presupuesto del recurso {$recurso['nombre']}.");
        }

        $cantidadComprometida = DetalleRequisicion::where('idPresupuesto', $presupuesto->id)
            ->whereHas('requisicion', function ($query) {
                $query->whereHas('estado', function ($estado) {
                    $estado->whereIn('estado', ['Presentado', 'Recibido', 'En Proceso de Compra']);
                });
            })
            ->sum('cantidad');

        $disponible = (int) ($presupuesto->cantidad ?? 0) - (int) $cantidadComprometida;

        if ((int) $recurso['cantidad_seleccionada'] > $disponible) {
            throw new \Exception("El recurso {$recurso['nombre']} ya no tiene cupo suficiente. Disponible actual: {$disponible}.");
        }
    }

    private function tieneCombustiblesSeleccionados(): bool
    {
        return collect($this->recursosSeleccionados)->contains(fn ($recurso) => !empty($recurso['es_combustible']));
    }

    private function hayCombustiblesSinConfirmar(): bool
    {
        return collect($this->recursosSeleccionados)
            ->filter(fn ($recurso) => !empty($recurso['es_combustible']))
            ->contains(fn ($recurso) => empty($this->ordenesCombustible[$recurso['id']]['confirmada']));
    }

    private function todasLasOrdenesCombustibleConfirmadas(): bool
    {
        return collect($this->recursosSeleccionados)
            ->filter(fn ($recurso) => !empty($recurso['es_combustible']))
            ->every(fn ($recurso) => !empty($this->ordenesCombustible[$recurso['id']]['confirmada']));
    }

    private function generarCorrelativoOrdenCombustible(): string
    {
        $ultimoId = (int) DB::table('orden_combustible')->max('id');
        return ($ultimoId + 1) . '-' . now()->format('Y');
    }

    private function crearOrdenesCombustibleParaDetalle(array $orden, array $recurso, Presupuesto $presupuesto, DetalleRequisicion $detalle, int $userId): void
    {
        $cantidadOrdenes = max(1, (int) ($recurso['cantidad_seleccionada'] ?? 1));
        $montoPorOrden = (float) ($recurso['precio_unitario'] ?? $presupuesto->costounitario ?? 0);

        for ($i = 0; $i < $cantidadOrdenes; $i++) {
            DB::table('orden_combustible')->insert([
                'correlativo' => $this->generarCorrelativoOrdenCombustible(),
                'monto' => $montoPorOrden,
                'monto_en_letras' => $this->numeroALetras($montoPorOrden),
                'modelo_vehiculo' => $orden['modelo_vehiculo'],
                'lugar_salida' => $orden['lugar_salida'],
                'lugar_destino' => $orden['lugar_destino'],
                'placa' => $orden['placa'],
                'recorrido_km' => $orden['recorrido_km'],
                'fecha_actividad' => $orden['fecha_realizar'],
                'actividades_realizar' => $orden['actividades'],
                'idPoa' => $this->idPoa,
                'idDetalleRequisicion' => $detalle->id,
                'idRecurso' => $presupuesto->idHistorico,
                'responsable' => $orden['idEmpleado'],
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function numeroALetras($numero): string
    {
        $entero = (int) $numero;
        $unidades = [
            '', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE',
            'DIEZ', 'ONCE', 'DOCE', 'TRECE', 'CATORCE', 'QUINCE', 'DIECISEIS', 'DIECISIETE',
            'DIECIOCHO', 'DIECINUEVE',
        ];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = [
            '', 'CIEN', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS',
            'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS',
        ];

        $convertir = function ($n) use ($unidades, $decenas, $centenas, &$convertir) {
            if ($n == 0) {
                return '';
            }

            if ($n < 20) {
                return $unidades[$n];
            }

            if ($n < 100) {
                $d = intdiv($n, 10);
                $u = $n % 10;
                return $decenas[$d] . ($u ? ' Y ' . $unidades[$u] : '');
            }

            if ($n < 1000) {
                $c = intdiv($n, 100);
                $resto = $n % 100;
                $prefijo = $c == 1 && $resto > 0 ? 'CIENTO' : $centenas[$c];
                return $prefijo . ($resto ? ' ' . $convertir($resto) : '');
            }

            if ($n < 1000000) {
                $miles = intdiv($n, 1000);
                $resto = $n % 1000;
                $prefijo = $miles == 1 ? 'MIL' : $convertir($miles) . ' MIL';
                return $prefijo . ($resto ? ' ' . $convertir($resto) : '');
            }

            return (string) $n;
        };

        return $entero == 0 ? 'CERO' : $convertir($entero);
    }

    // Abrir el modal de sumario
    public function abrirSumario()
    {

        if (!$this->puedeCrearRequisicion) {
            session()->flash('error', $this->mensajePlazoRequisicion);
            return;
        }
        $this->recursosSeleccionados = [];
        // Obtener actividades y presupuestos aprobados 
        $actividades_aprobadas = Tarea::whereHas('presupuestos', function($q) {
            $q->where('cantidad', '>', 0);
        })
        ->where('estado', 'APROBADO')
        ->when($this->buscarActividad, function($q) {
            $q->where(function($subq) {
                $subq->where('nombre', 'like', '%'.$this->buscarActividad.'%');
                $subq->orWhereHas('actividad', function($q2) {
                    $q2->where('nombre', 'like', '%'.$this->buscarActividad.'%');
                });
            });
        })
        ->when($this->departamentoSeleccionado, function($q) {
            // Filtrar por el departamento seleccionado a través de tareas relacionadas con presupuestos
            $q->where('idDeptartamento', $this->departamentoSeleccionado);
        })
        ->with(['presupuestos' => function($query) {
            $query->where('cantidad', '>', 0); // Filtrar presupuestos con cantidad mayor a 0
        }, 'presupuestos.objetoGasto', 'presupuestos.mes', 'presupuestos.unidadMedida', 'presupuestos.fuente', 'actividad'])
        ->paginate($this->perPage);

        foreach ($this->presupuestosSeleccionados as $presupuestoId => $cantidad) {
            if ($cantidad > 0) {
                foreach ($actividades_aprobadas as $actividad) {
                    $presupuesto = $actividad->presupuestos->where('id', $presupuestoId)->first();
                    if ($presupuesto) {
                        $this->recursosSeleccionados[] = [
                            'id' => $presupuesto->id,
                            'nombre' => $presupuesto->recurso,
                            'idHistorico' => $presupuesto->idHistorico,
                            'actividad' => ($actividad->actividad->nombre ?? '-') . ' / ' . ($actividad->nombre ?? '-'),
                            'proceso_compra' => $presupuesto->tareaHistorico && $presupuesto->tareaHistorico->procesoCompra ? $presupuesto->tareaHistorico->procesoCompra->nombre_proceso : '-',
                            'cantidad_seleccionada' => $cantidad,
                            'unidad_medida' => $presupuesto->unidadMedida->nombre ?? '-',
                            'precio_unitario' => $presupuesto->costounitario ?? 0,
                            'total' => $cantidad * ($presupuesto->costounitario ?? 0),
                        ];
                        break;
                    }
                }
            }
        }
        $this->showSumarioModal = true;
    }

    /*protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];*/

    public function updatedCorrelativo($value)
    {
        $this->correlativo = is_array($value) ? '' : $value;
    }

    public function buscar() {}

    public function sortBy($field)
    {
        // Validate the sort field against valid columns in the requisicion table
        $validColumns = ['id', 'correlativo', 'descripcion', 'fechaSolicitud', 'fechaRequerido']; // Add other valid columns here
        if (!in_array($field, $validColumns)) {
            $field = 'id'; // Default to a valid column
        }

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

    public function updatingBusqueda()
    {
        $this->resetPage();
    }

    public function updatingEstado()
    {
        $this->resetPage();
    }

    public function resetInputFields()
    {
        $this->correlativo = '';
        $this->descripcion = '';
        $this->observacion = '';
        $this->created_by = Auth::id();
        $this->approved_by = null;
        $this->idPoa = null;
        $this->idDepartamento = Auth::user()->idDepartamento ?? null;
        $this->idEstado = $this->getEstadoPresentadoId();
        $this->fechaSolicitud = now();
        $this->fechaRequerido = null;
        $this->requisicionId = null;
        $this->successMessage = ''; // Resetea el mensaje de éxito
        $this->resetValidation();
    }

    public function create()
    {
        $this->resetInputFields();
        $this->isEditing = false;
        $this->openModal();
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
        // Asignar automáticamente el departamento y estado
        $empleadoDepto = \DB::table('empleado_deptos')
            ->where('idEmpleado', Auth::id())
            ->whereNull('deleted_at')
            ->first();
        $this->idDepartamento = $empleadoDepto ? $empleadoDepto->idDepto : null;
        $this->idEstado = $this->getEstadoPresentadoId();
        try {
            $data = [
                'correlativo' => $this->correlativo,
                'descripcion' => $this->descripcion,
                'observacion' => $this->observacion,
                'created_by' => $this->created_by,
                'approved_by' => $this->approved_by,
                'idPoa' => $this->idPoa,
                'idDepartamento' => $this->idDepartamento,
                'idEstado' => $this->idEstado,
                'fechaSolicitud' => $this->fechaSolicitud,
                'fechaRequerido' => $this->fechaRequerido,
            ];
            $requisicion = RequisicionModel::updateOrCreate(
                ['id' => $this->requisicionId],
                $data
            );
            $this->successMessage = $this->requisicionId
                ? 'Requisición actualizada correctamente.'
                : 'Requisición creada correctamente.';
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar: ' . $e->getMessage();
            $this->showErrorModal = true;
        }
    }

    protected function getEstadoPresentadoId()
    {
        $estado = \DB::table('estado_requisicion')->where('estado', 'Presentado')->first();
        return $estado ? $estado->id : null;
    }

    public function edit($id)
    {
        $requisicion = RequisicionModel::findOrFail($id);
        $this->requisicionId = $id;
        $this->correlativo = $requisicion->correlativo;
        $this->descripcion = $requisicion->descripcion;
        $this->observacion = $requisicion->observacion;
        $this->created_by = $requisicion->created_by;
        $this->approved_by = $requisicion->approved_by;
        $this->idPoa = $requisicion->idPoa;
        $this->idDepartamento = $requisicion->idDepartamento;
        $this->idEstado = $requisicion->idEstado;
        $this->fechaSolicitud = $requisicion->fechaSolicitud;
        $this->fechaRequerido = $requisicion->fechaRequerido;
        $this->isEditing = true;
        // Cargar detalles de la requisición con relaciones
        $this->detalleRequisiciones = $requisicion->detalleRequisiciones()->with(['recurso', 'presupuesto'])->get();
        $this->openModal();
    }

    public function confirmDelete($id)
    {
        $this->requisicionToDelete = RequisicionModel::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        try {
            $requisicionId = $this->requisicionToDelete->id;
            $this->requisicionToDelete->delete();
            session()->flash('message', 'Requisición eliminada correctamente.');
            $this->showDeleteModal = false;
            $this->resetPage();
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar la requisición: ' . $e->getMessage();
            $this->showDeleteModal = false;
            $this->showErrorModal = true;
        }
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->requisicionToDelete = null;
    }

    public function closeErrorModal()
    {
        $this->showErrorModal = false;
        $this->errorMessage = '';
    }

   public function mount()
    {
        $this->cargarEmpleadosOrdenCombustible();

        $this->departamentoSeleccionado = session('departamentoSeleccionado');
        
        $userId = Auth::id();
        $this->departamentosUsuario = Departamento::whereHas('empleados', function($q) use ($userId) {
            $q->where('empleados.id', $userId);
        })->with('unidadEjecutora')->get();

        $this->mostrarSelector = $this->departamentosUsuario->count() > 1;

        if ($this->departamentosUsuario->count() == 1) {
            $this->departamentoSeleccionado = $this->departamentosUsuario->first()->id;
        }
        
        $poaYearGuardado = session('poaYearSeleccionado');
        
        if ($poaYearGuardado) {
            $this->poaYear = $poaYearGuardado;
        } else {
            $poa = Poa::activo()->latest()->first();
            $this->poaYear = $poa?->anio;
        }

        $this->verificarPlazoRequisicion();
    }

    public function updatingBuscarActividad()
    {
        $this->resetPage();
    }

    // LEGADO: este método pertenece al flujo anterior de orden de
    // combustible con persistencia directa en DB. Se conserva comentado
    // por referencia. No usar en el flujo actual.
    public function abrirOrdenCombustibleModal($recursoId)
    {
        $recurso = collect($this->recursosSeleccionados)->firstWhere('id', $recursoId);
        $this->ordenCombustibleRecursoId = $recursoId;
        $this->ordenCombustibleRecursoNombre = $recurso['nombre'] ?? '';
        $this->ordenCombustibleData = [
            'modelo_vehiculo' => '',
            'placa' => '',
            'lugar_salida' => '',
            'lugar_destino' => '',
            'recorrido_km' => 0,
            'fecha_actividad' => '',
            'responsable' => '',
            'actividades_realizar' => '',
        ];
        $this->cargarEmpleadosOrdenCombustible($this->ordenCombustibleData['responsable']);
        $this->showOrdenCombustibleModal = true;
    }

    private function cargarEmpleadosOrdenCombustible($responsableId = null)
    {
        $empleados = Empleado::query()
            ->select('id', 'nombre', 'apellido', 'num_empleado')
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->limit(30)
            ->get();

        if ($responsableId && !$empleados->contains('id', (int) $responsableId)) {
            $responsable = Empleado::select('id', 'nombre', 'apellido', 'num_empleado')->find($responsableId);

            if ($responsable) {
                $empleados->prepend($responsable);
            }
        }

        $this->empleados = $empleados;
    }

    public function searchEmpleadosOrdenCombustible($search = '')
    {
        return Empleado::query()
            ->select('id', 'nombre', 'apellido', 'num_empleado')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', '%' . $search . '%')
                        ->orWhere('apellido', 'like', '%' . $search . '%')
                        ->orWhere('num_empleado', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('nombre')
            ->orderBy('apellido')
            ->limit(30)
            ->get()
            ->map(fn ($empleado) => [
                'id' => $empleado->id,
                'text' => trim($empleado->nombre . ' ' . $empleado->apellido) . ($empleado->num_empleado ? ' - #' . $empleado->num_empleado : ''),
            ])
            ->toArray();
    }

    // LEGADO: este método pertenece al flujo anterior de orden de
    // combustible con persistencia directa en DB. Se conserva comentado
    // por referencia. No usar en el flujo actual.
    public function cerrarOrdenCombustibleModal()
    {
        $this->showOrdenCombustibleModal = false;
        $this->ordenCombustibleRecursoId = null;
        $this->ordenCombustibleRecursoNombre = '';
        $this->ordenCombustibleData = [
            'modelo_vehiculo' => '',
            'placa' => '',
            'lugar_salida' => '',
            'lugar_destino' => '',
            'recorrido_km' => 0,
            'fecha_actividad' => '',
            'responsable' => '',
            'actividades_realizar' => '',
        ];
    }

    public function irAlSumario()
    {

        if (!$this->puedeCrearRequisicion) {
            session()->flash('error', $this->mensajePlazoRequisicion);
            return;
        }

        //dd($this->departamentoSeleccionado); 
        session([
            'recursosSeleccionados' => $this->recursosSeleccionados,
            'departamentoSeleccionado' => $this->departamentoSeleccionado,
            'poaYearSeleccionado'    => $this->poaYear,
        ]);
        return redirect()->route('requisiciones-sumario');
    }

    public function sincronizarDepartamento($id)
    {
        if ($this->departamentoSeleccionado != $id) {
            $this->recursosSeleccionados = [];
            $this->presupuestosSeleccionados = [];
            $this->cantidadesInput = [];
            $this->erroresCantidad = [];
            $this->ordenesCombustible = [];
            $this->showModalRequisicion = false;
            $this->pasoActual = 1;
            $this->modalRequisicionError = '';
            $this->cerrarModalCantidad();
            session()->forget('recursosSeleccionados');
        }
        
        $this->departamentoSeleccionado = $id;
        $this->resetPage();
    }

    // LEGADO: este método pertenece al flujo anterior de orden de
    // combustible con persistencia directa en DB. Se conserva comentado
    // por referencia. No usar en el flujo actual.
    public function guardarOrdenCombustible()
    {
        $this->validate([
            'ordenCombustibleData.modelo_vehiculo' => 'required',
            'ordenCombustibleData.placa' => 'required',
            'ordenCombustibleData.lugar_salida' => 'required',
            'ordenCombustibleData.lugar_destino' => 'required',
            'ordenCombustibleData.recorrido_km' => 'required|numeric',
            'ordenCombustibleData.fecha_actividad' => 'required|date',
            'ordenCombustibleData.responsable' => 'required|exists:empleados,id',
            'ordenCombustibleData.actividades_realizar' => 'required',
        ], [
            'ordenCombustibleData.*.required' => 'Este campo es obligatorio.',
        ]);

        // Obtener idPoa del recurso seleccionado si no está definido
        if (empty($this->idPoa) && $this->ordenCombustibleRecursoId) {
            $presupuesto = Presupuesto::find($this->ordenCombustibleRecursoId);
            if ($presupuesto && $presupuesto->idtarea) {
                $tarea = Tarea::find($presupuesto->idtarea);
                if ($tarea && $tarea->idPoa) {
                    $this->idPoa = $tarea->idPoa;
                }
            }
        }

        $idDetalleRequisicion = null;

        // Buscar o crear requisición
        $requisicion = RequisicionModel::where('idPoa', $this->idPoa)
            ->where('created_by', \Auth::id())
            ->orderByDesc('id')
            ->first();

        // Si no existe requisición, crear una nueva
        if (!$requisicion) {
            $poa = Poa::find($this->idPoa);
            if ($poa) {
                // Obtener el departamento del usuario actual
                $empleadoDepto = \DB::table('empleado_deptos')
                    ->where('idEmpleado', \Auth::id())
                    ->whereNull('deleted_at')
                    ->first();

                $idDepartamento = $empleadoDepto ? $empleadoDepto->idDepto : (\Auth::user()->idDepartamento ?? null);

                if (!$idDepartamento) {
                    throw new \Exception('No se pudo determinar el departamento del usuario.');
                }

                // Generar correlativo siguiendo la misma lógica del módulo de requisiciones
                $departamento = \App\Models\Departamento\Departamento::find($idDepartamento);
                $tipoDepto = $departamento->tipo ?? '';
                $nombreDepto = $departamento->name ?? '';
                $ultimo = RequisicionModel::orderBy('id', 'desc')->first();
                $numero = $ultimo ? ($ultimo->id + 1) : 1;
                $anio = $poa->anio ?? now()->format('Y');
                $correlativo = \App\Helpers\CorrelativoHelper::generarCorrelativo($tipoDepto, $nombreDepto, $anio, $numero);

                // Estado inicial
                $estadoInicial = $this->getEstadoPresentadoId();

                $requisicion = RequisicionModel::create([
                    'correlativo' => $correlativo,
                    'descripcion' => $this->descripcion,
                    'observacion' => $this->observacion,
                    'idPoa' => $this->idPoa,
                    'idDepartamento' => $idDepartamento,
                    'idEstado' => $estadoInicial,
                    'fechaSolicitud' => now(),
                    'fechaRequerido' => now(),
                    'created_by' => \Auth::id(),
                ]);
            }
        }

        if ($requisicion) {
            // Buscar detalle existente
            $detalle = DetalleRequisicion::where('idRequisicion', $requisicion->id)
                ->where('idPresupuesto', $this->ordenCombustibleRecursoId)
                ->orderByDesc('id')
                ->first();
            
            if ($detalle) {
                $idDetalleRequisicion = $detalle->id;
            }
        }

        // Si no se encontró detalle, intentar buscar por POA y Presupuesto
        if (!$idDetalleRequisicion) {
            $detalle = DetalleRequisicion::where('idPoa', $this->idPoa)
                ->where('idPresupuesto', $this->ordenCombustibleRecursoId)
                ->orderByDesc('id')
                ->first();
            if ($detalle) {
                $idDetalleRequisicion = $detalle->id;
            }
        }

        // Si aún no existe detalle, crear uno nuevo
        if (!$idDetalleRequisicion && $requisicion) {
            $presupuesto = Presupuesto::find($this->ordenCombustibleRecursoId);
            $idRecurso = $presupuesto ? ($presupuesto->idHistorico ?? $presupuesto->idrecurso ?? null) : null;
            $idUnidadMedida = $presupuesto ? ($presupuesto->idunidad ?? $presupuesto->idUnidadMedida ?? null) : null;

            if ($idRecurso && $idUnidadMedida) {
                $detalleNuevo = DetalleRequisicion::create([
                    'idRequisicion' => $requisicion->id,
                    'idPoa' => $this->idPoa,
                    'idPresupuesto' => $this->ordenCombustibleRecursoId,
                    'idRecurso' => $idRecurso,
                    'cantidad' => 1,
                    'idUnidadMedida' => $idUnidadMedida,
                    'entregado' => false,
                    'created_by' => \Auth::id(),
                ]);
                $idDetalleRequisicion = $detalleNuevo->id;
            }
        }

        $this->ordenCombustibleData['idDetalleRequisicion'] = $idDetalleRequisicion;

        $ultimo = RequisicionModel::orderBy('id', 'desc')->first();
        $numero = $ultimo ? ($ultimo->id + 1) : 1;
        $anio = now()->format('Y');
        $correlativo = $numero . '-' . $anio;

        if (empty($this->ordenCombustibleData['idDetalleRequisicion'])) {
            throw new \Exception('idDetalleRequisicion null');
        }

        // Obtener el idHistorico del recurso seleccionado
        $recursoSeleccionado = collect($this->recursosSeleccionados)->firstWhere('id', $this->ordenCombustibleRecursoId);
        $idHistorico = $recursoSeleccionado['idHistorico'] ?? null;

        // Si no está en el array, obtenerlo del presupuesto (por compatibilidad con datos antiguos)
        if (!$idHistorico) {
            $presupuesto = Presupuesto::find($this->ordenCombustibleRecursoId);
            $idHistorico = $presupuesto ? $presupuesto->idHistorico : null;
        }

        if (!$idHistorico) {
            throw new \Exception('No se pudo obtener el recurso histórico para la orden de combustible.');
        }

        \DB::table('orden_combustible')->insert([
            'correlativo' => $correlativo,
            //'monto' => 0,
            //'monto_en_letras' => '',
            'monto' => $this->ordenCombustibleData['monto'],
            'monto_en_letras' => $this->ordenCombustibleData['monto_en_letras'],
            'modelo_vehiculo' => $this->ordenCombustibleData['modelo_vehiculo'],
            'lugar_salida' => $this->ordenCombustibleData['lugar_salida'],
            'lugar_destino' => $this->ordenCombustibleData['lugar_destino'],
            'placa' => $this->ordenCombustibleData['placa'],
            'recorrido_km' => $this->ordenCombustibleData['recorrido_km'],
            'fecha_actividad' => $this->ordenCombustibleData['fecha_actividad'],
            'actividades_realizar' => $this->ordenCombustibleData['actividades_realizar'],
            'idPoa' => $this->idPoa,
            'idDetalleRequisicion' => $this->ordenCombustibleData['idDetalleRequisicion'],
            'idRecurso' => $idHistorico,
            'responsable' => $this->ordenCombustibleData['responsable'],
            'created_by' => \Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Marcar el recurso como que ya tiene orden de combustible
        foreach ($this->recursosSeleccionados as &$recurso) {
            if ($recurso['id'] == $this->ordenCombustibleRecursoId) {
                $recurso['orden_combustible_creada'] = true;
            }
        }
        unset($recurso);

        $this->cerrarOrdenCombustibleModal();
        $this->showSumarioModal = true;
        session()->flash('message', 'Orden de combustible creada correctamente.');
    }

    public function limpiarSumario()
    {
        $this->recursosSeleccionados = [];
        $this->presupuestosSeleccionados = [];
        $this->cantidadesInput = [];
        $this->erroresCantidad = [];
        $this->ordenesCombustible = [];
        $this->showModalRequisicion = false;
        $this->pasoActual = 1;
        $this->modalRequisicionError = '';
        $this->cerrarModalCantidad();
        session()->forget('recursosSeleccionados');
    }

    private function verificarPlazoRequisicion()
    {
        // Obtener el POA correspondiente al año seleccionado
        $poa = Poa::activo()
            ->when($this->poaYear, function ($query) {
                $query->where('anio', $this->poaYear);
            })
            ->first();

        if (!$poa) {
            $this->puedeCrearRequisicion = false;
            $this->mensajePlazoRequisicion = 'No hay un POA activo para el año seleccionado.';
            $this->diasRestantes = null;
            return;
        }

        // Obtener el plazo activo para el POA seleccionado
        $plazo = $poa->plazos()
            ->where('tipo_plazo', 'requerimientos')
            ->where('activo', true)
            ->first();

        if (!$plazo) {
            $this->puedeCrearRequisicion = false;
            $this->mensajePlazoRequisicion = 'No hay un plazo configurado para esta acción.';
            $this->diasRestantes = null;
            return;
        }

        // Validar si el plazo aún no ha iniciado
        if (now()->lt($plazo->fecha_inicio)) {
            $this->puedeCrearRequisicion = false;
            $this->mensajePlazoRequisicion = 'El plazo para esta acción aún no ha iniciado. Inicia el ' . $plazo->fecha_inicio->format('d/m/Y') . '.';
            $this->diasRestantes = null;
            return;
        }

        // Validar si el plazo ya pasó
        if (now()->gt($plazo->fecha_fin)) {
            $this->puedeCrearRequisicion = false;
            $this->mensajePlazoRequisicion = 'El plazo para esta acción ya pasó.';
            $this->diasRestantes = null;
            return;
        }

        // Calcular días restantes como un número entero
        $this->diasRestantes = floor(now()->diffInDays($plazo->fecha_fin, false));
        $this->puedeCrearRequisicion = $this->diasRestantes >= 0;
        $this->diasRestantes = $poa->getDiasRestantes('requerimientos'); 

        if (!$this->puedeCrearRequisicion) {
            $this->mensajePlazoRequisicion = 'El plazo para gestionar requisiciones ha finalizado.';
        }
    }

    public function updatedPoaYear()
    {
        // Actualizar el cálculo del plazo cuando se cambia el POA en el select
        $this->verificarPlazoRequisicion();
        $this->resetPage();
        session(['poaYearSeleccionado' => $this->poaYear]);

        // Limpiar recursos seleccionados al cambiar de POA
        $this->recursosSeleccionados = [];
        $this->presupuestosSeleccionados = [];
        $this->cantidadesInput = [];
        $this->erroresCantidad = [];
        $this->ordenesCombustible = [];
        $this->showModalRequisicion = false;
        $this->pasoActual = 1;
        $this->modalRequisicionError = '';
        $this->cerrarModalCantidad();
        session()->forget('recursosSeleccionados');
    }
     public function render()
    {
        $userId = Auth::id();
        $this->departamentosUsuario = Departamento::whereHas('empleados', function($q) use ($userId) {
            $q->where('empleados.id', $userId);
        })->with('unidadEjecutora')->get();
        $this->mostrarSelector = $this->departamentosUsuario->count() > 1;

        // Obtener el POA activo o el correspondiente al año seleccionado
        $poa = Poa::activo()
            ->when($this->poaYear, function($q) {
                $q->where('anio', $this->poaYear);
            })
            ->first();

        if ($poa) {
            $this->idPoa = $poa->id; // Asignar el idPoa del POA encontrado
        }

        $actividades_aprobadas = Tarea::whereHas('presupuestos', function($q) {
            $q->where('cantidad', '>', 0);
        })
        ->where('estado', 'APROBADO')
        ->when($this->poaYear, function($q) {
            $q->whereHas('poa', function($q2) {
                $q2->where('anio', $this->poaYear);
            });
        })
        ->when($this->buscarActividad, function($q) {
            $q->where(function($subq) {
                $subq->where('nombre', 'like', '%'.$this->buscarActividad.'%');
                $subq->orWhereHas('actividad', function($q2) {
                    $q2->where('nombre', 'like', '%'.$this->buscarActividad.'%');
                });
            });
        })
        ->when($this->departamentoSeleccionado, function($q) {
            // Filtrar por el departamento seleccionado a través de tareas relacionadas con presupuestos
            $q->where('idDeptartamento', $this->departamentoSeleccionado);
        })
        ->with(['presupuestos' => function($query) {
            $query->where('cantidad', '>', 0); // Filtrar presupuestos con cantidad mayor a 0
        }, 'presupuestos.objetoGasto', 'presupuestos.mes', 'presupuestos.unidadMedida', 'presupuestos.fuente', 'actividad'])
        ->paginate($this->perPage);

    $allPresupuestos = collect();
    foreach ($actividades_aprobadas as $actividad) {
        foreach ($actividad->presupuestos as $presupuesto) {
            $allPresupuestos->push($presupuesto);
        }
    }

    $valoresPlanificados = [];
    foreach ($allPresupuestos as $presupuesto) {
        $cantidadPlanificada = DetalleRequisicion::where('idPresupuesto', $presupuesto->id)
            ->whereHas('requisicion', function($q) {
                $q->whereHas('estado', function($q2) {
                    $q2->whereIn('estado', ['Presentado', 'Recibido', 'En Proceso de Compra']);
                });
            })
            ->sum('cantidad');
        $cantidadDisponible = ($presupuesto->cantidad ?? 0) - $cantidadPlanificada;
        $costoUnitario = $presupuesto->costounitario ?? 0;
        $costoDisponible = $cantidadDisponible * $costoUnitario;
        $costoPlanificado = $cantidadPlanificada * $costoUnitario;
        $valoresPlanificados[$presupuesto->id] = [
            'cantidad_disponible' => $cantidadDisponible,
            'cantidad_planificada' => $cantidadPlanificada,
            'costo_disponible' => $costoDisponible,
            'costo_planificado' => $costoPlanificado,
        ];
    }

    $poas = Poa::activo()->get();
    $this->poaYears = $poas->pluck('anio')->unique()->sort()->values(); // Obtener años únicos de los POA activos

    return view('livewire.seguimiento.Requisicion.create-requisiciones', [
        'mostrarSelector' => $this->mostrarSelector,
        'departamentosUsuario' => $this->departamentosUsuario,
        'departamentoSeleccionado' => $this->departamentoSeleccionado,
        'actividades_aprobadas' => $actividades_aprobadas, // Pasar las actividades filtradas a la vista
        'valoresPlanificados' => $valoresPlanificados,
        'poaYears' => $this->poaYears, // Pasar los años únicos a la vista
        'puedeCrearRequisicion' => $this->puedeCrearRequisicion,
        'mensajePlazoRequisicion' => $this->mensajePlazoRequisicion,
        'diasRestantes' => $this->diasRestantes, // Pass remaining days to the view
    ])->layout($this->layout);
    }
}
