<?php

namespace App\Livewire\Inventario;

use App\Models\Actas\ActaEntrega;
use App\Models\Actas\DetalleActaEntrega;
use App\Models\Departamento\Departamento;
use App\Models\Empleados\Empleado;
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
use App\Services\ActaIntermediaService;
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
    public bool $showAdvertenciaModal = false;
    public string $advertenciaTitulo = '';
    public string $advertenciaMensaje = '';
    public array $nuevoDetalle = [
        'detalle_acta_entrega_id' => null,
        'producto_id' => null,
        'lote_id' => null,
        'cantidad' => 1,
    ];

    public function mount(?InventarioSalida $salida = null, ?ActaEntrega $acta = null): void
    {
        app(ActaIntermediaService::class)->crearPendientes(Auth::id());

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
        }
    }

    protected function rules(): array
    {
        return [
            'numero_salida' => 'required|string|max:255|unique:inventario_salidas,numero_salida,' . $this->salidaId,
            'bodega_id' => 'required|exists:inventario_bodegas,id',
            'acta_entrega_id' => 'required|exists:acta_entrega,id',
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
            'detalles.*.detalle_acta_entrega_id' => 'nullable|exists:detalle_acta_entrega,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ];
    }

    public function openProductoModal(?int $detalleActaId = null): void
    {
        if (! $this->acta_entrega_id) {
            $this->addError('acta_entrega_id', 'Seleccione primero un acta de entrega.');
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
        if (! $this->acta_entrega_id) {
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
            'nuevoDetalle.detalle_acta_entrega_id' => 'required|exists:detalle_acta_entrega,id',
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

            $this->prepararContextoActa($this->acta_entrega_id, false);

            $salida = DB::transaction(function () use ($service) {
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

                if ($this->esActaFinal()) {
                    $this->cerrarRequisicionPorEntregaFinal();
                }

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
            ? ActaEntrega::with(['tipoActaEntrega', 'requisicion:id,correlativo'])->find($this->acta_entrega_id)
            : null;
        $tipoActaSeleccionada = mb_strtolower((string) $actaSeleccionada?->tipoActaEntrega?->tipo);
        $actaRouteBase = $tipoActaSeleccionada === 'final' ? 'acta-entrega-pdf' : 'acta-entrega-intermedia-pdf';

        return view('livewire.inventario.salida-form', [
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'productos' => InventarioProducto::where('activo', true)->orderBy('nombre')->get(),
            'existencias' => InventarioExistencia::with('lote')->where('cantidad_disponible', '>', 0)->get(),
            'actas' => ActaEntrega::with('requisicion:id,correlativo')
                ->whereHas('tipoActaEntrega', fn ($query) => $query->whereRaw('LOWER(tipo) in (?, ?)', ['intermedia', 'final']))
                ->latest()->limit(100)->get(['id', 'correlativo', 'idRequisicion']),
            'actaSeleccionada' => $actaSeleccionada,
            'actaPdfUrl' => $actaSeleccionada ? route($actaRouteBase, $actaSeleccionada->idRequisicion) : null,
            'actaDownloadUrl' => $actaSeleccionada ? route($actaRouteBase . '-download', $actaSeleccionada->idRequisicion) : null,
            'actaTitulo' => $tipoActaSeleccionada === 'final' ? 'Acta de entrega final' : 'Acta de entrega intermedia',
            'departamentos' => Departamento::orderBy('name')->get(['id', 'name']),
            'empleados' => Empleado::orderBy('nombre')->get(['id', 'nombre', 'apellido']),
        ]);
    }
}
