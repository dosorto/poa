<?php

namespace App\Services;

use App\Models\Actas\ActaEntrega;
use App\Models\Actas\DetalleActaEntrega;
use App\Models\Actas\TipoActaEntrega;
use App\Models\EjecucionPresupuestaria\DetalleEjecucionPresupuestaria;
use Illuminate\Support\Facades\DB;

class ActaIntermediaService
{
    public function crearPendientes(?int $usuarioId = null): void
    {
        $tipoId = TipoActaEntrega::whereRaw('LOWER(tipo) = ?', ['intermedia'])->value('id');

        if (! $tipoId) {
            return;
        }

        $detalles = DetalleEjecucionPresupuestaria::with('detalleRequisicion')
            ->whereHas('detalleRequisicion')
            ->get()
            ->groupBy(fn ($detalle) => $detalle->detalleRequisicion->idRequisicion);

        foreach ($detalles as $requisicionId => $detallesEjecucion) {
            $existe = ActaEntrega::where('idRequisicion', $requisicionId)
                ->where('idTipoActaEntrega', $tipoId)
                ->exists();

            if ($existe) {
                continue;
            }

            DB::transaction(function () use ($requisicionId, $detallesEjecucion, $tipoId, $usuarioId) {
                $acta = ActaEntrega::create([
                    'correlativo' => $this->siguienteCorrelativo(),
                    'fecha_extendida' => now(),
                    'idTipoActaEntrega' => $tipoId,
                    'idRequisicion' => $requisicionId,
                    'idEjecucionPresupuestaria' => $detallesEjecucion->first()->idEjecucion,
                    'created_by' => $usuarioId,
                ]);

                foreach ($detallesEjecucion as $detalle) {
                    DetalleActaEntrega::create([
                        'log_cant_ejecutada' => $detalle->cant_ejecutada,
                        'log_monto_unitario_ejecutado' => $detalle->monto_unitario_ejecutado,
                        'log_fechaEjecucion' => $detalle->fechaEjecucion,
                        'idActaEntrega' => $acta->id,
                        'idRequisicion' => $requisicionId,
                        'idDetalleRequisicion' => $detalle->idDetalleRequisicion,
                        'idEjecucionPresupuestaria' => $detalle->idEjecucion,
                        'idDetalleEjecucionPresupuestaria' => $detalle->id,
                        'created_by' => $usuarioId,
                    ]);
                }
            });
        }
    }

    private function siguienteCorrelativo(): string
    {
        $numero = ((int) ActaEntrega::withTrashed()->max('id')) + 1;

        return 'ACT-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT) . '-' . now()->format('Y');
    }
}
