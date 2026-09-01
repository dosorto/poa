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
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->numero_salida = 'SAL-' . now()->format('YmdHis');
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
            'producto_id' => $detalle->producto_id,
            'lote_id' => $detalle->lote_id,
            'cantidad' => $detalle->cantidad,
        ])->toArray();
        $this->showModal = true;
    }

    public function addDetalle(): void
    {
        $this->detalles[] = $this->emptyDetalle();
    }

    public function removeDetalle(int $index): void
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles);
    }

    public function save(): void
    {
        $this->validate();

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
        $this->reset(['salidaId', 'numero_salida', 'bodega_id', 'acta_entrega_id', 'requisicion_id', 'tipo_salida', 'motivo', 'departamento_id', 'empleado_recibe_id', 'responsable_entrega_id', 'fecha_salida', 'observacion', 'detalles']);
        $this->tipo_salida = 'manual';
        $this->resetValidation();
    }

    private function emptyDetalle(): array
    {
        return ['producto_id' => null, 'lote_id' => null, 'cantidad' => 1];
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
            'actas' => ActaEntrega::latest()->limit(100)->get(['id', 'correlativo']),
            'requisiciones' => Requisicion::latest()->limit(100)->get(['id', 'correlativo']),
            'departamentos' => Departamento::orderBy('nombre')->get(['id', 'nombre']),
            'empleados' => Empleado::orderBy('nombre')->get(['id', 'nombre', 'apellido']),
        ]);
    }
}
