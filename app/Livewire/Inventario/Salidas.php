<?php

namespace App\Livewire\Inventario;

use App\Models\Inventario\InventarioSalida;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Salidas extends Component
{
    use WithPagination;

    public string $search = '';
    public string $periodo = 'todos';
    public string $fechaDesde = '';
    public string $fechaHasta = '';

    public function updatedPeriodo(): void
    {
        [$this->fechaDesde, $this->fechaHasta] = match ($this->periodo) {
            'hoy' => [today()->toDateString(), today()->toDateString()],
            'semana' => [today()->startOfWeek()->toDateString(), today()->endOfWeek()->toDateString()],
            'mes' => [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()],
            'personalizado' => [$this->fechaDesde, $this->fechaHasta],
            default => ['', ''],
        };
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->periodo = 'personalizado';
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->periodo = 'personalizado';
        $this->resetPage();
    }

    public function limpiarFechas(): void
    {
        $this->reset('periodo', 'fechaDesde', 'fechaHasta');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = '%' . $this->search . '%';
        $validacion = Validator::make(
            ['desde' => $this->fechaDesde, 'hasta' => $this->fechaHasta],
            [
                'desde' => ['nullable', 'date_format:Y-m-d'],
                'hasta' => ['nullable', 'date_format:Y-m-d', ...($this->fechaDesde !== '' ? ['after_or_equal:desde'] : [])],
            ],
            [
                'date_format' => 'Ingrese una fecha válida.',
                'after_or_equal' => 'La fecha «Hasta» debe ser igual o posterior a «Desde».',
            ],
        );
        $fechasValidas = $validacion->passes();

        return view('livewire.inventario.salidas', [
            'errorFechas' => $validacion->errors()->first(),
            'salidas' => InventarioSalida::with(['bodega', 'actaEntrega.tipoActaEntrega', 'requisicion.departamento'])
                ->when(! $fechasValidas, fn ($query) => $query->whereRaw('1 = 0'))
                ->when($fechasValidas && $this->fechaDesde !== '', fn ($query) => $query->whereDate('fecha_salida', '>=', $this->fechaDesde))
                ->when($fechasValidas && $this->fechaHasta !== '', fn ($query) => $query->whereDate('fecha_salida', '<=', $this->fechaHasta))
                ->where(function ($query) use ($search) {
                    $query->where('numero_salida', 'like', $search)
                        ->orWhereHas('requisicion', fn ($query) => $query->where('correlativo', 'like', $search))
                        ->orWhereHas('actaEntrega', fn ($query) => $query->where('correlativo', 'like', $search));
                })
                ->latest()
                ->paginate(10),
        ]);
    }
}
