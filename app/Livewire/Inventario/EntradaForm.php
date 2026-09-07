<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioEntrada;
use App\Models\Inventario\InventarioEntradaDetalle;
use App\Models\Inventario\InventarioProducto;
use App\Models\Requisicion\Requisicion;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EntradaForm extends Component
{
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
    public bool $showProductoModal = false;
    public array $nuevoDetalle = [];

    public function mount(?InventarioEntrada $entrada = null): void
    {
        if ($entrada?->exists) {
            abort_unless($entrada->estado === 'borrador', 404);
            $entrada->load('detalles');
            $this->entradaId = $entrada->id;
            $this->fill($entrada->only(['numero_entrada', 'numero_factura', 'proveedor', 'orden_compra_referencia', 'requisicion_id', 'bodega_id', 'observacion']));
            $this->fecha_factura = $entrada->fecha_factura?->format('Y-m-d');
            $this->fecha_entrada = $entrada->fecha_entrada?->format('Y-m-d') ?? now()->toDateString();
            $this->detalles = $entrada->detalles->map(fn ($detalle) => [
                'producto_id' => $detalle->producto_id,
                'codigo_lote' => $detalle->codigo_lote,
                'cantidad' => $detalle->cantidad,
                'costo_unitario' => $detalle->costo_unitario,
                'fecha_vencimiento' => $detalle->fecha_vencimiento?->format('Y-m-d'),
            ])->toArray();
            return;
        }

        $this->numero_entrada = 'ENT-' . now()->format('YmdHis');
        $this->fecha_entrada = now()->toDateString();
        $this->bodega_id = InventarioBodega::where('activo', true)->orderBy('id')->value('id');
    }

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

    public function openProductoModal(): void
    {
        $this->nuevoDetalle = ['producto_id' => null, 'codigo_lote' => null, 'cantidad' => 1, 'costo_unitario' => null, 'fecha_vencimiento' => null];
        $this->resetValidation('nuevoDetalle');
        $this->showProductoModal = true;
    }

    public function agregarProducto(): void
    {
        $this->validate([
            'nuevoDetalle.producto_id' => 'required|exists:inventario_productos,id',
            'nuevoDetalle.codigo_lote' => 'nullable|string|max:255',
            'nuevoDetalle.cantidad' => 'required|numeric|min:0.01',
            'nuevoDetalle.costo_unitario' => 'nullable|numeric|min:0',
            'nuevoDetalle.fecha_vencimiento' => 'nullable|date',
        ]);

        $this->detalles[] = $this->nuevoDetalle;
        $this->showProductoModal = false;
    }

    public function removeDetalle(int $index): void
    {
        unset($this->detalles[$index]);
        $this->detalles = array_values($this->detalles);
    }

    public function save()
    {
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
                'total' => $detalle['costo_unitario'] ? (float) $detalle['cantidad'] * (float) $detalle['costo_unitario'] : null,
                'fecha_vencimiento' => $detalle['fecha_vencimiento'] ?: null,
            ]);
        }

        session()->flash('message', 'Entrada guardada en borrador.');
        return redirect()->route('inventario.entradas');
    }

    public function render()
    {
        $productos = InventarioProducto::where('activo', true)->orderBy('nombre')->get();

        return view('livewire.inventario.entrada-form', [
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'requisiciones' => Requisicion::latest()->limit(100)->get(['id', 'correlativo']),
            'productosOptions' => $productos->map(fn ($producto) => ['id' => $producto->id, 'text' => $producto->codigo_interno . ' - ' . $producto->nombre])->all(),
            'productosPorId' => $productos->keyBy('id'),
        ]);
    }
}
