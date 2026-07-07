<?php

namespace App\Exports;

use App\Models\Actividad\Actividad;
use App\Models\Actividad\Indicador;
use App\Models\Empleados\Empleado;
use App\Models\Presupuestos\Presupuesto;
use App\Models\Tareas\Tarea;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ConsolidadoUnidadEjecutoraExport
{
    public function __construct(private readonly EloquentCollection $actividades)
    {
    }

    public function rows(): array
    {
        $rows = [];

        foreach ($this->actividades as $actividad) {
            $baseRow = $this->buildActividadBaseRow($actividad);

            if ($actividad->tareas->isEmpty()) {
                $rows[] = array_merge($baseRow, $this->blankTareaRow(), $this->blankPresupuestoRow());
                continue;
            }

            foreach ($actividad->tareas as $tarea) {
                $tareaRow = $this->buildTareaRow($tarea);

                if ($tarea->presupuestos->isEmpty()) {
                    $rows[] = array_merge($baseRow, $tareaRow, $this->blankPresupuestoRow());
                    continue;
                }

                foreach ($tarea->presupuestos as $presupuesto) {
                    $rows[] = array_merge(
                        $baseRow,
                        $tareaRow,
                        $this->buildPresupuestoRow($presupuesto)
                    );
                }
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Año',
            'Unidad Ejecutora',
            'Departamento',
            'Estado',
            'Correlativo Actividad',
            'Actividad',
            'Dimensión',
            'Objetivo',
            'Área',
            'Resultado Institucional',
            'Resultado de Actividad',
            'Población Objetivo',
            'Medio de Verificación',
            'Categoría',
            'Tipo de Actividad',
            'Encargados',
            'Indicadores',
            'Subido al SPI',
            'Correlativo Tarea',
            'Tarea',
            'Descripción Tarea',
            'Estado Tarea',
            'Recurso',
            'Detalle Técnico',
            'Cantidad',
            'Costo Unitario',
            'Total Presupuestado',
            'Objeto de Gasto',
            'Unidad de Medida',
            'Fuente de Financiamiento',
            'Mes de Ejecución',
        ];
    }

    private function buildActividadBaseRow(Actividad $actividad): array
    {
        return [
            'anio' => $actividad->poa->anio ?? '',
            'unidad_ejecutora' => $actividad->unidadEjecutora->name ?? '',
            'departamento' => $actividad->departamento->name ?? '',
            'estado' => $actividad->estado ?? '',
            'correlativo_actividad' => $actividad->correlativo ?? '',
            'actividad' => $actividad->nombre ?? '',
            'dimension' => $actividad->resultado->area->objetivo->dimension->nombre ?? '',
            'objetivo' => $actividad->resultado->area->objetivo->nombre ?? '',
            'area' => $actividad->resultado->area->nombre ?? '',
            'resultado_institucional' => $actividad->resultado->nombre ?? '',
            'resultado_actividad' => $actividad->resultadoActividad ?? '',
            'poblacion_objetivo' => $actividad->poblacion_objetivo ?? '',
            'medio_verificacion' => $actividad->medio_verificacion ?? '',
            'categoria' => $actividad->categoria->categoria ?? '',
            'tipo_actividad' => $actividad->tipo->tipo ?? '',
            'encargados' => $actividad->empleados->map(function (Empleado $empleado) {
                return trim(($empleado->nombre ?? '') . ' ' . ($empleado->apellido ?? ''))
                    . ' (#' . ($empleado->num_empleado ?? 'N/A') . ')';
            })->implode('; '),
            'indicadores' => $actividad->indicadores
                ->map(fn (Indicador $indicador) => $this->buildIndicadorTexto($indicador))
                ->implode("\n\n"),
            'subido_spi' => $actividad->uploadedIntoSPI ? 'Sí' : 'No',
        ];
    }

    private function buildTareaRow(Tarea $tarea): array
    {
        return [
            'correlativo_tarea' => $tarea->correlativo ?? '',
            'tarea' => $tarea->nombre ?? '',
            'descripcion_tarea' => $tarea->descripcion ?? '',
            'estado_tarea' => $tarea->estado ?? '',
        ];
    }

    private function buildPresupuestoRow(Presupuesto $presupuesto): array
    {
        $objeto = $presupuesto->objetoGasto
            ? trim(($presupuesto->objetoGasto->identificador ?? '') . ' - ' . ($presupuesto->objetoGasto->nombre ?? ''))
            : ($presupuesto->idobjeto ?? '');

        $unidad = $presupuesto->unidadMedida
            ? trim(($presupuesto->unidadMedida->id ?? '') . ' - ' . ($presupuesto->unidadMedida->nombre ?? ''))
            : ($presupuesto->idunidad ?? '');

        $fuente = $presupuesto->fuente
            ? trim(($presupuesto->fuente->identificador ?? '') . ' - ' . ($presupuesto->fuente->nombre ?? ''))
            : ($presupuesto->idfuente ?? '');

        return [
            'recurso' => $presupuesto->recurso ?? '',
            'detalle_tecnico' => $presupuesto->detalle_tecnico ?? '',
            'cantidad' => $this->formatDecimal($presupuesto->cantidad ?? 0),
            'costounitario' => $this->formatDecimal($presupuesto->costounitario ?? 0),
            'total_presupuestado' => $this->formatDecimal($presupuesto->total ?? 0),
            'objeto_gasto' => $objeto,
            'unidad_medida' => $unidad,
            'fuente_financiamiento' => $fuente,
            'mes_ejecucion' => $presupuesto->mes->mes ?? '',
        ];
    }

    private function blankTareaRow(): array
    {
        return [
            'correlativo_tarea' => '',
            'tarea' => '',
            'descripcion_tarea' => '',
            'estado_tarea' => '',
        ];
    }

    private function blankPresupuestoRow(): array
    {
        return [
            'recurso' => '',
            'detalle_tecnico' => '',
            'cantidad' => '',
            'costounitario' => '',
            'total_presupuestado' => '',
            'objeto_gasto' => '',
            'unidad_medida' => '',
            'fuente_financiamiento' => '',
            'mes_ejecucion' => '',
        ];
    }

    private function buildIndicadorTexto(Indicador $indicador): string
    {
        $lineas = [
            'Indicador: ' . ($indicador->nombre ?? 'N/A'),
            'Tipo: ' . ($indicador->isPorcentaje ? 'Porcentaje' : 'Cantidad'),
            'Descripción: ' . ($indicador->descripcion ?: 'N/A'),
            'Meta planificada: ' . $this->formatDecimal($indicador->cantidadPlanificada ?? 0),
            'Cantidad ejecutada: ' . $this->formatDecimal($indicador->cantidadEjecutada ?? 0),
        ];

        foreach ($this->getIndicadorTrimestres($indicador) as $trimestre) {
            if (($trimestre['planificado'] ?? 0) <= 0 && ($trimestre['ejecutado'] ?? 0) <= 0) {
                continue;
            }

            $lineas[] = $trimestre['nombre']
                . ': Planificado ' . $this->formatDecimal($trimestre['planificado'])
                . ' | Ejecutado ' . $this->formatDecimal($trimestre['ejecutado']);
        }

        return implode("\n", $lineas);
    }

    private function getIndicadorTrimestres(Indicador $indicador): array
    {
        $trimestres = [
            'T1' => ['meses' => [1, 2, 3], 'nombre' => 'Trimestre 1', 'planificado' => 0, 'ejecutado' => 0],
            'T2' => ['meses' => [4, 5, 6], 'nombre' => 'Trimestre 2', 'planificado' => 0, 'ejecutado' => 0],
            'T3' => ['meses' => [7, 8, 9], 'nombre' => 'Trimestre 3', 'planificado' => 0, 'ejecutado' => 0],
            'T4' => ['meses' => [10, 11, 12], 'nombre' => 'Trimestre 4', 'planificado' => 0, 'ejecutado' => 0],
        ];

        foreach ($indicador->planificacions as $planificacion) {
            $mesId = $planificacion->mes->id ?? null;

            foreach ($trimestres as &$trimestre) {
                if (! $mesId || ! in_array($mesId, $trimestre['meses'], true)) {
                    continue;
                }

                $trimestre['planificado'] += (float) ($planificacion->cantidad ?? 0);
                $trimestre['ejecutado'] += (float) $planificacion->seguimientos->sum('cantidad');
            }
        }

        return $trimestres;
    }

    private function formatDecimal($valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }
}
