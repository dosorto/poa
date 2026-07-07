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
            'resultado_actividad' => $this->getResultadoActividadConCorrelativo($actividad),
            'poblacion_objetivo' => $actividad->poblacion_objetivo ?? '',
            'medio_verificacion' => $actividad->medio_verificacion ?? '',
            'categoria' => $actividad->categoria->categoria ?? '',
            'tipo_actividad' => $actividad->tipo->tipo ?? '',
            'encargados' => $actividad->empleados->map(function (Empleado $empleado) {
                return trim(($empleado->nombre ?? '') . ' ' . ($empleado->apellido ?? ''))
                    . ' (#' . ($empleado->num_empleado ?? 'N/A') . ')';
            })->implode('; '),
            'indicadores' => $actividad->indicadores
                ->map(fn (Indicador $indicador) => $indicador->nombre ?? 'N/A')
                ->implode('; '),
            'subido_spi' => $actividad->uploadedIntoSPI ? 'Sí' : 'No',
        ];
    }

    private function getResultadoActividadConCorrelativo(Actividad $actividad): string
    {
        $resultadoActividad = $actividad->resultadoActividad ?? '';

        if (! $actividad->correlativo) {
            return $resultadoActividad;
        }

        return $actividad->correlativo . ' - ' . $resultadoActividad;
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

    private function formatDecimal($valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }
}
