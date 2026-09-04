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

    public function updated($property, $value): void
    {
        if ($property !== 'recurso_id') {
            return;
        }

        if (! $value) {
            return;
        }

        $this->cargarDatosDelRecurso((int) $value);
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
        $this->generarCodigoInternoSiFalta();
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

    public function searchRecursosInventario($search = ''): array
    {
        $recursos = TareaHistorico::query()
            ->select('id', 'nombre')
            ->when($search, function ($query) use ($search) {
                $query->where('nombre', 'like', '%' . $search . '%');
            })
            ->orderBy('nombre')
            ->orderBy('id')
            ->limit(80)
            ->get()
            ->unique(fn ($recurso) => $this->normalizarNombreRecurso($recurso->nombre))
            ->map(fn ($recurso) => [
                'id' => (string) $recurso->id,
                'text' => $recurso->nombre,
            ])
            ->values();

        if ($this->recurso_id) {
            $selectedId = (string) $this->recurso_id;

            if (! $recursos->contains(fn ($option) => (string) $option['id'] === $selectedId)) {
                $recursoSeleccionado = TareaHistorico::find($this->recurso_id);

                if ($recursoSeleccionado) {
                    $recursos->prepend([
                        'id' => $selectedId,
                        'text' => $recursoSeleccionado->nombre,
                    ]);
                }
            }
        }

        return $recursos->toArray();
    }

    public function searchObjetosGastoInventario($search = ''): array
    {
        $objetos = ObjetoGasto::query()
            ->select('identificador', 'nombre')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('identificador', 'like', '%' . $search . '%')
                        ->orWhere('nombre', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('identificador')
            ->limit(50)
            ->get()
            ->map(fn ($objeto) => [
                'id' => (string) $objeto->identificador,
                'text' => $objeto->identificador . ' - ' . $objeto->nombre,
            ])
            ->values();

        if ($this->idobjeto) {
            $selectedId = (string) $this->idobjeto;

            if (! $objetos->contains(fn ($option) => (string) $option['id'] === $selectedId)) {
                $objetoSeleccionado = ObjetoGasto::where('identificador', $this->idobjeto)->first();

                if ($objetoSeleccionado) {
                    $objetos->prepend([
                        'id' => $selectedId,
                        'text' => $objetoSeleccionado->identificador . ' - ' . $objetoSeleccionado->nombre,
                    ]);
                }
            }
        }

        return $objetos->toArray();
    }

    public function searchCubsInventario($search = ''): array
    {
        $cubs = Cub::query()
            ->select('IDUNSPSC', 'descripcion_esp')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('IDUNSPSC', 'like', '%' . $search . '%')
                        ->orWhere('descripcion_esp', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('IDUNSPSC')
            ->limit(50)
            ->get()
            ->map(fn ($cub) => [
                'id' => (string) $cub->IDUNSPSC,
                'text' => $cub->IDUNSPSC . ' - ' . $cub->descripcion_esp,
            ])
            ->values();

        if ($this->idCubs) {
            $selectedId = (string) $this->idCubs;

            if (! $cubs->contains(fn ($option) => (string) $option['id'] === $selectedId)) {
                $cubSeleccionado = Cub::where('IDUNSPSC', $this->idCubs)->first();

                if ($cubSeleccionado) {
                    $cubs->prepend([
                        'id' => $selectedId,
                        'text' => $cubSeleccionado->IDUNSPSC . ' - ' . $cubSeleccionado->descripcion_esp,
                    ]);
                }
            }
        }

        return $cubs->toArray();
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

    private function cargarDatosDelRecurso(int $recursoId): void
    {
        $recurso = TareaHistorico::with([
                'detallesTecnicos' => fn ($query) => $query->where('estado', true)->latest(),
            ])
            ->find($recursoId);

        if (! $recurso) {
            return;
        }

        $detallesTecnicos = $recurso->detallesTecnicos->pluck('nombre')->filter();
        $detalleNombre = $detallesTecnicos->first();
        $detalleTecnico = $detallesTecnicos->isNotEmpty()
            ? $detallesTecnicos->implode("\n")
            : null;

        $productoRelacionado = InventarioProducto::where(function ($query) use ($recursoId) {
                $query->where('recurso_id', $recursoId)
                    ->orWhereHas('recursos', fn ($recursos) => $recursos->where('tareas_historicos.id', $recursoId));
            })
            ->when($this->productoId, fn ($query) => $query->where('id', '!=', $this->productoId))
            ->latest()
            ->first();

        if ($productoRelacionado) {
            $this->nombre = $productoRelacionado->nombre;
            $this->descripcion = $detalleTecnico ?: $productoRelacionado->descripcion;
            $this->marca = $productoRelacionado->marca;
            $this->presentacion = $productoRelacionado->presentacion;
            $this->unidad_medida_id = $productoRelacionado->unidad_medida_id;
            $this->idCubs = $productoRelacionado->idCubs ?: $recurso->idCubs;
            $this->idobjeto = $productoRelacionado->idobjeto ?: $recurso->idobjeto;

            return;
        }

        $this->nombre = $detalleNombre ?: $recurso->nombre;
        $this->descripcion = $detalleTecnico ?: $recurso->nombre;
        $this->marca = null;
        $this->presentacion = null;
        $this->unidad_medida_id = $recurso->idunidad;
        $this->idCubs = $recurso->idCubs;
        $this->idobjeto = $recurso->idobjeto;
    }

    private function generarCodigoInternoSiFalta(): void
    {
        if (filled($this->codigo_interno)) {
            return;
        }

        $siguienteId = ((int) InventarioProducto::withTrashed()->max('id')) + 1;

        do {
            $codigo = 'INV-' . str_pad((string) $siguienteId, 6, '0', STR_PAD_LEFT);
            $siguienteId++;
        } while (InventarioProducto::withTrashed()->where('codigo_interno', $codigo)->exists());

        $this->codigo_interno = $codigo;
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
            'recursos' => $this->searchRecursosInventario(),
            'unidades' => UnidadMedida::orderBy('nombre')->get(['id', 'nombre']),
            'objetos' => $this->searchObjetosGastoInventario(),
            'cubs' => $this->searchCubsInventario(),
        ]);
    }

    private function normalizarNombreRecurso(?string $nombre): string
    {
        return preg_replace('/\s+/', ' ', mb_strtoupper(trim((string) $nombre))) ?: '';
    }
}
