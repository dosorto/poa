<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioEntrada;
use App\Models\Inventario\InventarioEntradaDetalle;
use App\Models\Inventario\InventarioProducto;
use App\Models\Requisicion\Requisicion;
use App\Services\Inventario\InventarioService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Entradas extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $entradaId = null;
    public string $numero_entrada = '';
    public ?string $numero_factura = null;
    public ?string $proveedor = null;
    public ?string $fecha_factura = null;
    public ?string $orden_compra_referencia = null;
    public ?int $requisicion_id = null;
    public ?int $bodega_id = null;
    public string $fecha_entrada = '';
    public ?string $observacion = null;
    public array $detalles = [];

    protected function rules(): array
    {
        return [
            'numero_entrada' => 'required|string|max:255|unique:inventario_entradas,numero_entrada,' . $this->entradaId,
            'numero_factura' => 'required|string|max:255',
            'proveedor' => 'required|string|max:255',
            'fecha_factura' => 'required|date',
            'orden_compra_referencia' => 'nullable|string|max:255',
            'requisicion_id' => 'nullable|exists:requisicion,id',
            'bodega_id' => 'required|exists:inventario_bodegas,id',
            'fecha_entrada' => 'required|date',
            'observacion' => 'nullable|string',
            'detalles' => 'required|array|min:1',
            'detalles.*.producto_id' => 'required|exists:inventario_productos,id',
            'detalles.*.codigo_lote' => 'nullable|string|max:255',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.costo_unitario' => 'nullable|numeric|min:0',
            'detalles.*.fecha_vencimiento' => 'nullable|date',
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->numero_entrada = 'ENT-' . now()->format('YmdHis');
        $this->fecha_entrada = now()->toDateString();
        $this->bodega_id = $this->defaultBodegaId();
        $this->detalles = [$this->emptyDetalle()];
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $entrada = InventarioEntrada::with('detalles')->findOrFail($id);

        if ($entrada->estado !== 'borrador') {
            session()->flash('error', 'Solo se pueden editar entradas en borrador.');
            return;
        }

        $this->entradaId = $entrada->id;
        $this->fill($entrada->only(['numero_entrada', 'numero_factura', 'proveedor', 'fecha_factura', 'orden_compra_referencia', 'requisicion_id', 'bodega_id', 'fecha_entrada', 'observacion']));
        $this->fecha_factura = $entrada->fecha_factura?->format('Y-m-d');
        $this->fecha_entrada = $entrada->fecha_entrada?->format('Y-m-d') ?? now()->toDateString();
        $this->detalles = $entrada->detalles->map(fn ($detalle) => [
            'id' => $detalle->id,
            'producto_id' => $detalle->producto_id,
            'codigo_lote' => $detalle->codigo_lote,
            'cantidad' => $detalle->cantidad,
            'costo_unitario' => $detalle->costo_unitario,
            'fecha_vencimiento' => $detalle->fecha_vencimiento?->format('Y-m-d'),
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
        $this->bodega_id ??= $this->defaultBodegaId();

        $this->validate();

        $entrada = InventarioEntrada::updateOrCreate(['id' => $this->entradaId], [
            'numero_entrada' => $this->numero_entrada,
            'numero_factura' => $this->numero_factura,
            'proveedor' => $this->proveedor,
            'fecha_factura' => $this->fecha_factura,
            'orden_compra_referencia' => $this->orden_compra_referencia,
            'requisicion_id' => $this->requisicion_id,
            'bodega_id' => $this->bodega_id,
            'fecha_entrada' => $this->fecha_entrada,
            'usuario_id' => Auth::id(),
            'observacion' => $this->observacion,
            'estado' => 'borrador',
        ]);

        $entrada->detalles()->delete();

        foreach ($this->detalles as $detalle) {
            InventarioEntradaDetalle::create([
                'entrada_id' => $entrada->id,
                'producto_id' => $detalle['producto_id'],
                'codigo_lote' => $detalle['codigo_lote'] ?: null,
                'cantidad' => $detalle['cantidad'],
                'costo_unitario' => $detalle['costo_unitario'] ?: null,
                'total' => $detalle['costo_unitario'] ? ((float) $detalle['cantidad'] * (float) $detalle['costo_unitario']) : null,
                'fecha_vencimiento' => $detalle['fecha_vencimiento'] ?: null,
            ]);
        }

        session()->flash('message', 'Entrada guardada en borrador.');
        $this->closeModal();
    }

    public function confirmar(int $id, InventarioService $service): void
    {
        $service->confirmarEntrada(InventarioEntrada::findOrFail($id));
        session()->flash('message', 'Entrada confirmada y registrada en kardex.');
    }

    public function anular(int $id, InventarioService $service): void
    {
        $service->anularEntrada(InventarioEntrada::findOrFail($id));
        session()->flash('message', 'Entrada anulada con movimiento reverso.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['entradaId', 'numero_entrada', 'numero_factura', 'proveedor', 'fecha_factura', 'orden_compra_referencia', 'requisicion_id', 'bodega_id', 'fecha_entrada', 'observacion', 'detalles']);
        $this->resetValidation();
    }

    private function emptyDetalle(): array
    {
        return ['producto_id' => null, 'codigo_lote' => null, 'cantidad' => 1, 'costo_unitario' => null, 'fecha_vencimiento' => null];
    }

    private function defaultBodegaId(): ?int
    {
        return InventarioBodega::where('activo', true)->orderBy('id')->value('id');
    }

    public function render()
    {
        $search = '%' . $this->search . '%';

        return view('livewire.inventario.entradas', [
            'entradas' => InventarioEntrada::with(['bodega', 'requisicion', 'detalles.producto'])
                ->where('numero_entrada', 'like', $search)
                ->orWhere('numero_factura', 'like', $search)
                ->orWhere('proveedor', 'like', $search)
                ->latest()
                ->paginate(10),
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'productos' => InventarioProducto::where('activo', true)->orderBy('nombre')->get(),
            'requisiciones' => Requisicion::latest()->limit(100)->get(['id', 'correlativo']),
        ]);
    }
}
