<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioEntrada;
use App\Models\Inventario\InventarioEntradaDetalle;
use App\Models\Inventario\InventarioProducto;
use App\Models\Requisicion\Requisicion;
use App\Services\Inventario\InventarioService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class EntradaForm extends Component
{
    #[Locked]
    public ?int $entradaId = null;

    #[Locked]
    public bool $soloLectura = false;
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
    public int $paso = 1;
    public bool $showProductoModal = false;
    public bool $showFinalizarModal = false;
    public bool $showAdvertenciaModal = false;
    public string $advertenciaTitulo = '';
    public string $advertenciaMensaje = '';
    public array $nuevoDetalle = [];

    public function mount(?InventarioEntrada $entrada = null): void
    {
        if ($entrada?->exists) {
            abort_unless(in_array($entrada->estado, ['borrador', 'confirmado'], true), 404);
            $entrada->load('detalles');
            $this->entradaId = $entrada->id;
            $this->soloLectura = $entrada->estado === 'confirmado';
            $this->paso = $entrada->estado === 'confirmado' ? 3 : 1;
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
            'bodega_id' => ['required', Rule::exists('inventario_bodegas', 'id')->where('activo', true)->whereNull('deleted_at')],
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

    protected function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'exists' => 'Seleccione un valor válido para :attribute.',
            'unique' => 'El número de entrada ya está registrado.',
            'date' => 'Ingrese una fecha válida para :attribute.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'string' => 'El campo :attribute debe ser texto.',
            'array' => 'Agregue los productos de la entrada.',
            'min' => 'El campo :attribute debe ser mayor o igual a :min.',
            'detalles.min' => 'Agregue al menos un producto a la entrada.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'bodega_id.required' => 'Seleccione una bodega destino.',
            'bodega_id.exists' => 'Seleccione una bodega destino activa.',
            'detalles.required' => 'Agregue al menos un producto a la entrada.',
            'nuevoDetalle.producto_id.required' => 'Seleccione un producto.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'numero_entrada' => 'número de entrada',
            'numero_factura' => 'factura',
            'proveedor' => 'proveedor',
            'fecha_factura' => 'fecha de factura',
            'orden_compra_referencia' => 'orden de compra',
            'requisicion_id' => 'requisición',
            'bodega_id' => 'bodega destino',
            'fecha_entrada' => 'fecha de entrada',
            'observacion' => 'observación',
            'detalles' => 'productos',
            'detalles.*.producto_id' => 'producto',
            'detalles.*.codigo_lote' => 'lote',
            'detalles.*.cantidad' => 'cantidad',
            'detalles.*.costo_unitario' => 'costo unitario',
            'detalles.*.fecha_vencimiento' => 'vencimiento',
            'nuevoDetalle.producto_id' => 'producto',
            'nuevoDetalle.codigo_lote' => 'lote',
            'nuevoDetalle.cantidad' => 'cantidad',
            'nuevoDetalle.costo_unitario' => 'costo unitario',
            'nuevoDetalle.fecha_vencimiento' => 'vencimiento',
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

    public function siguientePaso(): void
    {
        if ($this->paso === 1) {
            try {
                $this->validate(array_filter(
                    $this->rules(),
                    fn ($campo) => ! str_starts_with($campo, 'detalles'),
                    ARRAY_FILTER_USE_KEY,
                ));
            } catch (ValidationException $e) {
                $this->mostrarAdvertencia('Datos incompletos', collect($e->errors())->flatten()->first() ?? $e->getMessage());
                return;
            }
        }

        $this->paso = min($this->paso + 1, 3);
    }

    public function pasoAnterior(): void
    {
        $this->paso = max($this->paso - 1, 1);
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
        $this->showFinalizarModal = false;

        return redirect()->route('inventario.entradas');
    }

    public function save(InventarioService $service)
    {
        $this->authorize('inventario.entradas.crear');

        try {
            if ($this->soloLectura || ($this->entradaId && InventarioEntrada::findOrFail($this->entradaId)->estado !== 'borrador')) {
                throw ValidationException::withMessages(['entrada' => 'Una entrada confirmada o anulada no puede modificarse.']);
            }
            $this->validate();

            $entrada = DB::transaction(function () use ($service) {
                if ($this->entradaId && InventarioEntrada::whereKey($this->entradaId)->lockForUpdate()->firstOrFail()->estado !== 'borrador') {
                    throw ValidationException::withMessages(['entrada' => 'La entrada ya fue confirmada o anulada.']);
                }
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
                        'costo_unitario' => isset($detalle['costo_unitario']) && $detalle['costo_unitario'] !== '' ? $detalle['costo_unitario'] : null,
                        'total' => isset($detalle['costo_unitario']) && $detalle['costo_unitario'] !== '' ? round((float) $detalle['cantidad'] * (float) $detalle['costo_unitario'], 2) : null,
                        'fecha_vencimiento' => $detalle['fecha_vencimiento'] ?: null,
                    ]);
                }

                return $service->confirmarEntrada($entrada);
            });

            $this->entradaId = $entrada->id;
            $this->soloLectura = true;
            session()->flash('message', 'Entrada confirmada y registrada en kardex.');
            return redirect()->route('inventario.entradas.acta', $entrada);
        } catch (ValidationException $e) {
            $this->mostrarAdvertencia('No se puede generar la entrada', collect($e->errors())->flatten()->first() ?? $e->getMessage());
        } catch (\Throwable $e) {
            $this->mostrarAdvertencia('No se puede generar la entrada', $e->getMessage());
        }
    }

    public function render()
    {
        $productos = InventarioProducto::where('activo', true)->orderBy('nombre')->get();

        return view('livewire.inventario.entrada-form', [
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'requisiciones' => Requisicion::latest()->limit(100)->get(['id', 'correlativo']),
            'productosOptions' => $productos->map(fn ($producto) => ['id' => $producto->id, 'text' => $producto->codigo_interno . ' - ' . $producto->nombre])->all(),
            'productosPorId' => $productos->keyBy('id'),
            'actaRecepcionUrl' => $this->entradaId ? route('inventario.entradas.acta-recepcion', $this->entradaId) : null,
            'actaRecepcionDownloadUrl' => $this->entradaId ? route('inventario.entradas.acta-recepcion.download', $this->entradaId) : null,
        ]);
    }
}
