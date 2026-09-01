<?php

namespace App\Livewire\Inventario;

use App\Imports\InventarioInicialImport;
use App\Models\Inventario\InventarioBodega;
use App\Models\Inventario\InventarioImportacion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class ImportacionInicial extends Component
{
    use WithFileUploads;

    public ?int $bodega_id = null;
    public $excelFile;
    public array $errores = [];
    public int $importados = 0;
    public int $total = 0;

    protected array $messages = [
        'bodega_id.required' => 'Selecciona una bodega destino.',
        'excelFile.required' => 'Selecciona un archivo antes de importar.',
        'excelFile.file' => 'El archivo seleccionado no es valido.',
        'excelFile.extensions' => 'El archivo debe ser .xlsx, .xls o .csv.',
        'excelFile.max' => 'El archivo no debe superar 10 MB.',
    ];

    public function importar(): void
    {
        $this->validate([
            'bodega_id' => 'required|exists:inventario_bodegas,id',
            'excelFile' => 'required|file|extensions:xlsx,xls,csv|max:10240',
        ]);

        $path = $this->excelFile->getRealPath();
        $extension = strtolower($this->excelFile->getClientOriginalExtension());

        if ($extension !== 'csv' && ! class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw ValidationException::withMessages([
                'excelFile' => 'La libreria PhpSpreadsheet no esta instalada o no esta registrada en Composer. Puedes importar CSV o ejecutar composer dump-autoload despues de habilitar ext-zip.',
            ]);
        }

        $importacion = InventarioImportacion::create([
            'archivo' => $this->excelFile->getClientOriginalName(),
            'usuario_id' => Auth::id(),
            'fecha' => now(),
            'estado' => 'procesando',
        ]);

        $import = new InventarioInicialImport($this->bodega_id, Auth::id(), $importacion->id);

        if ($extension === 'csv') {
            $this->importarCsv($path, $import);
        } else {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $spreadsheet = $reader->load($path);
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $headers = array_map(fn ($header) => str($header)->lower()->snake()->toString(), array_shift($rows) ?? []);

            foreach ($rows as $row) {
                $import->model(array_combine($headers, array_values($row)) ?: []);
            }
        }

        $this->errores = $import->errores;
        $this->importados = $import->importados;
        $this->total = $import->total;

        session()->flash('message', 'Importacion procesada. Filas importadas: ' . $this->importados . '.');
        $this->reset(['excelFile']);
    }

    private function importarCsv(string $path, InventarioInicialImport $import): void
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages(['excelFile' => 'No se pudo leer el archivo CSV.']);
        }

        $headers = fgetcsv($handle);
        $headers = array_map(fn ($header) => str($header)->lower()->snake()->toString(), $headers ?: []);

        while (($row = fgetcsv($handle)) !== false) {
            $import->model(array_combine($headers, $row) ?: []);
        }

        fclose($handle);
    }

    public function descargarPlantilla()
    {
        $headers = [
            'codigo_interno',
            'codigo_barra',
            'nombre',
            'descripcion',
            'unidad_medida_id',
            'idobjeto',
            'idCubs',
            'codigo_lote',
            'cantidad',
            'fecha_vencimiento',
            'ubicacion',
            'stock_minimo',
        ];

        return response()->streamDownload(function () use ($headers) {
            echo implode(',', $headers) . PHP_EOL;
        }, 'plantilla_inventario.csv');
    }

    public function render()
    {
        return view('livewire.inventario.importacion-inicial', [
            'bodegas' => InventarioBodega::where('activo', true)->orderBy('nombre')->get(),
            'importaciones' => InventarioImportacion::with('usuario')->latest()->limit(10)->get(),
        ]);
    }
}
