<?php
//Cabiios para mostrar los CUBS
namespace App\Livewire\Cub;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Cubs\Cub;

#[Layout('layouts.app')]
class Cubs extends Component
{
    use WithPagination;

    public string $search = '';
    public int|string $perPage = 10;
    public string $sortField = 'id';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        $cubs = Cub::query()
            ->with(['unidadEjecutora'])
            ->when($this->search, function ($query) {
                $s = '%' . $this->search . '%';
                $query->where('IDUNSPSC', 'like', $s)
                    ->orWhere('descripcion_esp', 'like', $s)
                    ->orWhere('descripcion_regional', 'like', $s);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate((int) $this->perPage);

        return view('livewire.cub.cubs', [
            'cubs' => $cubs,
        ]);
    }
}
