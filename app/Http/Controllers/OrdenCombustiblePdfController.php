<?php

namespace App\Http\Controllers;

use App\Models\Requisicion\DetalleRequisicion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class OrdenCombustiblePdfController extends Controller
{
    private function prepararDatos($detalleId): array
    {
        $detalleRequisicion = DetalleRequisicion::with([
            'presupuesto.tareaHistorico',
            'presupuesto.tarea',
            'requisicion.departamento',
            'requisicion.creador',
        ])->findOrFail($detalleId);

        $orden = \DB::table('orden_combustible')
            ->where('idDetalleRequisicion', $detalleId)
            ->orderByDesc('id')
            ->first();

        if (!$orden) {
            $orden = \DB::table('orden_combustible')
                ->where('idRecurso', $detalleRequisicion->idRecurso)
                ->where('idPoa', $detalleRequisicion->idPoa)
                ->where('created_by', optional($detalleRequisicion->requisicion)->created_by)
                ->orderByDesc('id')
                ->first();
        }

        if (!$orden) {
            abort(404, 'Orden de combustible no encontrada.');
        }

        $orden = (object) $orden;
        $orden->detalleRequisicion = $detalleRequisicion;
        $orden->tareas_historico = $detalleRequisicion->presupuesto->tareaHistorico ?? null;
        $orden->empleado = $orden->responsable
            ? \App\Models\Empleados\Empleado::find($orden->responsable)
            : null;

        return [
            'orden' => $orden,
            'userDescarga' => Auth::user(),
            'userSolicitante' => optional($detalleRequisicion->requisicion)->creador ?? null,
        ];
    }

    public function show($detalleId)
    {
        $pdf = Pdf::loadView('pdf.orden-combustible', $this->prepararDatos($detalleId));

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="orden-combustible-'.$detalleId.'.pdf"');
    }

    public function download($detalleId)
    {
        $pdf = Pdf::loadView('pdf.orden-combustible', $this->prepararDatos($detalleId));

        return $pdf->download('orden-combustible-' . $detalleId . '.pdf');
    }
}
