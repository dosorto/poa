<?php

namespace App\Livewire\Consolidado;

use App\Models\Actividad\Actividad;
use App\Models\Dimension\Dimension;
use App\Models\Poa\Poa;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use ZipArchive;

#[Layout('layouts.app')]
class PlanificacionConsolidado extends Component
{
    use WithPagination;

    public $anio;
    public $dimensionId;
    public $departamentoId;
    public $expandedRow = null;

    public $anios = [];
    public $dimensiones = [];
    public $departamentos = [];
    public $departamentoIds = [];

    public function mount()
    {
        $this->anios = Poa::distinct()->orderBy('anio', 'desc')->pluck('anio')->toArray();
        $this->anio = count($this->anios) > 0 ? $this->anios[0] : date('Y');
        $this->dimensiones = Dimension::orderBy('nombre')->get();
        $this->cargarDepartamentosEmpleado();
    }

    public function cargarDepartamentosEmpleado()
    {
        $empleado = Auth::user()?->empleado;

        if (! $empleado) {
            $this->departamentos = [];
            $this->departamentoIds = [];
            $this->departamentoId = '';
            return;
        }

        $departamentos = $empleado->departamentos()
            ->orderBy('name')
            ->get(['departamentos.id', 'departamentos.name']);

        $this->departamentos = $departamentos->toArray();
        $this->departamentoIds = $departamentos->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        if ($this->departamentoId !== '' && ! in_array((int) $this->departamentoId, $this->departamentoIds, true)) {
            $this->departamentoId = '';
        }
    }

    public function updatingDepartamentoId()
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatedDepartamentoId($value)
    {
        if ($value !== '' && ! in_array((int) $value, $this->departamentoIds, true)) {
            $this->departamentoId = '';
        }
    }

    public function updatingAnio()
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function updatingDimensionId()
    {
        $this->expandedRow = null;
        $this->resetPage();
    }

    public function toggleExpand($actividadId)
    {
        $this->expandedRow = $this->expandedRow === $actividadId ? null : $actividadId;
    }

    public function getActividadesProperty()
    {
        return $this->buildActividadesQuery()
            ->orderBy('correlativo')
            ->paginate(20);
    }

