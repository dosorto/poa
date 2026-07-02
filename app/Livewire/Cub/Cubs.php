<?php

namespace App\Livewire\Cub;

use App\Imports\CubsImport;
use App\Models\Cubs\Cub;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

#[Layout('layouts.app')]
class Cubs extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $cubId = null;
    public string $IDUNSPSC = '';
    public string $descripcion_esp = '';
    public string $descripcion_regional = '';
    public bool $isModalOpen = false;
    public bool $isImportModalOpen = false;
    public bool $showDeleteModal = false;
    public bool $showErrorModal = false;
    public string $errorMessage = '';
    public ?Cub $cubToDelete = null;
    public $excelFile = null;
    public array $importErrors = [];

    public string $search = '';
    public int|string $perPage = 10;
    public string $sortField = 'id';
    public string $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'desc'],
    ];

    protected $rules = [
        'IDUNSPSC' => 'required|max:50',
        'descripcion_esp' => 'required|max:1000',
        'descripcion_regional' => 'nullable|max:1000',
    ];

    protected $messages = [
        'IDUNSPSC.required' => 'El código UNSPSC es obligatorio.',
        'IDUNSPSC.max' => 'El código UNSPSC no puede exceder 50 caracteres.',
        'descripcion_esp.required' => 'La descripción en español es obligatoria.',
        'descripcion_esp.max' => 'La descripción en español no puede exceder 1000 caracteres.',
        'descripcion_regional.max' => 'La descripción regional no puede exceder 1000 caracteres.',
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

    public function resetInputFields(): void
    {
        $this->cubId = null;
        $this->IDUNSPSC = '';
        $this->descripcion_esp = '';
        $this->descripcion_regional = '';
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function openImportModal(): void
    {
        $this->resetImportFields();
        $this->isImportModalOpen = true;
    }

    public function store(): void
    {
        $this->validate();

        Cub::updateOrCreate(['id' => $this->cubId], [
            'IDUNSPSC' => $this->IDUNSPSC,
            'descripcion_esp' => $this->descripcion_esp,
            'descripcion_regional' => $this->descripcion_regional,
        ]);

        session()->flash('message', $this->cubId
            ? 'CUB actualizado correctamente.'
            : 'CUB creado correctamente.');

        $this->closeModal();
        $this->resetPage();
    }

    public function edit(int $id): void
    {
        $cub = Cub::findOrFail($id);

        $this->cubId = $cub->id;
        $this->IDUNSPSC = $cub->IDUNSPSC;
        $this->descripcion_esp = $cub->descripcion_esp;
        $this->descripcion_regional = $cub->descripcion_regional ?? '';
        $this->isModalOpen = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->cubToDelete = Cub::findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        try {
            if ($this->cubToDelete) {
                $this->cubToDelete->delete();
                session()->flash('message', 'CUB eliminado correctamente.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'No se pudo eliminar el CUB.');
        }

        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->resetInputFields();
    }

    public function closeImportModal(): void
    {
        $this->isImportModalOpen = false;
        $this->resetImportFields();
    }

    public function resetImportFields(): void
    {
        $this->excelFile = null;
        $this->importErrors = [];
        $this->resetValidation(['excelFile']);
    }

    public function importExcel(): void
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
        $import = new CubsImport();

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
        $requiredHeaders = ['IDUNSPSC', 'descripcion_esp', 'descripcion_regional'];
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

            $IDUNSPSC = trim((string) ($row[$headerIndexes['IDUNSPSC']] ?? ''));
            $descripcionEsp = trim((string) ($row[$headerIndexes['descripcion_esp']] ?? ''));
            $descripcionRegional = trim((string) ($row[$headerIndexes['descripcion_regional']] ?? ''));

            if ($IDUNSPSC === '' || $descripcionEsp === '') {
                $skipped++;
                $this->importErrors[] = "Fila {$rowNumber}: IDUNSPSC y descripcion_esp son obligatorios.";
                continue;
            }

            if (strlen($IDUNSPSC) > 50 || strlen($descripcionEsp) > 1000 || strlen($descripcionRegional) > 1000) {
                $skipped++;
                $this->importErrors[] = "Fila {$rowNumber}: uno o más campos exceden el máximo permitido.";
                continue;
            }

            $cub = Cub::updateOrCreate([
                'IDUNSPSC' => $IDUNSPSC,
            ], [
                'descripcion_esp' => $descripcionEsp,
                'descripcion_regional' => $descripcionRegional !== '' ? $descripcionRegional : $descripcionEsp,
            ]);

            $cub->wasRecentlyCreated ? $created++ : $updated++;
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

    private function isEmptyCsvRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->cubToDelete = null;
    }

    public function closeErrorModal(): void
    {
        $this->showErrorModal = false;
        $this->errorMessage = '';
    }

    public function render()
    {
        $cubs = Cub::query()
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
