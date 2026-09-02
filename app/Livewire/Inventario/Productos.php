<?php

namespace App\Livewire\Inventario;

use App\Models\Cubs\Cub;
use App\Models\GrupoGastos\ObjetoGasto;
use App\Models\Inventario\InventarioProducto;
use App\Models\Requisicion\UnidadMedida;
use App\Models\Tareas\TareaHistorico;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Productos extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $productoId = null;
    public ?int $recurso_id = null;
    public ?string $idCubs = null;
    public ?string $idobjeto = null;
    public ?int $unidad_medida_id = null;
    public string $codigo_interno = '';
    public ?string $codigo_barra = null;
    public string $nombre = '';
    public ?string $descripcion = null;
    public ?string $marca = null;
    public ?string $presentacion = null;
    public ?string $stock_minimo = null;
    public bool $maneja_lote = false;
    public bool $maneja_vencimiento = false;
    public bool $activo = true;

    protected function rules(): array
    {
        return [
            'recurso_id' => 'nullable|exists:tareas_historicos,id',
            'idCubs' => 'nullable|exists:cubs,IDUNSPSC',
            'idobjeto' => 'nullable|exists:objetogastos,identificador',
            'unidad_medida_id' => 'required|exists:unidadmedidas,id',
            'codigo_interno' => 'required|string|max:255|unique:inventario_productos,codigo_interno,' . $this->productoId,
            'codigo_barra' => 'nullable|string|max:255|unique:inventario_productos,codigo_barra,' . $this->productoId,
            'nombre' => 'required|string|min:2|max:255',
            'descripcion' => 'nullable|string',
            'marca' => 'nullable|string|max:255',
            'presentacion' => 'nullable|string|max:255',
            'stock_minimo' => 'nullable|numeric|min:0',
            'maneja_lote' => 'boolean',
            'maneja_vencimiento' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $producto = InventarioProducto::findOrFail($id);
        $this->fill($producto->only([
            'recurso_id',
            'idCubs',
            'idobjeto',
            'unidad_medida_id',
            'codigo_interno',
            'codigo_barra',
            'nombre',
            'descripcion',
            'marca',
            'presentacion',
            'stock_minimo',
            'maneja_lote',
            'maneja_vencimiento',
            'activo',
        ]));
        $this->productoId = $producto->id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $recursoAnteriorId = $this->productoId
            ? InventarioProducto::whereKey($this->productoId)->value('recurso_id')
            : null;

        $producto = InventarioProducto::updateOrCreate(['id' => $this->productoId], [
            'recurso_id' => $this->recurso_id,
            'idCubs' => $this->idCubs ?: null,
            'idobjeto' => $this->idobjeto ?: null,
            'unidad_medida_id' => $this->unidad_medida_id,
            'codigo_interno' => $this->codigo_interno,
            'codigo_barra' => $this->codigo_barra ?: null,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'marca' => $this->marca,
            'presentacion' => $this->presentacion,
            'stock_minimo' => $this->stock_minimo ?: null,
            'maneja_lote' => $this->maneja_lote,
            'maneja_vencimiento' => $this->maneja_vencimiento,
            'activo' => $this->activo,
        ]);

        if ($recursoAnteriorId && $recursoAnteriorId !== $this->recurso_id) {
            $producto->recursos()->detach($recursoAnteriorId);
        }

        if ($this->recurso_id) {
            $producto->recursos()->syncWithoutDetaching([$this->recurso_id]);
        }

        session()->flash('message', 'Producto guardado correctamente.');
        $this->closeModal();
    }

    public function toggleActivo(int $id): void
    {
        $producto = InventarioProducto::findOrFail($id);
        $producto->update(['activo' => ! $producto->activo]);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'productoId',
            'recurso_id',
            'idCubs',
            'idobjeto',
            'unidad_medida_id',
            'codigo_interno',
            'codigo_barra',
            'nombre',
            'descripcion',
            'marca',
            'presentacion',
            'stock_minimo',
            'maneja_lote',
            'maneja_vencimiento',
        ]);
        $this->activo = true;
        $this->resetValidation();
    }

    public function render()
    {
        $search = '%' . $this->search . '%';

        return view('livewire.inventario.productos', [
            'productos' => InventarioProducto::with(['unidadMedida', 'recurso', 'cub', 'objetoGasto'])
                ->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', $search)
                        ->orWhere('codigo_interno', 'like', $search)
                        ->orWhere('codigo_barra', 'like', $search);
                })
                ->latest()
                ->paginate(10),
            'recursos' => TareaHistorico::orderBy('nombre')->limit(200)->get(['id', 'nombre']),
            'unidades' => UnidadMedida::orderBy('nombre')->get(['id', 'nombre']),
            'objetos' => ObjetoGasto::orderBy('identificador')->get(['identificador', 'nombre']),
            'cubs' => Cub::orderBy('IDUNSPSC')->limit(200)->get(['IDUNSPSC', 'descripcion_esp']),
        ]);
    }
}