    protected function buildActividadesQuery()
    {
        $query = Actividad::with([
            'departamento',
            'poa',
            'unidadEjecutora',
            'categoria',
            'tipo',
            'resultado.area.objetivo.dimension',
            'indicadores.planificacions',
            'tareas.presupuestos',
        ]);

        if ($this->anio) {
            $query->whereHas('poa', function ($q) {
                $q->where('anio', $this->anio);
            });
        }

        if ($this->dimensionId) {
            $query->whereHas('resultado.area.objetivo.dimension', function ($q) {
                $q->where('id', $this->dimensionId);
            });
        }

        if (empty($this->departamentoIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('idDeptartamento', $this->departamentoIds);
        }

        if ($this->departamentoId) {
            $query->where('idDeptartamento', $this->departamentoId);
        }

        return $query;
    }

    public function getActividadDetalleProperty()
    {
        if (! $this->expandedRow) {
            return null;
        }

        $query = Actividad::with([
            'departamento',
            'poa',
            'unidadEjecutora.institucion',
            'categoria',
            'tipo',
            'resultado.area.objetivo.dimension',
            'indicadores.planificacions.mes',
            'tareas.presupuestos.mes',
            'tareas.presupuestos.grupoGasto',
            'tareas.presupuestos.objetoGasto',
            'tareas.presupuestos.fuente',
            'empleados',
        ]);

        if (empty($this->departamentoIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('idDeptartamento', $this->departamentoIds);
        }

        if ($this->departamentoId) {
            $query->where('idDeptartamento', $this->departamentoId);
        }

        return $query->find($this->expandedRow);
    }

    public function exportarExcel()
    {
        $actividades = $this->buildActividadesQuery()
            ->orderBy('correlativo')
            ->get();

        $headers = [
            'Correlativo',
            'Actividad',
            'Departamento',
            'Unidad Ejecutora',
            'Ano',
            'Dimension',
            'Objetivo',
            'Area',
            'Resultado institucional',
            'Resultado de actividad',
            'Poblacion objetivo',
            'Medio de verificacion',
            'Categoria',
            'Tipo',
            'Indicadores',
            'Tareas',
            'Presupuesto total',
        ];

        $rows = $actividades->map(function ($actividad) {
            $presupuestoTotal = $actividad->tareas->sum(function ($tarea) {
                return $tarea->presupuestos->sum('total');
            });

            $indicadores = $actividad->indicadores->map(function ($indicador) {
                return trim(($indicador->nombre ?? 'N/A')
                    . ' | Meta: ' . number_format((float) ($indicador->cantidadPlanificada ?? 0), 2, '.', ',')
                    . ' | Ejecutada: ' . number_format((float) ($indicador->cantidadEjecutada ?? 0), 2, '.', ','));
            })->implode(' || ');

            $tareas = $actividad->tareas->map(function ($tarea) {
                $totalTarea = $tarea->presupuestos->sum('total');

                return trim(($tarea->correlativo ?? 'N/A')
                    . ' - ' . ($tarea->nombre ?? 'N/A')
                    . ' | Total: L ' . number_format((float) $totalTarea, 2, '.', ','));
            })->implode(' || ');

            return [
                $actividad->correlativo ?? '',
                $actividad->nombre ?? '',
                $actividad->departamento->name ?? '',
                $actividad->unidadEjecutora->name ?? '',
                (string) ($actividad->poa->anio ?? ''),
                $actividad->resultado->area->objetivo->dimension->nombre ?? '',
                $actividad->resultado->area->objetivo->nombre ?? '',
                $actividad->resultado->area->nombre ?? '',
                $actividad->resultado->nombre ?? '',
                $actividad->resultadoActividad ?? '',
                $actividad->poblacion_objetivo ?? '',
                $actividad->medio_verificacion ?? '',
                $actividad->categoria->categoria ?? '',
                $actividad->tipo->tipo ?? '',
                $indicadores,
                $tareas,
                number_format((float) $presupuestoTotal, 2, '.', ','),
            ];
        })->all();

        $tempFile = $this->createXlsxFile($headers, $rows);

        return response()->download(
            $tempFile,
            'consolidado-planificacion-' . now()->format('Ymd_His') . '.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    protected function createXlsxFile(array $headers, array $rows): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'planificacion_xlsx_');
        $zip = new ZipArchive();

        if ($tempFile === false || $zip->open($tempFile, ZipArchive::OVERWRITE) !== true) {
            abort(500, 'No se pudo generar el archivo de Excel.');
        }

        $sheetRows = array_merge([$headers], $rows);

        $zip->addFromString('[Content_Types].xml', $this->getXlsxContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->getXlsxRootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->getXlsxWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->getXlsxWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->getXlsxStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildXlsxSheetXml($sheetRows));
        $zip->close();

        return $tempFile;
    }

    protected function buildXlsxSheetXml(array $rows): string
    {
        $xmlRows = [];

        foreach ($rows as $rowIndex => $row) {
            $cells = [];

            foreach ($row as $columnIndex => $value) {
                $cellReference = $this->xlsxColumnName($columnIndex + 1) . ($rowIndex + 1);
                $style = $rowIndex === 0 ? ' s="1"' : '';
                $escapedValue = $this->escapeXml((string) $value);

                $cells[] = '<c r="' . $cellReference . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">' . $escapedValue . '</t></is></c>';
            }

            $xmlRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . '</worksheet>';
    }

    protected function xlsxColumnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    protected function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    protected function getXlsxContentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    protected function getXlsxRootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    protected function getXlsxWorkbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Planificacion" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    protected function getXlsxWorkbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    protected function getXlsxStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="2">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    public function render()
    {
        return view('livewire.consolidado.planificacion-consolidado', [
            'actividades' => $this->actividades,
            'actividadDetalle' => $this->actividadDetalle,
            'departamentos' => $this->departamentos,
            'dimensiones' => $this->dimensiones,
            'anios' => $this->anios,
        ]);
    }
}
