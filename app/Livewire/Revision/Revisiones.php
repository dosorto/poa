<?php

namespace App\Livewire\Revision;


use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Departamento\Departamento;
use App\Models\Actividad\Actividad;
use App\Models\Poa\Poa;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Revisiones extends Component
{
	protected string $layout = 'layouts.app';
	use WithPagination;

	private const ESTADOS_REVISION = ['REVISION', 'REFORMULACION', 'APROBADO', 'RECHAZADO'];

	public $search = '';
	public $perPage = 10;
	public $sortField = 'name';
	public $sortDirection = 'asc';
	public $poaYear = null;
	public $poaYears = [];

	public $showActividades = false;
	public $departamentoId = null;

	protected $queryString = [
		'poaYear' => ['except' => ''],
		'showActividades' => ['except' => false],
		'departamentoId' => ['except' => null],
	];

	public function updatingSearch()
	{
		$this->resetPage();
	}

	public function updatedPoaYear()
	{
		$this->resetPage();
	}

	public function verActividades($departamentoId)
	{
		$this->departamentoId = $departamentoId;
		$this->showActividades = true;
	}

	public function volverARevisiones()
	{
		$this->showActividades = false;
		$this->departamentoId = null;
	}



	public function mount()
	{
		$this->poaYears = Poa::orderBy('anio', 'desc')->pluck('anio')->unique()->toArray();
		if (empty($this->poaYear) && count($this->poaYears)) {
			$this->poaYear = $this->poaYears[0];
		}

		if ($this->departamentoId) {
			$this->showActividades = true;
		}
	}

	public function render()
	{
		if ($this->showActividades && $this->departamentoId) {
			return view('livewire.Revision.actividades-revision-wrapper', [
				'departamentoId' => $this->departamentoId,
				'poaYear' => $this->poaYear,
			]);
		}

		$aplicarFiltroPoa = function ($q) {
			if ($this->poaYear) {
				$q->whereHas('poa', function ($q2) {
					$q2->where('anio', $this->poaYear);
				});
			}
		};

		$departamentosQuery = Departamento::query()
			->withCount([
				'actividades as actividades_count' => function ($q) use ($aplicarFiltroPoa) {
					$q->whereIn('estado', self::ESTADOS_REVISION);
					$aplicarFiltroPoa($q);
				},
				'actividades as actividades_aprobadas_count' => function ($q) use ($aplicarFiltroPoa) {
					$q->where('estado', 'APROBADO');
					$aplicarFiltroPoa($q);
				},
				'actividades as actividades_pendientes_count' => function ($q) use ($aplicarFiltroPoa) {
					$q->whereIn('estado', ['REVISION', 'REFORMULACION']);
					$aplicarFiltroPoa($q);
				},
			])
			->whereHas('actividades', function($q) use ($aplicarFiltroPoa) {
				$q->whereIn('estado', self::ESTADOS_REVISION);
				$aplicarFiltroPoa($q);
			})
			->when($this->search, function($q) {
				$q->where('name', 'like', '%'.$this->search.'%');
			})
			->orderBy($this->sortField, $this->sortDirection);

		$revisiones = (clone $departamentosQuery)->paginate($this->perPage);

		$revisiones->getCollection()->transform(function($item) {
			$item->departamento = $item;
			return $item;
		});

		$actividadesResumenQuery = Actividad::query()
			->whereIn('estado', self::ESTADOS_REVISION)
			->when($this->poaYear, function ($q) {
				$q->whereHas('poa', function ($q2) {
					$q2->where('anio', $this->poaYear);
				});
			})
			->when($this->search, function ($q) {
				$q->whereHas('departamento', function ($departamentoQuery) {
					$departamentoQuery->where('name', 'like', '%' . $this->search . '%');
				});
			});

		$resumen = [
			'departamentos' => (clone $departamentosQuery)->count(),
			'actividades' => (clone $actividadesResumenQuery)->count(),
			'aprobadas' => (clone $actividadesResumenQuery)->where('estado', 'APROBADO')->count(),
			'pendientes' => (clone $actividadesResumenQuery)->whereIn('estado', ['REVISION', 'REFORMULACION'])->count(),
		];

		return view('livewire.Revision.revision', [
			'revisiones' => $revisiones,
			'resumen' => $resumen,
			'poaYear' => $this->poaYear,
		]);
	}
}
