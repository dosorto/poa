<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CubsImport implements ToCollection, WithHeadingRow, WithValidation, WithChunkReading
{
    use Importable;

    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $importErrors = [];

    private int $processedRows = 1;

    public function collection(Collection $rows): void
    {
        $validRows = [];

        foreach ($rows as $row) {
            $this->processedRows++;
            $row = $row->toArray();

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $IDUNSPSC = trim((string) $this->value($row, ['idunspsc', 'IDUNSPSC', 'unspsc', 'codigo_unspsc', 'codigo']));
            $descripcionEsp = trim((string) $this->value($row, ['descripcion_esp', 'descripcion_espanol', 'descripcion_en_espanol']));
            $descripcionRegional = trim((string) $this->value($row, ['descripcion_regional']));

            if ($IDUNSPSC === '' || $descripcionEsp === '') {
                $this->skipped++;
                $this->importErrors[] = "Fila {$this->processedRows}: IDUNSPSC y descripcion_esp son obligatorios.";
                continue;
            }

            if (strlen($IDUNSPSC) > 50 || strlen($descripcionEsp) > 1000 || strlen($descripcionRegional) > 1000) {
                $this->skipped++;
                $this->importErrors[] = "Fila {$this->processedRows}: uno o más campos exceden el máximo permitido.";
                continue;
            }

            $validRows[$IDUNSPSC] = [
                'IDUNSPSC' => $IDUNSPSC,
                'descripcion_esp' => $descripcionEsp,
                'descripcion_regional' => $descripcionRegional !== '' ? $descripcionRegional : $descripcionEsp,
            ];
        }

        if (empty($validRows)) {
            return;
        }

        $now = now();
        $userId = auth()->id();
        $codes = array_keys($validRows);

        $existingCodes = DB::table('cubs')
            ->whereNull('deleted_at')
            ->whereIn('IDUNSPSC', $codes)
            ->pluck('IDUNSPSC')
            ->all();

        $existingLookup = array_flip($existingCodes);
        $newRows = [];
        $updateRows = [];

        foreach ($validRows as $code => $data) {
            if (isset($existingLookup[$code])) {
                $updateRows[] = $data;
                continue;
            }

            $newRows[] = [
                'IDUNSPSC' => $data['IDUNSPSC'],
                'descripcion_esp' => $data['descripcion_esp'],
                'descripcion_regional' => $data['descripcion_regional'],
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($updateRows)) {
            foreach (array_chunk($updateRows, 500) as $chunk) {
                $this->bulkUpdate($chunk, $userId, $now);
            }

            $this->updated += count($updateRows);
        }

        if (! empty($newRows)) {
            foreach (array_chunk($newRows, 500) as $chunk) {
                DB::table('cubs')->insert($chunk);
            }

            $this->created += count($newRows);
        }
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
        return 1000;
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

    private function bulkUpdate(array $rows, ?int $userId, mixed $now): void
    {
        $descripcionEspCase = 'CASE IDUNSPSC';
        $descripcionRegionalCase = 'CASE IDUNSPSC';
        $descripcionEspBindings = [];
        $descripcionRegionalBindings = [];
        $codes = [];

        foreach ($rows as $row) {
            $descripcionEspCase .= ' WHEN ? THEN ?';
            $descripcionEspBindings[] = $row['IDUNSPSC'];
            $descripcionEspBindings[] = $row['descripcion_esp'];

            $descripcionRegionalCase .= ' WHEN ? THEN ?';
            $descripcionRegionalBindings[] = $row['IDUNSPSC'];
            $descripcionRegionalBindings[] = $row['descripcion_regional'];

            $codes[] = $row['IDUNSPSC'];
        }

        $descripcionEspCase .= ' ELSE descripcion_esp END';
        $descripcionRegionalCase .= ' ELSE descripcion_regional END';
        $placeholders = implode(',', array_fill(0, count($codes), '?'));

        DB::update(
            "UPDATE cubs
             SET descripcion_esp = {$descripcionEspCase},
                 descripcion_regional = {$descripcionRegionalCase},
                 updated_by = ?,
                 updated_at = ?
             WHERE deleted_at IS NULL
               AND IDUNSPSC IN ({$placeholders})",
            [
                ...$descripcionEspBindings,
                ...$descripcionRegionalBindings,
                $userId,
                $now,
                ...$codes,
            ]
        );
    }
}
