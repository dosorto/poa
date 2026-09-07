<?php

namespace App\Livewire\Inventario;

use App\Models\Actas\ActaEntrega;
use App\Models\Departamento\Departamento;
use App\Models\Empleados\Empleado;
use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioExistencia;
use App\Models\Inventario\InventarioProducto;
use App\Models\Inventario\InventarioSalida;
use App\Models\Inventario\InventarioSalidaDetalle;
use App\Services\ActaIntermediaService;
use Illuminate\Support\Facades\Auth;
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
    public bool $showProductoModal = false;
    public array $nuevoDetalle = [
        'detalle_acta_entrega_id' => null,
        'producto_id' => null,
        'lote_id' => null,
        'cantidad' => 1,
    ];

    public function mount(?InventarioSalida $salida = null): void
    {
        app(ActaIntermediaService::class)->crearPendientes(Auth::id());

        if ($salida?->exists) {
            abort_unless($salida->estado === 'borrador', 404);
            $salida->load('detalles');
            $this->salidaId = $salida->id;
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
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:inventario_productos,id',
            'detalles.*.lote_id' => 'required|exists:inventario_lotes,id',
            'detalles.*.detalle_acta_entrega_id' => 'nullable|exists:detalle_acta_entrega,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ];
    }

    public function openProductoModal(?int $detalleActaId = null): void
    {
        if (! $this->acta_entrega_id) {
            $this->addError('acta_entrega_id', 'Seleccione primero un acta intermedia.');
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

    public function save()
    {
        $this->bodega_id ??= $this->defaultBodegaId();
        $this->validate();

        $this->prepararContextoActa($this->acta_entrega_id, false);

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

        session()->flash('message', 'Salida guardada en borrador.');
        return redirect()->route('inventario.salidas');
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

    private function prepararContextoActa(int $actaId, bool $cargarDetalles = true): void
    {
        $acta = ActaEntrega::with(['tipoActaEntrega', 'requisicion.departamento', 'detalles.detalleRequisicion.recurso'])->findOrFail($actaId);
        if (mb_strtolower((string) $acta->tipoActaEntrega?->tipo) !== 'intermedia') {
            throw ValidationException::withMessages([
                'acta_entrega_id' => 'Solo se permiten actas intermedias para salidas de inventario.',
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

            $this->detallesActaDisponibles[] = [
                'id' => $detalleActa->id,
                'text' => $detalleActa->detalleRequisicion?->recurso?->nombre ?? 'Recurso no disponible',
                'recurso' => $detalleActa->detalleRequisicion?->recurso?->nombre ?? 'Recurso no disponible',
                'cantidad_autorizada' => (float) $detalleActa->log_cant_ejecutada,
            ];
        }

        if (! $cargarDetalles) {
            return;
        }

        $this->detalles = $acta->detalles->map(function ($detalleActa) {
            $productos = $this->productosPorDetalleActa[$detalleActa->id] ?? [];

            return [
                'detalle_acta_entrega_id' => $detalleActa->id,
                'producto_id' => count($productos) === 1 ? $productos[0]['id'] : null,
                'lote_id' => null,
                'cantidad' => (float) $detalleActa->log_cant_ejecutada,
                'recurso' => $detalleActa->detalleRequisicion?->recurso?->nombre ?? 'Recurso no disponible',
                'cantidad_autorizada' => (float) $detalleActa->log_cant_ejecutada,
            ];
        })->values()->all();
    }

    private function defaultBodegaId(): ?int
    {
        return InventarioBodega::where('activo', true)->orderBy('id')->value('id');
    }

    public function render()
    {
        return view('livewire.inventario.salida-form', [
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'productos' => InventarioProducto::where('activo', true)->orderBy('nombre')->get(),
            'existencias' => InventarioExistencia::with('lote')->where('cantidad_disponible', '>', 0)->get(),
            'actas' => ActaEntrega::with('requisicion:id,correlativo')
                ->whereHas('tipoActaEntrega', fn ($query) => $query->whereRaw('LOWER(tipo) = ?', ['intermedia']))
                ->latest()->limit(100)->get(['id', 'correlativo', 'idRequisicion']),
            'departamentos' => Departamento::orderBy('name')->get(['id', 'name']),
            'empleados' => Empleado::orderBy('nombre')->get(['id', 'nombre', 'apellido']),
        ]);
    }
}
