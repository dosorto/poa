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
use App\Models\Requisicion\Requisicion;
use App\Services\Inventario\InventarioService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Salidas extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $salidaId = null;
    public string $numero_salida = '';
    public ?int $bodega_id = null;
    public ?int $acta_entrega_id = null;
    public ?int $requisicion_id = null;
    public string $tipo_salida = 'manual';
    public ?string $motivo = null;
    public ?int $departamento_id = null;
    public ?int $empleado_recibe_id = null;
    public ?int $responsable_entrega_id = null;
    public string $fecha_salida = '';
    public ?string $observacion = null;
    public array $detalles = [];
    public array $productosPorDetalleActa = [];

    protected function rules(): array
    {
        return [
            'numero_salida' => 'required|string|max:255|unique:inventario_salidas,numero_salida,' . $this->salidaId,
            'bodega_id' => 'required|exists:inventario_bodegas,id',
            'acta_entrega_id' => 'nullable|exists:acta_entrega,id',
            'requisicion_id' => 'nullable|exists:requisicion,id',
            'tipo_salida' => 'required|string',
            'motivo' => 'required_if:tipo_salida,manual|nullable|string',
            'departamento_id' => 'nullable|exists:departamentos,id',
            'empleado_recibe_id' => 'nullable|exists:empleados,id',
            'responsable_entrega_id' => 'required_if:tipo_salida,manual|nullable|exists:users,id',
            'fecha_salida' => 'required|date',
            'observacion' => 'required_if:tipo_salida,manual|nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:inventario_productos,id',
            'detalles.*.lote_id' => 'nullable|exists:inventario_lotes,id',
            'detalles.*.detalle_acta_entrega_id' => 'nullable|exists:detalle_acta_entrega,id',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->numero_salida = 'SAL-' . now()->format('YmdHis');
        $this->bodega_id = $this->defaultBodegaId();
        $this->fecha_salida = now()->toDateString();
        $this->responsable_entrega_id = Auth::id();
        $this->detalles = [$this->emptyDetalle()];
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $salida = InventarioSalida::with('detalles')->findOrFail($id);

        if ($salida->estado !== 'borrador') {
            session()->flash('error', 'Solo se pueden editar salidas en borrador.');
            return;
        }

        $this->salidaId = $salida->id;
        $this->fill($salida->only(['numero_salida', 'bodega_id', 'acta_entrega_id', 'requisicion_id', 'tipo_salida', 'motivo', 'departamento_id', 'empleado_recibe_id', 'responsable_entrega_id', 'fecha_salida', 'observacion']));
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

        $this->showModal = true;
    }

    public function addDetalle(): void
    {
        if ($this->acta_entrega_id) {
            session()->flash('error', 'Agregue otro lote desde una línea del acta; no se permiten productos ajenos al acta.');
            return;
        }

        $this->detalles[] = $this->emptyDetalle();
    }

    public function agregarLoteActa(int $index): void
    {
        $detalle = $this->detalles[$index] ?? null;

        if (! $this->acta_entrega_id || ! $detalle || empty($detalle['detalle_acta_entrega_id'])) {
            return;
        }

        $nuevoDetalle = $detalle;
        unset($nuevoDetalle['id']);
        $nuevoDetalle['lote_id'] = null;
        $nuevoDetalle['cantidad'] = 0;
        $this->detalles[] = $nuevoDetalle;
    }

    public function removeDetalle(int $index): void
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles);
    }

    public function save(): void
    {
        $this->bodega_id ??= $this->defaultBodegaId();

        $this->validate();

        if ($this->acta_entrega_id) {
            $this->prepararContextoActa($this->acta_entrega_id, false);
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

        session()->flash('message', 'Salida guardada en borrador.');
        $this->closeModal();
    }

    public function confirmar(int $id, InventarioService $service): void
    {
        $service->confirmarSalida(InventarioSalida::findOrFail($id));
        session()->flash('message', 'Salida confirmada y registrada en kardex.');
    }

    public function anular(int $id, InventarioService $service): void
    {
        $service->anularSalida(InventarioSalida::findOrFail($id));
        session()->flash('message', 'Salida anulada con movimiento reverso.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['salidaId', 'numero_salida', 'bodega_id', 'acta_entrega_id', 'requisicion_id', 'tipo_salida', 'motivo', 'departamento_id', 'empleado_recibe_id', 'responsable_entrega_id', 'fecha_salida', 'observacion', 'detalles', 'productosPorDetalleActa']);
        $this->tipo_salida = 'manual';
        $this->resetValidation();
    }

    private function emptyDetalle(): array
    {
        return ['detalle_acta_entrega_id' => null, 'producto_id' => null, 'lote_id' => null, 'cantidad' => 1];
    }

    private function defaultBodegaId(): ?int
    {
        return InventarioBodega::where('activo', true)->orderBy('id')->value('id');
    }

    public function updatedActaEntregaId($actaId): void
    {
        if (! $actaId) {
            $this->productosPorDetalleActa = [];
            return;
        }

        $this->prepararContextoActa((int) $actaId);
    }

    private function prepararContextoActa(int $actaId, bool $cargarDetalles = true): void
    {
        $acta = ActaEntrega::with([
            'tipoActaEntrega',
            'requisicion.departamento',
            'detalles.detalleRequisicion.recurso',
        ])->findOrFail($actaId);

        if (mb_strtolower((string) $acta->tipoActaEntrega?->tipo) !== 'final') {
            $this->addError('acta_entrega_id', 'Solo se permiten actas finales para salidas de bodega.');
            return;
        }

        $this->requisicion_id = $acta->idRequisicion;
        $this->departamento_id = $acta->requisicion?->idDepartamento;
        $this->tipo_salida = 'entrega';
        $this->productosPorDetalleActa = [];

        foreach ($acta->detalles as $detalleActa) {
            $recursoId = $detalleActa->detalleRequisicion?->idRecurso;
            $productos = $recursoId
                ? InventarioProducto::where('activo', true)
                    ->whereHas('recursos', fn ($query) => $query->where('tareas_historicos.id', $recursoId))
                    ->orderBy('nombre')
                    ->get(['id', 'codigo_interno', 'nombre'])
                : collect();

            $this->productosPorDetalleActa[$detalleActa->id] = $productos->map(fn (InventarioProducto $producto) => [
                'id' => $producto->id,
                'nombre' => $producto->codigo_interno . ' - ' . $producto->nombre,
            ])->all();
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
                'cantidad' => $detalleActa->log_cant_ejecutada,
                'cantidad_autorizada' => $detalleActa->log_cant_ejecutada,
                'recurso' => $detalleActa->detalleRequisicion?->recurso?->nombre ?? 'Recurso no disponible',
            ];
        })->values()->all();
    }

    public function render()
    {
        $search = '%' . $this->search . '%';

        return view('livewire.inventario.salidas', [
            'salidas' => InventarioSalida::with(['bodega', 'actaEntrega', 'detalles.producto'])
                ->where('numero_salida', 'like', $search)
                ->orWhere('tipo_salida', 'like', $search)
                ->latest()
                ->paginate(10),
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'productos' => InventarioProducto::where('activo', true)->orderBy('nombre')->get(),
            'existencias' => InventarioExistencia::with('lote')->where('cantidad_disponible', '>', 0)->get(),
            'actas' => ActaEntrega::whereHas('tipoActaEntrega', fn ($query) => $query->whereRaw('LOWER(tipo) = ?', ['final']))
                ->latest()
                ->limit(100)
                ->get(['id', 'correlativo']),
            'requisiciones' => Requisicion::latest()->limit(100)->get(['id', 'correlativo']),
            'departamentos' => Departamento::orderBy('name')->get(['id', 'name']),
            'empleados' => Empleado::orderBy('nombre')->get(['id', 'nombre', 'apellido']),
        ]);
    }
}
