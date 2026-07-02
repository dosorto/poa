<?php

namespace App\Livewire\Tarea;

use App\Imports\RecursosImport;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Attributes\Renderless;
use App\Models\Tareas\TareaHistorico;
use App\Models\GrupoGastos\ObjetoGasto;
use App\Models\Requisicion\UnidadMedida;
use App\Models\ProcesoCompras\ProcesoCompra;
use App\Models\Cubs\Cub;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
    class TareasHistorico extends Component
    {
        use WithFileUploads;
        use WithPagination;

        protected string $layout = 'layouts.app';

        public $nombre;
        public $idobjeto;
        public $idunidad;
        public $idProcesoCompra;
        public $idCubs;
        public $tareaId;
        public $search = '';
        public $perPage = 10;
        public $sortField = 'id';
        public $sortDirection = 'desc';
        public $showModal = false;
        public $showDeleteModal = false;
        public $showImportModal = false;
        public $recursoToDelete;
        public $errorMessage = '';
        public $showErrorModal = false;
        public $isEditing = false;
        public $excelFile = null;
        public array $importErrors = [];

        protected $rules = [
            'nombre' => 'required|min:3',
            'idobjeto' => 'required|exists:objetogastos,identificador',
            'idunidad' => 'required|exists:unidadmedidas,id',
            'idProcesoCompra' => 'required|exists:procesos_compras,id',
            'idCubs' => 'nullable|exists:cubs,IDUNSPSC',
        ];

        protected $messages = [
            'nombre.required' => 'El nombre del recurso es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'idobjeto.required' => 'El objeto de gasto es obligatorio.',
            'idobjeto.exists' => 'El objeto de gasto seleccionado no existe.',
            'idunidad.required' => 'La unidad de medida es obligatoria.',
            'idunidad.exists' => 'La unidad de medida seleccionada no existe.',
            'idProcesoCompra.required' => 'El proceso de compra es obligatorio.',
            'idProcesoCompra.exists' => 'El proceso de compra seleccionado no existe.',
            'idCubs.exists' => 'El CUBS seleccionado no existe.',
        ];

        protected $queryString = [
            'search' => ['except' => ''],
            'sortField' => ['except' => 'id'],
            'sortDirection' => ['except' => 'desc'],
        ];

        public function updatedNombre($value)
        {
            $this->nombre = is_array($value) ? '' : $value;
        }

        public function sortBy($field)
        {
            if ($this->sortField === $field) {
                $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortDirection = 'asc';
            }
            $this->sortField = $field;
        }

        public function updatingSearch()
        {
            $this->resetPage();
        }

        public function resetInputFields()
        {
            $this->nombre = '';
            $this->idobjeto = null;
            $this->idunidad = null;
            $this->idProcesoCompra = null;
            $this->idCubs = null;
            $this->tareaId = null;
            $this->resetValidation();
        }

        public function create()
        {
            $this->resetInputFields();
            $this->isEditing = false;
            $this->openModal();
        }

        public function openImportModal()
        {
            $this->resetImportFields();
            $this->showImportModal = true;
        }

        public function openModal()
        {
            $this->showModal = true;
        }

        public function closeModal()
        {
            $this->showModal = false;
            $this->resetInputFields();
        }

        public function closeImportModal()
        {
            $this->showImportModal = false;
            $this->resetImportFields();
        }

        public function resetImportFields()
        {
            $this->excelFile = null;
            $this->importErrors = [];
            $this->resetValidation(['excelFile']);
        }

        public function closeDeleteModal()
        {
            $this->showDeleteModal = false;
            $this->recursoToDelete = null;
        }

        public function closeErrorModal()
        {
            $this->showErrorModal = false;
            $this->errorMessage = '';
        }

        public function store()
        {
            $this->validate();

            try {
                $user = Auth::user();
                $data = [
                    'nombre' => $this->nombre,
                    'idobjeto' => $this->idobjeto,
                    'idunidad' => $this->idunidad,
                    'idProcesoCompra' => $this->idProcesoCompra,
                    'idCubs' => $this->idCubs,
                    'created_by' => $user->id,
                ];

                $tarea = TareaHistorico::updateOrCreate(
                    ['id' => $this->tareaId],
                    $data
                );

                session()->flash('message',
                    $this->tareaId
                        ? 'Recurso actualizado correctamente.'
                        : 'Recurso creado correctamente.'
                );

                $this->closeModal();
                $this->resetPage();
            } catch (\Exception $e) {
                $this->errorMessage = 'Error al guardar: ' . $e->getMessage();
                $this->showErrorModal = true;
            }
        }

        public function importExcel()
        {
            @set_time_limit(300);

            $this->validate([
                'excelFile' => 'required|file|mimes:xlsx,xls|max:5120',
            ], [
                'excelFile.required' => 'Debes seleccionar un archivo Excel.',
                'excelFile.file' => 'El archivo seleccionado no es válido.',
                'excelFile.mimes' => 'El archivo debe ser Excel (.xlsx o .xls).',
                'excelFile.max' => 'El archivo no debe superar 5 MB.',
            ]);

            $this->importErrors = [];
            $import = new RecursosImport();

            Excel::import($import, $this->excelFile);

            $this->importErrors = $import->importErrors;

            $message = "Importación completada. Creados: {$import->created}. Actualizados: {$import->updated}. Omitidos: {$import->skipped}.";

            /*
            Código CSV anterior, conservado para reversa:

            $path = $this->csvFile->getRealPath();
            $handle = fopen($path, 'r');

            if ($handle === false) {
                $this->addError('csvFile', 'No se pudo leer el archivo CSV.');
                return;
            }

            $headers = fgetcsv($handle);

            if ($headers === false) {
                fclose($handle);
                $this->addError('csvFile', 'El archivo CSV está vacío.');
                return;
            }

            $headers = array_map(fn ($header) => trim((string) $header, " \t\n\r\0\x0B\xEF\xBB\xBF"), $headers);
            $requiredHeaders = ['nombre', 'idobjeto', 'idunidad', 'idProcesoCompra', 'idCubs'];
            $missingHeaders = array_diff($requiredHeaders, $headers);

            if (! empty($missingHeaders)) {
                fclose($handle);
                $this->addError('csvFile', 'Faltan columnas requeridas: ' . implode(', ', $missingHeaders) . '.');
                return;
            }

            $headerIndexes = array_flip($headers);
            $created = 0;
            $updated = 0;
            $skipped = 0;
            $rowNumber = 1;
            $this->importErrors = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isEmptyCsvRow($row)) {
                    continue;
                }

                $nombre = trim((string) ($row[$headerIndexes['nombre']] ?? ''));
                $idobjeto = trim((string) ($row[$headerIndexes['idobjeto']] ?? ''));
                $idunidad = trim((string) ($row[$headerIndexes['idunidad']] ?? ''));
                $idProcesoCompra = trim((string) ($row[$headerIndexes['idProcesoCompra']] ?? ''));
                $idCubs = trim((string) ($row[$headerIndexes['idCubs']] ?? ''));

                $error = $this->validateImportRow($nombre, $idobjeto, $idunidad, $idProcesoCompra, $idCubs);

                if ($error) {
                    $skipped++;
                    $this->importErrors[] = "Fila {$rowNumber}: {$error}";
                    continue;
                }

                $recurso = TareaHistorico::updateOrCreate([
                    'nombre' => $nombre,
                    'idobjeto' => $idobjeto,
                    'idunidad' => (int) $idunidad,
                    'idProcesoCompra' => (int) $idProcesoCompra,
                    'idCubs' => $idCubs,
                ], []);

                $recurso->wasRecentlyCreated ? $created++ : $updated++;
            }

            fclose($handle);

            $message = "Importación completada. Creados: {$created}. Actualizados: {$updated}. Omitidos: {$skipped}.";
            */

            if (! empty($this->importErrors)) {
                session()->flash('error', $message . ' Revisa los errores en el modal.');
                return;
            }

            session()->flash('message', $message);
            $this->closeImportModal();
            $this->resetPage();
        }

        private function validateImportRow(string $nombre, string $idobjeto, string $idunidad, string $idProcesoCompra, string $idCubs): ?string
        {
            if ($nombre === '' || $idobjeto === '' || $idunidad === '' || $idProcesoCompra === '' || $idCubs === '') {
                return 'todos los campos son obligatorios.';
            }

            if (strlen($nombre) < 3) {
                return 'el nombre debe tener al menos 3 caracteres.';
            }

            if (! ctype_digit($idunidad) || ! ctype_digit($idProcesoCompra)) {
                return 'los IDs de unidad y proceso deben ser números enteros.';
            }

            if (! ObjetoGasto::where('identificador', $idobjeto)->exists()) {
                return "el objeto de gasto con identificador {$idobjeto} no existe.";
            }

            if (! UnidadMedida::whereKey((int) $idunidad)->exists()) {
                return "la unidad de medida {$idunidad} no existe.";
            }

            if (! ProcesoCompra::whereKey((int) $idProcesoCompra)->exists()) {
                return "el proceso de compra {$idProcesoCompra} no existe.";
            }

            if (! Cub::where('IDUNSPSC', $idCubs)->exists()) {
                return "el CUBS con código UNSPSC {$idCubs} no existe.";
            }

            return null;
        }

        private function isEmptyCsvRow(array $row): bool
        {
            return collect($row)->every(fn ($value) => trim((string) $value) === '');
        }

        public function edit($id)
        {
            $tarea = TareaHistorico::findOrFail($id);
            $this->tareaId = $id;
            $this->nombre = $tarea->nombre;
            $this->idobjeto = $tarea->idobjeto;
            $this->idunidad = $tarea->idunidad;
            $this->idProcesoCompra = $tarea->idProcesoCompra;
            $this->idCubs = $tarea->idCubs;
            $this->isEditing = true;
            $this->openModal();
        }

        public function confirmDelete($id)
        {
            $this->recursoToDelete = TareaHistorico::findOrFail($id);
            $this->showDeleteModal = true;
        }

        public function delete()
        {
            try {
                $this->recursoToDelete->delete();
                session()->flash('message', 'Recurso eliminado correctamente.');
                $this->resetPage();
            } catch (\Exception $e) {
                $this->errorMessage = 'Error al eliminar el recurso: ' . $e->getMessage();
                $this->showDeleteModal = false;
                $this->showErrorModal = true;
            }
        }

        #[Renderless]
        public function buscarCubs(string $query): array
        {
            return Cub::where('descripcion_esp', 'like', "%{$query}%")
                ->orWhere('IDUNSPSC', 'like', "%{$query}%")
                ->limit(5)
                ->get(['IDUNSPSC', 'descripcion_esp'])
                ->map(fn($cub) => [
                    'id'   => $cub->IDUNSPSC,
                    'text' => ($cub->IDUNSPSC ?? '') . ' - ' . ($cub->descripcion_esp ?? ''),
                ])
                ->values()
                ->toArray();
        }

        public function render()
        {
            $recursos = TareaHistorico::with(['objeto', 'unidadMedida', 'procesoCompra', 'cub'])
                ->where(function ($query) {
                    if ($this->search) {
                        $query->where('nombre', 'like', '%' . $this->search . '%');
                    }
                })
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage);

            $objetosGasto = ObjetoGasto::all()->map(function($obj) {
                return ['value' => $obj->identificador, 'text' => $obj->identificador . ' - ' . $obj->nombre];
            })->toArray();
            $unidadesMedida = UnidadMedida::all()->map(function($u) {
                return ['value' => $u->id, 'text' => $u->nombre];
            })->toArray();
            $procesosCompra = ProcesoCompra::all()->map(function($p) {
                return ['value' => $p->id, 'text' => $p->nombre_proceso];
            })->toArray();
            $cubsSeleccionados = $this->idCubs
                ? Cub::where('IDUNSPSC', $this->idCubs)
                    ->get(['IDUNSPSC', 'descripcion_esp'])
                    ->map(fn ($cub) => [
                        'value' => $cub->IDUNSPSC,
                        'text' => ($cub->IDUNSPSC ?? '') . ' - ' . ($cub->descripcion_esp ?? ''),
                    ])
                    ->toArray()
                : [];
            return view('livewire.Tareas.Tarea-historico', [
                'recursos' => $recursos,
                'objetosGasto' => $objetosGasto,
                'unidadesMedida' => $unidadesMedida,
                'procesosCompra' => $procesosCompra,
                'cubsSeleccionados' => $cubsSeleccionados,
            ]);
        }
    }
