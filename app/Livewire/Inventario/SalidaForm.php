<?php

namespace App\Livewire\Inventario;

use App\Models\Actas\ActaEntrega;
use App\Models\Actas\DetalleActaEntrega;
use App\Models\Actas\TipoActaEntrega;
use App\Models\Departamento\Departamento;
use App\Models\Empleados\Empleado;
use App\Models\EjecucionPresupuestaria\DetalleEjecucionPresupuestaria;
use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioExistencia;
use App\Models\Inventario\InventarioProducto;
use App\Models\Inventario\InventarioSalida;
use App\Models\Inventario\InventarioSalidaDetalle;
use App\Models\EjecucionPresupuestaria\EjecucionPresupuestaria;
use App\Models\EjecucionPresupuestaria\EjecucionPresupuestariaLog;
use App\Models\Requisicion\EstadoRequisicion;
use App\Models\Requisicion\EstadoRequisicionLog;
use App\Models\Requisicion\Requisicion;
use App\Services\Inventario\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SalidaForm extends Component
{
    public ?int $salidaId = null;
    public string $numero_salida = '';
    public ?int $bodega_id = null;
    public ?int $acta_entrega_id = null;
    public ?int $requisicion_id = null;
    public ?string $tipoActaPendiente = null;
    public string $tipo_salida = 'entrega';
    public ?string $motivo = null;
    public ?int $departamento_id = null;
    public ?int $empleado_recibe_id = null;
    public ?int $responsable_entrega_id = null;
    public string $fecha_salida = '';
    public ?string $observacion = null;
    public array $detalles = [];
    public array $productosPorDetalleActa = [];
    public array $detallesActaDisponibles = [];
    public int $paso = 1;
    public bool $actaBloqueada = false;
    public bool $showProductoModal = false;
    public bool $showGuardarModal = false;
    public bool $showFinalizarModal = false;
    public bool $showAdvertenciaModal = false;
    public string $advertenciaTitulo = '';
    public string $advertenciaMensaje = '';
    public array $nuevoDetalle = [
        'detalle_acta_entrega_id' => null,
        'producto_id' => null,
        'lote_id' => null,
        'cantidad' => 1,
    ];

    public function mount(?InventarioSalida $salida = null, ?ActaEntrega $acta = null, ?Requisicion $requisicion = null): void
    {
        if ($salida?->exists) {
            abort_unless(in_array($salida->estado, ['borrador', 'confirmado'], true), 404);
            $salida->load('detalles');
            $this->salidaId = $salida->id;
            $this->actaBloqueada = true;
            $this->paso = $salida->estado === 'confirmado' ? 3 : 1;
            $this->fill($salida->only([
                'numero_salida', 'bodega_id', 'acta_entrega_id', 'requisicion_id', 'tipo_salida',
                'motivo', 'departamento_id', 'empleado_recibe_id', 'responsable_entrega_id', 'observacion',
            ]));
            $this->fecha_salida = $salida->fecha_salida?->format('Y-m-d') ?? now()->toDateString();
            $this->detalles = $salida->detalles->map(fn ($detalle) => [
                'id' => $detalle->id,
                'detalle_acta_entrega_id' => $detalle->detalle_acta_entrega_id,
                'producto_id' => $detalle->producto_id,
                'lote_id' => $detalle->lote_id,
                'cantidad' => $detalle->cantidad,
            ])->toArray();

            if ($this->acta_entrega_id) {
                $this->prepararContextoActa($this->acta_entrega_id, false);
            }

            return;
        }

        $this->numero_salida = 'SAL-' . now()->format('YmdHis');
        $this->bodega_id = $this->defaultBodegaId();
        $this->fecha_salida = now()->toDateString();
        $this->responsable_entrega_id = Auth::id();
        $this->detalles = [];

        if ($acta?->exists) {
            $this->acta_entrega_id = $acta->id;
            $this->actaBloqueada = true;
            $this->prepararContextoActa($acta->id);
            return;
        }

        if ($requisicion?->exists) {
            $this->tipoActaPendiente = 'intermedia';
            $this->requisicion_id = $requisicion->id;
            $this->departamento_id = $requisicion->idDepartamento;
            $this->actaBloqueada = true;
            $this->prepararContextoIntermediaPendiente($requisicion->id);
        }
    }

    protected function rules(): array
    {
        return [
            'numero_salida' => 'required|string|max:255|unique:inventario_salidas,numero_salida,' . $this->salidaId,
            'bodega_id' => 'required|exists:inventario_bodegas,id',
            'acta_entrega_id' => ($this->tipoActaPendiente === 'intermedia' ? 'nullable' : 'required') . '|exists:acta_entrega,id',
            'requisicion_id' => 'required|exists:requisicion,id',
            'tipo_salida' => 'required|in:entrega',
            'motivo' => 'nullable|string',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'empleado_recibe_id' => 'nullable|exists:empleados,id',
            'responsable_entrega_id' => 'nullable|exists:users,id',
            'fecha_salida' => 'required|date',
            'observacion' => 'nullable|string',
            'detalles' => [$this->esActaFinal() ? 'nullable' : 'required', 'array', $this->esActaFinal() ? 'min:0' : 'min:1'],
            'detalles.*.producto_id' => 'required|exists:inventario_productos,id',
            'detalles.*.lote_id' => 'required|exists:inventario_lotes,id',
            'detalles.*.detalle_acta_entrega_id' => $this->tipoActaPendiente === 'intermedia' ? 'required|integer' : 'nullable|exists:detalle_acta_entrega,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ];
    }

    public function openProductoModal(?int $detalleActaId = null): void
    {
        if (! $this->acta_entrega_id && $this->tipoActaPendiente !== 'intermedia') {
            $this->addError('acta_entrega_id', 'Seleccione primero el acta de entrega.');
            return;
        }

        $this->resetValidation('nuevoDetalle');
        $this->nuevoDetalle = [
            'detalle_acta_entrega_id' => $detalleActaId,
            'producto_id' => null,
            'lote_id' => null,
            'cantidad' => 1,
        ];
        $this->showProductoModal = true;
    }

    public function siguientePaso(): void
    {
        if (! $this->acta_entrega_id && $this->tipoActaPendiente !== 'intermedia') {
            $this->addError('acta_entrega_id', 'Seleccione primero el acta de entrega.');
            return;
        }

        $this->paso = min($this->paso + 1, 3);
    }

    public function pasoAnterior(): void
    {
        $this->paso = max($this->paso - 1, 1);
    }

    public function abrirConfirmacionGuardar(): void
    {
        $this->resetValidation();

        if (empty($this->detalles)) {
            if (! $this->esActaFinal()) {
                $this->mostrarAdvertencia('Sin productos', 'Debe agregar al menos un producto antes de generar la entrega.');
                return;
            }

            $this->mostrarAdvertencia('Entrega final sin productos', 'No hay productos pendientes para despachar. Puede finalizar esta requisición sin movimiento de inventario.');
        }

        $this->showGuardarModal = true;
    }

    public function cerrarConfirmacionGuardar(): void
    {
        $this->showGuardarModal = false;
    }

    public function abrirConfirmacionFinalizar(): void
    {
        $this->showFinalizarModal = true;
    }

    public function cerrarConfirmacionFinalizar(): void
    {
        $this->showFinalizarModal = false;
    }

    public function finalizarFlujo()
    {
        if ($this->esActaFinal()) {
            try {
                DB::transaction(fn () => $this->cerrarRequisicionPorEntregaFinal());
            } catch (\Throwable $e) {
                $this->showFinalizarModal = false;
                $this->mostrarAdvertencia('No se puede finalizar', $e->getMessage());
                return null;
            }
        }

        $this->showFinalizarModal = false;

        return redirect()->route('inventario.salidas');
    }

    public function mostrarAdvertencia(string $titulo, string $mensaje): void
    {
        $this->advertenciaTitulo = $titulo;
        $this->advertenciaMensaje = $mensaje;
        $this->showAdvertenciaModal = true;
    }

    public function cerrarAdvertencia(): void
    {
        $this->showAdvertenciaModal = false;
        $this->advertenciaTitulo = '';
        $this->advertenciaMensaje = '';
    }

    public function updatedNuevoDetalleDetalleActaEntregaId(): void
    {
        $this->nuevoDetalle['producto_id'] = null;
        $this->nuevoDetalle['lote_id'] = null;
    }

    public function updatedNuevoDetalleProductoId(): void
    {
        $this->nuevoDetalle['lote_id'] = null;
    }

    public function updatedNuevoDetalle($value, string $key): void
    {
        if ($key === 'detalle_acta_entrega_id') {
            $this->nuevoDetalle['producto_id'] = null;
            $this->nuevoDetalle['lote_id'] = null;
        }

        if ($key === 'producto_id') {
            $this->nuevoDetalle['lote_id'] = null;
        }
    }

    public function agregarProducto(): void
    {
        $this->validate([
            'nuevoDetalle.detalle_acta_entrega_id' => $this->tipoActaPendiente === 'intermedia' ? 'required|integer' : 'required|exists:detalle_acta_entrega,id',
            'nuevoDetalle.producto_id' => 'required|exists:inventario_productos,id',
            'nuevoDetalle.lote_id' => 'required|exists:inventario_lotes,id',
            'nuevoDetalle.cantidad' => 'required|numeric|min:0.01',
        ]);

        $detalleActaId = (int) $this->nuevoDetalle['detalle_acta_entrega_id'];
        $detalleActa = collect($this->detallesActaDisponibles)->firstWhere('id', $detalleActaId);
        $producto = collect($this->productosPorDetalleActa[$detalleActaId] ?? [])
            ->firstWhere('id', (int) $this->nuevoDetalle['producto_id']);

        if (! $detalleActa || ! $producto) {
            $this->addError('nuevoDetalle.producto_id', 'El producto no corresponde a un recurso de la requisición.');
            return;
        }

        $cantidadAgregada = collect($this->detalles)
            ->where('detalle_acta_entrega_id', $detalleActaId)
            ->sum(fn ($detalle) => (float) $detalle['cantidad']);

        if (($cantidadAgregada + (float) $this->nuevoDetalle['cantidad']) > (float) $detalleActa['cantidad_autorizada']) {
            $this->addError('nuevoDetalle.cantidad', 'La cantidad supera lo autorizado para este recurso.');
            return;
        }

        $this->detalles[] = [
            ...$this->nuevoDetalle,
            'detalle_acta_entrega_id' => $detalleActaId,
            'producto_id' => (int) $this->nuevoDetalle['producto_id'],
            'lote_id' => (int) $this->nuevoDetalle['lote_id'],
            'recurso' => $detalleActa['recurso'],
            'producto_nombre' => $producto['nombre'],
            'cantidad_autorizada' => $detalleActa['cantidad_autorizada'],
        ];

        $this->showProductoModal = false;
    }

    public function removeDetalle(int $index): void
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles);
    }

    public function updatedDetalles($value, string $key): void
    {
        if (str_ends_with($key, '.producto_id')) {
            $index = (int) str($key)->before('.')->toString();
            $this->detalles[$index]['lote_id'] = null;
        }
    }

    public function save(InventarioService $service)
    {
        try {
            $this->bodega_id ??= $this->defaultBodegaId();
            $this->asignarLotesDisponibles();

            if (! $this->prepararDetallesParaValidacion()) {
                return null;
            }

            $this->validate();

            if ($this->tipoActaPendiente !== 'intermedia') {
                $this->prepararContextoActa($this->acta_entrega_id, false);
            }

            $salida = DB::transaction(function () use ($service) {
                if ($this->tipoActaPendiente === 'intermedia') {
                    $acta = $this->crearActaIntermediaDesdeSalida();
                    $this->acta_entrega_id = $acta->id;
                }

                $salida = InventarioSalida::updateOrCreate(['id' => $this->salidaId], [
                    'numero_salida' => $this->numero_salida,
                    'bodega_id' => $this->bodega_id,
                    'acta_entrega_id' => $this->acta_entrega_id,
                    'requisicion_id' => $this->requisicion_id,
                    'tipo_salida' => $this->tipo_salida,
                    'motivo' => $this->motivo,
                    'departamento_id' => $this->departamento_id,
                    'empleado_recibe_id' => $this->empleado_recibe_id,
                    'responsable_entrega_id' => $this->responsable_entrega_id,
                    'usuario_id' => Auth::id(),
                    'fecha_salida' => $this->fecha_salida,
                    'observacion' => $this->observacion,
                    'estado' => 'borrador',
                ]);

                $salida->detalles()->delete();
                foreach ($this->detalles as $detalle) {
                    InventarioSalidaDetalle::create([
                        'salida_id' => $salida->id,
                        'detalle_acta_entrega_id' => $detalle['detalle_acta_entrega_id'] ?? null,
                        'producto_id' => $detalle['producto_id'],
                        'lote_id' => $detalle['lote_id'] ?: null,
                        'cantidad' => $detalle['cantidad'],
                    ]);
                }

                $salida = $salida->detalles()->exists()
                    ? $service->confirmarSalida($salida)
                    : tap($salida, function (InventarioSalida $salida) {
                        $salida->forceFill(['estado' => 'confirmado'])->save();
                    })->refresh();

                return $salida;
            });

            $this->salidaId = $salida->id;
            $this->showGuardarModal = false;

            session()->flash('message', 'Entrega generada y registrada en kardex.');
            return redirect()->route('inventario.salidas.acta', $salida);
        } catch (ValidationException $e) {
            $this->showGuardarModal = false;
            $this->mostrarAdvertencia('No se puede generar la entrega', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->showGuardarModal = false;
            $this->mostrarAdvertencia('No se puede generar la entrega', $e->getMessage());
        }
    }

    public function updatedActaEntregaId($actaId): void
    {
        if (! $actaId) {
            $this->productosPorDetalleActa = [];
            $this->detallesActaDisponibles = [];
            $this->requisicion_id = null;
            $this->departamento_id = null;
            $this->detalles = [];
            return;
        }
        $this->prepararContextoActa((int) $actaId);
    }

    private function esActaFinal(): bool
    {
        if (! $this->acta_entrega_id) {
            return false;
        }

        return ActaEntrega::with('tipoActaEntrega')
            ->whereKey($this->acta_entrega_id)
            ->get()
            ->contains(fn (ActaEntrega $acta) => mb_strtolower((string) $acta->tipoActaEntrega?->tipo) === 'final');
    }

    private function cerrarRequisicionPorEntregaFinal(): void
    {
        if (! $this->requisicion_id) {
            return;
        }

        $requisicion = Requisicion::with('estado')->lockForUpdate()->findOrFail($this->requisicion_id);

        if (($requisicion->estado?->estado ?? '') !== 'Finalizado') {
            $estadoFinalizado = EstadoRequisicion::where('estado', 'Finalizado')->firstOrFail();

            $requisicion->update([
                'idEstado' => $estadoFinalizado->id,
            ]);

            EstadoRequisicionLog::create([
                'observacion' => 'Requisición finalizada al generar entrega final de inventario',
                'log' => 'Cambio a Finalizado',
                'idRequisicion' => $requisicion->id,
                'created_by' => Auth::id(),
            ]);
        }

        $ejecucionPresupuestaria = EjecucionPresupuestaria::where('idRequisicion', $requisicion->id)->first();

        if ($ejecucionPresupuestaria && (int) $ejecucionPresupuestaria->idEstadoEjecucion !== 4) {
            $ejecucionPresupuestaria->update([
                'idEstadoEjecucion' => 4,
                'fechaFinEjecucion' => now(),
                'updated_by' => Auth::id(),
            ]);

            EjecucionPresupuestariaLog::create([
                'observacion' => 'Log generado por el sistema',
                'log' => 'Ejecución finalizada al generar entrega final de inventario',
                'idEjecucionPresupuestaria' => $ejecucionPresupuestaria->id,
                'created_by' => Auth::id(),
            ]);
        }
    }

    private function prepararContextoIntermediaPendiente(int $requisicionId): void
    {
        $requisicion = Requisicion::with('departamento')->findOrFail($requisicionId);

        $this->requisicion_id = $requisicion->id;
        $this->departamento_id = $requisicion->idDepartamento;
        $this->tipo_salida = 'entrega';
        $this->productosPorDetalleActa = [];
        $this->detallesActaDisponibles = [];

        $ejecuciones = DetalleEjecucionPresupuestaria::with(['detalleRequisicion.recurso'])
            ->whereHas('detalleRequisicion', fn ($query) => $query->where('idRequisicion', $requisicionId))
            ->orderBy('id')
            ->get();

        foreach ($ejecuciones as $ejecucion) {
            $detalleRequisicion = $ejecucion->detalleRequisicion;
            $recursoId = $detalleRequisicion?->idRecurso;
            $cantidadPendiente = $this->cantidadPendienteEjecucion($ejecucion);

            if (! $detalleRequisicion || ! $recursoId || $cantidadPendiente <= 0) {
                continue;
            }

            $productos = InventarioProducto::where('activo', true)
                ->whereHas('recursos', fn ($query) => $query->where('tareas_historicos.id', $recursoId))
                ->orderBy('nombre')
                ->get(['id', 'codigo_interno', 'nombre']);

            $this->productosPorDetalleActa[$ejecucion->id] = $productos->map(fn ($producto) => [
                'id' => $producto->id,
                'nombre' => $producto->codigo_interno . ' - ' . $producto->nombre,
                'text' => $producto->codigo_interno . ' - ' . $producto->nombre,
            ])->all();

            $this->detallesActaDisponibles[] = [
                'id' => $ejecucion->id,
                'text' => $detalleRequisicion->recurso?->nombre ?? 'Recurso no disponible',
                'recurso' => $detalleRequisicion->recurso?->nombre ?? 'Recurso no disponible',
                'cantidad_autorizada' => $cantidadPendiente,
                'detalle_requisicion_id' => $detalleRequisicion->id,
                'detalle_ejecucion_id' => $ejecucion->id,
            ];
        }

        $this->detalles = collect($this->detallesActaDisponibles)->map(function ($detalleDisponible) {
            $productos = $this->productosPorDetalleActa[$detalleDisponible['id']] ?? [];
            $productoId = count($productos) === 1 ? $productos[0]['id'] : null;
            $cantidad = (float) $detalleDisponible['cantidad_autorizada'];

            return [
                'detalle_acta_entrega_id' => $detalleDisponible['id'],
                'producto_id' => $productoId,
                'lote_id' => $productoId ? $this->loteDisponiblePara($productoId, $cantidad) : null,
                'cantidad' => $cantidad,
                'recurso' => $detalleDisponible['recurso'],
                'cantidad_autorizada' => $cantidad,
            ];
        })->values()->all();
    }

    private function crearActaIntermediaDesdeSalida(): ActaEntrega
    {
        if (! $this->requisicion_id) {
            throw ValidationException::withMessages(['requisicion_id' => 'No se encontró la requisición para generar el acta intermedia.']);
        }

        if (empty($this->detalles)) {
            throw ValidationException::withMessages(['detalles' => 'Debe agregar al menos un producto antes de generar la entrega intermedia.']);
        }

        $tipoId = TipoActaEntrega::whereRaw('LOWER(tipo) = ?', ['intermedia'])->value('id');

        if (! $tipoId) {
            throw ValidationException::withMessages(['acta' => 'No se encontró el tipo de acta intermedia.']);
        }

        $ejecuciones = DetalleEjecucionPresupuestaria::with('detalleRequisicion')
            ->whereIn('id', collect($this->detalles)->pluck('detalle_acta_entrega_id')->filter()->unique()->values())
            ->whereHas('detalleRequisicion', fn ($query) => $query->where('idRequisicion', $this->requisicion_id))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($ejecuciones->isEmpty()) {
            throw ValidationException::withMessages(['detalles' => 'No se encontraron ejecuciones válidas para generar el acta intermedia.']);
        }

        $acta = ActaEntrega::create([
            'correlativo' => $this->siguienteCorrelativoActa(),
            'fecha_extendida' => now(),
            'idTipoActaEntrega' => $tipoId,
            'idRequisicion' => $this->requisicion_id,
            'idEjecucionPresupuestaria' => $ejecuciones->first()->idEjecucion,
            'created_by' => Auth::id(),
        ]);

        foreach ($this->detalles as $index => $detalle) {
            $ejecucion = $ejecuciones->get((int) $detalle['detalle_acta_entrega_id']);

            if (! $ejecucion) {
                throw ValidationException::withMessages(['detalles' => 'Uno de los productos no corresponde a una ejecución válida.']);
            }

            $detalleActa = DetalleActaEntrega::create([
                'log_cant_ejecutada' => $detalle['cantidad'],
                'log_monto_unitario_ejecutado' => $ejecucion->monto_unitario_ejecutado,
                'log_fechaEjecucion' => $ejecucion->fechaEjecucion,
                'idActaEntrega' => $acta->id,
                'idRequisicion' => $this->requisicion_id,
                'idDetalleRequisicion' => $ejecucion->idDetalleRequisicion,
                'idEjecucionPresupuestaria' => $ejecucion->idEjecucion,
                'idDetalleEjecucionPresupuestaria' => $ejecucion->id,
                'created_by' => Auth::id(),
            ]);

            $this->detalles[$index]['detalle_acta_entrega_id'] = $detalleActa->id;
        }

        $this->tipoActaPendiente = null;

        return $acta;
    }

    private function cantidadPendienteEjecucion(DetalleEjecucionPresupuestaria $ejecucion): float
    {
        $yaDespachado = InventarioSalidaDetalle::query()
            ->join('inventario_salidas', 'inventario_salidas.id', '=', 'inventario_salida_detalles.salida_id')
            ->join('detalle_acta_entrega', 'detalle_acta_entrega.id', '=', 'inventario_salida_detalles.detalle_acta_entrega_id')
            ->where('inventario_salidas.estado', 'confirmado')
            ->whereNull('inventario_salidas.deleted_at')
            ->where('detalle_acta_entrega.idDetalleEjecucionPresupuestaria', $ejecucion->id)
            ->when($this->salidaId, fn ($query) => $query->where('inventario_salidas.id', '!=', $this->salidaId))
            ->sum('inventario_salida_detalles.cantidad');

        return max((float) $ejecucion->cant_ejecutada - (float) $yaDespachado, 0);
    }

    private function siguienteCorrelativoActa(): string
    {
        $numero = ((int) ActaEntrega::withTrashed()->max('id')) + 1;

        return 'ACT-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT) . '-' . now()->format('Y');
    }

    private function prepararContextoActa(int $actaId, bool $cargarDetalles = true): void
    {
        $acta = ActaEntrega::with(['tipoActaEntrega', 'requisicion.departamento', 'detalles.detalleRequisicion.recurso'])->findOrFail($actaId);
        $tipoActa = mb_strtolower((string) $acta->tipoActaEntrega?->tipo);

        if (! in_array($tipoActa, ['intermedia', 'final'], true)) {
            throw ValidationException::withMessages([
                'acta_entrega_id' => 'Solo se permiten actas intermedias o finales para salidas de inventario.',
            ]);
        }

        $this->requisicion_id = $acta->idRequisicion;
        $this->departamento_id = $acta->requisicion?->idDepartamento;
        $this->tipo_salida = 'entrega';
        $this->productosPorDetalleActa = [];
        $this->detallesActaDisponibles = [];

        foreach ($acta->detalles as $detalleActa) {
            $recursoId = $detalleActa->detalleRequisicion?->idRecurso;
            $productos = $recursoId
                ? InventarioProducto::where('activo', true)
                    ->whereHas('recursos', fn ($query) => $query->where('tareas_historicos.id', $recursoId))
                    ->orderBy('nombre')->get(['id', 'codigo_interno', 'nombre'])
                : collect();
            $this->productosPorDetalleActa[$detalleActa->id] = $productos->map(fn ($producto) => [
                'id' => $producto->id,
                'nombre' => $producto->codigo_interno . ' - ' . $producto->nombre,
                'text' => $producto->codigo_interno . ' - ' . $producto->nombre,
            ])->all();

            $cantidadPendiente = $this->cantidadPendienteActa($detalleActa, $tipoActa);

            if ($cantidadPendiente <= 0) {
                continue;
            }

            $this->detallesActaDisponibles[] = [
                'id' => $detalleActa->id,
                'text' => $detalleActa->detalleRequisicion?->recurso?->nombre ?? 'Recurso no disponible',
                'recurso' => $detalleActa->detalleRequisicion?->recurso?->nombre ?? 'Recurso no disponible',
                'cantidad_autorizada' => $cantidadPendiente,
            ];
        }

        if (! $cargarDetalles) {
            return;
        }

        $this->detalles = $acta->detalles->map(function ($detalleActa) use ($tipoActa) {
            $productos = $this->productosPorDetalleActa[$detalleActa->id] ?? [];
            $productoId = count($productos) === 1 ? $productos[0]['id'] : null;
            $cantidad = $this->cantidadPendienteActa($detalleActa, $tipoActa);

            if ($cantidad <= 0) {
                return null;
            }

            return [
                'detalle_acta_entrega_id' => $detalleActa->id,
                'producto_id' => $productoId,
                'lote_id' => $productoId ? $this->loteDisponiblePara($productoId, $cantidad) : null,
                'cantidad' => $cantidad,
                'recurso' => $detalleActa->detalleRequisicion?->recurso?->nombre ?? 'Recurso no disponible',
                'cantidad_autorizada' => $cantidad,
            ];
        })->filter()->values()->all();
    }

    private function cantidadPendienteActa(DetalleActaEntrega $detalleActa, string $tipoActa): float
    {
        $cantidadActa = (float) $detalleActa->log_cant_ejecutada;

        if ($tipoActa !== 'final' || ! $detalleActa->idDetalleRequisicion) {
            return $cantidadActa;
        }

        $yaDespachado = InventarioSalidaDetalle::query()
            ->join('inventario_salidas', 'inventario_salidas.id', '=', 'inventario_salida_detalles.salida_id')
            ->join('detalle_acta_entrega', 'detalle_acta_entrega.id', '=', 'inventario_salida_detalles.detalle_acta_entrega_id')
            ->where('inventario_salidas.estado', 'confirmado')
            ->whereNull('inventario_salidas.deleted_at')
            ->where('detalle_acta_entrega.idDetalleRequisicion', $detalleActa->idDetalleRequisicion)
            ->when($this->salidaId, fn ($query) => $query->where('inventario_salidas.id', '!=', $this->salidaId))
            ->sum('inventario_salida_detalles.cantidad');

        return max($cantidadActa - (float) $yaDespachado, 0);
    }

    private function asignarLotesDisponibles(): void
    {
        foreach ($this->detalles as $index => $detalle) {
            if (! empty($detalle['lote_id']) || empty($detalle['producto_id'])) {
                continue;
            }

            $this->detalles[$index]['lote_id'] = $this->loteDisponiblePara(
                (int) $detalle['producto_id'],
                (float) ($detalle['cantidad'] ?? 0),
            );
        }
    }

    private function prepararDetallesParaValidacion(): bool
    {
        $detallesIncompletos = collect($this->detalles)->filter(function ($detalle) {
            return empty($detalle['producto_id']) || empty($detalle['lote_id']);
        });

        if ($detallesIncompletos->isEmpty()) {
            return true;
        }

        if ($this->esActaFinal()) {
            $this->detalles = collect($this->detalles)
                ->filter(fn ($detalle) => ! empty($detalle['producto_id']) && ! empty($detalle['lote_id']))
                ->values()
                ->all();

            return true;
        }

        $recurso = $detallesIncompletos->first()['recurso'] ?? 'uno de los recursos';

        $this->showGuardarModal = false;
        $this->mostrarAdvertencia(
            'Sin existencia disponible',
            'No se encontró producto o lote disponible para ' . $recurso . '. Primero registre una entrada de inventario o seleccione un lote disponible.'
        );

        return false;
    }

    private function loteDisponiblePara(int $productoId, float $cantidad): ?int
    {
        if (! $this->bodega_id || $cantidad <= 0) {
            return null;
        }

        return InventarioExistencia::query()
            ->where('bodega_id', $this->bodega_id)
            ->where('producto_id', $productoId)
            ->where('cantidad_disponible', '>=', $cantidad)
            ->orderBy('lote_id')
            ->value('lote_id');
    }

    private function defaultBodegaId(): ?int
    {
        return InventarioBodega::where('activo', true)->orderBy('id')->value('id');
    }

    public function render()
    {
        $actaSeleccionada = $this->acta_entrega_id
            ? ActaEntrega::with([
                'tipoActaEntrega',
                'requisicion:id,correlativo,descripcion,observacion,idDepartamento,idEstado,fechaSolicitud,fechaRequerido',
                'requisicion.departamento:id,name,siglas',
                'requisicion.estado:id,estado',
            ])->find($this->acta_entrega_id)
            : null;
        $requisicionPendiente = $this->tipoActaPendiente === 'intermedia' && $this->requisicion_id
            ? Requisicion::with(['departamento:id,name,siglas', 'estado:id,estado'])
                ->find($this->requisicion_id, [
                    'id',
                    'correlativo',
                    'descripcion',
                    'observacion',
                    'idDepartamento',
                    'idEstado',
                    'fechaSolicitud',
                    'fechaRequerido',
                ])
            : null;
        $requisicionVista = $actaSeleccionada?->requisicion ?? $requisicionPendiente;
        $tipoActaSeleccionada = mb_strtolower((string) $actaSeleccionada?->tipoActaEntrega?->tipo);
        $actaRouteBase = $tipoActaSeleccionada === 'final' ? 'acta-entrega-pdf' : 'acta-entrega-intermedia-pdf';
        $actaPdfUrl = $actaSeleccionada ? route($actaRouteBase, $actaSeleccionada->idRequisicion) : null;
        $actaDownloadUrl = $actaSeleccionada ? route($actaRouteBase . '-download', $actaSeleccionada->idRequisicion) : null;
        $empleados = $this->empleadosDisponibles();

        if ($actaSeleccionada && $tipoActaSeleccionada === 'intermedia') {
            $actaPdfUrl .= '?acta_id=' . $actaSeleccionada->id;
            $actaDownloadUrl .= '?acta_id=' . $actaSeleccionada->id;
        }

        return view('livewire.inventario.salida-form', [
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'productos' => InventarioProducto::where('activo', true)->orderBy('nombre')->get(),
            'existencias' => InventarioExistencia::with('lote')->where('cantidad_disponible', '>', 0)->get(),
            'actas' => ActaEntrega::with('requisicion:id,correlativo')
                ->whereHas('tipoActaEntrega', fn ($query) => $query->whereRaw('LOWER(tipo) in (?, ?)', ['intermedia', 'final']))
                ->latest()->limit(100)->get(['id', 'correlativo', 'idRequisicion']),
            'actaSeleccionada' => $actaSeleccionada,
            'requisicionPendiente' => $requisicionPendiente,
            'requisicionVista' => $requisicionVista,
            'actaPendientePreview' => $this->tipoActaPendiente === 'intermedia' ? $this->siguienteCorrelativoActa() : null,
            'actaPdfUrl' => $actaPdfUrl,
            'actaDownloadUrl' => $actaDownloadUrl,
            'actaTitulo' => $tipoActaSeleccionada === 'final' ? 'Acta de entrega final' : 'Acta de entrega intermedia',
            'departamentos' => Departamento::orderBy('name')->get(['id', 'name']),
            'empleados' => $empleados['items'],
            'empleadosFiltradosPorDepartamento' => $empleados['filtrados'],
        ]);
    }

    private function empleadosDisponibles(): array
    {
        $columnas = ['id', 'nombre', 'apellido', 'num_empleado', 'dni'];
        $empleados = collect();
        $filtrados = false;

        if ($this->departamento_id) {
            $empleados = Empleado::whereHas('departamentos', function ($query) {
                $query->where('departamentos.id', $this->departamento_id);
            })
                ->orderBy('nombre')
                ->get($columnas);

            $filtrados = $empleados->isNotEmpty();
        }

        if ($empleados->isEmpty()) {
            $empleados = Empleado::orderBy('nombre')->get($columnas);
        }

        if ($this->empleado_recibe_id && ! $empleados->contains('id', $this->empleado_recibe_id)) {
            $empleadoSeleccionado = Empleado::find($this->empleado_recibe_id, $columnas);

            if ($empleadoSeleccionado) {
                $empleados->prepend($empleadoSeleccionado);
            }
        }

        return [
            'items' => $empleados->map(fn (Empleado $empleado) => [
                'id' => $empleado->id,
                'text' => trim($empleado->nombre . ' ' . $empleado->apellido)
                    . ($empleado->num_empleado ? ' - ' . $empleado->num_empleado : ''),
            ])->values()->all(),
            'filtrados' => $filtrados,
        ];
    }
}
