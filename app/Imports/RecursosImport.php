<?php

namespace App\Imports;

use App\Models\Cubs\Cub;
use App\Models\GrupoGastos\ObjetoGasto;
use App\Models\ProcesoCompras\ProcesoCompra;
use App\Models\Requisicion\UnidadMedida;
use App\Models\Tareas\TareaHistorico;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class RecursosImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading
{
    use Importable;
    use RemembersRowNumber;

    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $importErrors = [];

    public function model(array $row): ?Model
    {
        if ($this->isEmptyRow($row)) {
            return null;
        }

        $rowNumber = $this->getRowNumber();

        $nombre = trim((string) $this->value($row, ['nombre']));
        $idobjeto = trim((string) $this->value($row, ['idobjeto', 'id_objeto']));
        $idunidad = trim((string) $this->value($row, ['idunidad', 'id_unidad']));
        $idProcesoCompra = trim((string) $this->value($row, ['id_proceso_compra', 'idprocesocompra', 'idProcesoCompra']));
        $idCubs = trim((string) $this->value($row, ['id_cubs', 'idcubs', 'idCubs']));

        $error = $this->validateImportRow($nombre, $idobjeto, $idunidad, $idProcesoCompra, $idCubs);

        if ($error) {
            $this->skipped++;
            $this->importErrors[] = "Fila {$rowNumber}: {$error}";
            return null;
        }

        $recurso = TareaHistorico::updateOrCreate([
            'nombre' => $nombre,
            'idobjeto' => $idobjeto,
            'idunidad' => (int) $idunidad,
            'idProcesoCompra' => (int) $idProcesoCompra,
            'idCubs' => $idCubs,
        ], []);

        $recurso->wasRecentlyCreated ? $this->created++ : $this->updated++;

        return null;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function rules(): array
    {
        return [];
    }

    public function chunkSize(): int
    {
        return 500;
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

    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return null;
    }

    private function isEmptyRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }
}
