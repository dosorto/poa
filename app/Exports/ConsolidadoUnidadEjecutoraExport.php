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
        return $this->actividades->map(function (Actividad $actividad) {
            return [
                'anio' => $actividad->poa->anio ?? '',
                'unidad_ejecutora' => $actividad->unidadEjecutora->name ?? '',
                'departamento' => $actividad->departamento->name ?? '',
                'estado' => $actividad->estado ?? '',
                'correlativo' => $actividad->correlativo ?? '',
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
                'indicadores' => $actividad->indicadores->map(fn (Indicador $indicador) => $this->buildIndicadorTexto($indicador))->implode("\n\n"),
                'tareas' => $actividad->tareas->map(fn (Tarea $tarea) => $this->buildTareaTexto($tarea))->implode("\n\n"),
                'total_presupuestado' => $this->formatDecimal($actividad->tareas->sum(fn (Tarea $tarea) => $tarea->presupuestos->sum('total'))),
                'subido_spi' => $actividad->uploadedIntoSPI ? 'Sí' : 'No',
            ];
        })->values()->all();
    }

    public function headings(): array
    {
        return [
            'Año',
            'Unidad Ejecutora',
            'Departamento',
            'Estado',
            'Correlativo',
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
            'Tareas y Recursos',
            'Total Presupuestado',
            'Subido al SPI',
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

    private function buildTareaTexto(Tarea $tarea): string
    {
        $lineas = [
            'Tarea: ' . ($tarea->nombre ?? 'N/A'),
            'Correlativo: ' . ($tarea->correlativo ?? 'N/A'),
            'Descripción: ' . ($tarea->descripcion ?: 'N/A'),
        ];

        foreach ($tarea->presupuestos as $presupuesto) {
            $lineas[] = '- ' . $this->buildPresupuestoTexto($presupuesto);
        }

        $lineas[] = 'Total tarea: L ' . $this->formatDecimal($tarea->presupuestos->sum('total'));

        return implode("\n", $lineas);
    }

    private function buildPresupuestoTexto(Presupuesto $presupuesto): string
    {
        return implode(' | ', [
            'Recurso: ' . ($presupuesto->recurso ?? 'N/A'),
            'Detalle: ' . ($presupuesto->detalle_tecnico ?: 'N/A'),
            'Cantidad: ' . $this->formatDecimal($presupuesto->cantidad ?? 0),
            'Costo unitario: L ' . $this->formatDecimal($presupuesto->costounitario ?? 0),
            'Total: L ' . $this->formatDecimal($presupuesto->total ?? 0),
            'Grupo: ' . ($presupuesto->grupoGasto->nombre ?? 'N/A'),
            'Objeto: ' . ($presupuesto->objetoGasto->nombre ?? 'N/A'),
            'Fuente: ' . ($presupuesto->fuente->nombre ?? 'N/A'),
            'Mes: ' . ($presupuesto->mes->mes ?? 'N/A'),
        ]);
    }

    private function formatDecimal($valor): string
    {
        return number_format((float) $valor, 2, '.', ',');
    }
}
