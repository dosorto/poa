<?php

namespace App\Observers;

use App\Models\Requisicion\Requisicion;
use App\Models\EjecucionPresupuestaria\EjecucionPresupuestaria;
use App\Models\EjecucionPresupuestaria\EjecucionPresupuestariaLog;
use App\Models\Actas\ActaEntrega;
use App\Models\Actas\DetalleActaEntrega;
use App\Models\Actas\TipoActaEntrega;
use App\Models\Requisicion\DetalleRequisicion;
use App\Models\EjecucionPresupuestaria\DetalleEjecucionPresupuestaria;
use App\Services\RequisicionCorreoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RequisicionObserver
{
    public function updated(Requisicion $requisicion)
    {
        // Verificar si cambió el estado
        if ($requisicion->isDirty('idEstado')) {
            $estadoAnterior = $requisicion->getOriginal('idEstado');
            $estadoNuevo = $requisicion->idEstado;
            
            $nombreEstadoNuevo = $requisicion->estado->estado ?? '';
            
            Log::info('Estado de requisición cambió', [
                'requisicion_id' => $requisicion->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'nombre_estado' => $nombreEstadoNuevo
            ]);

            // Si el estado es "En Proceso de Compra", crear ejecución presupuestaria
            if ($nombreEstadoNuevo === 'En Proceso de Compra') {
                $this->crearEjecucionPresupuestaria($requisicion);
            }
            
            // Si el estado cambia a "Cancelado" y existe ejecución, marcarla como cancelada
            if ($nombreEstadoNuevo === 'Rechazado' || $nombreEstadoNuevo === 'Cancelado') {
                $this->cancelarEjecucionPresupuestaria($requisicion);
            }
            
            // Si el estado es "Completado" o "Finalizado", marcar ejecución como finalizada
            if ($nombreEstadoNuevo === 'Completado' || $nombreEstadoNuevo === 'Finalizado') {
                $this->finalizarEjecucionPresupuestaria($requisicion);
            }
        }
    }

    protected function crearEjecucionPresupuestaria(Requisicion $requisicion)
    {
        // Verificar si ya existe una ejecución para esta requisición
        $ejecucionExistente = EjecucionPresupuestaria::where('idRequisicion', $requisicion->id)->first();
        
        if ($ejecucionExistente) {
            Log::info('Ya existe una ejecución presupuestaria para esta requisición', [
                'requisicion_id' => $requisicion->id,
                'ejecucion_id' => $ejecucionExistente->id
            ]);
            return;
        }

        try {
            // Crear ejecución presupuestaria con estado "Parcialmente ejecutado" (ID: 1)
            $ejecucion = EjecucionPresupuestaria::create([
                'observacion' => 'Sin observación',
                'fechaInicioEjecucion' => now(),
                'idRequisicion' => $requisicion->id,
                'idEstadoEjecucion' => 1, // Parcialmente ejecutado
                'created_by' => Auth::id(),
            ]);

            Log::info('Ejecución presupuestaria creada', [
                'requisicion_id' => $requisicion->id,
                'ejecucion_id' => $ejecucion->id
            ]);

            // Crear log
            try {
                $log = EjecucionPresupuestariaLog::create([
                    'observacion' => 'Log generado por el sistema',
                    'log' => 'Ejecución creada',
                    'idEjecucionPresupuestaria' => $ejecucion->id,
                    'created_by' => Auth::id(),
                ]);

                Log::info('Log de ejecución presupuestaria creado', [
                    'ejecucion_id' => $ejecucion->id,
                    'log_id' => $log->id
                ]);
            } catch (\Exception $e) {
                Log::error('Error al crear log de ejecución', [
                    'ejecucion_id' => $ejecucion->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al crear ejecución presupuestaria', [
                'requisicion_id' => $requisicion->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function cancelarEjecucionPresupuestaria(Requisicion $requisicion)
    {
        $ejecucion = EjecucionPresupuestaria::where('idRequisicion', $requisicion->id)->first();
        
        if (!$ejecucion) {
            return;
        }

        try {
            // Actualizar estado a "Ejecución cancelada" (ID: 3)
            $ejecucion->update([
                'idEstadoEjecucion' => 3,
                'fechaFinEjecucion' => now(),
                'observacion' => ($ejecucion->observacion ?? '') . ' | Ejecución cancelada por rechazo/cancelación de requisición',
                'updated_by' => Auth::id(),
            ]);

            // Crear log
            EjecucionPresupuestariaLog::create([
                'observacion' => 'Log generado por el sistema',
                'log' => 'Ejecución modificada - Estado cambiado a cancelada',
                'idEjecucionPresupuestaria' => $ejecucion->id,
                'created_by' => Auth::id(),
            ]);

            Log::info('Ejecución presupuestaria cancelada', [
                'requisicion_id' => $requisicion->id,
                'ejecucion_id' => $ejecucion->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cancelar ejecución presupuestaria', [
                'requisicion_id' => $requisicion->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function finalizarEjecucionPresupuestaria(Requisicion $requisicion)
    {
        $ejecucion = EjecucionPresupuestaria::where('idRequisicion', $requisicion->id)->first();
        
        if (!$ejecucion) {
            return;
        }

        try {
            // Actualizar estado a "Ejecución finalizada" (ID: 4)
            $ejecucion->update([
                'idEstadoEjecucion' => 4,
                'fechaFinEjecucion' => now(),
                'observacion' => ($ejecucion->observacion ?? '') . ' | Ejecución finalizada',
                'updated_by' => Auth::id(),
            ]);

            // Crear log
            EjecucionPresupuestariaLog::create([
                'observacion' => 'Log generado por el sistema',
                'log' => 'Ejecución modificada - Estado cambiado a finalizada',
                'idEjecucionPresupuestaria' => $ejecucion->id,
                'created_by' => Auth::id(),
            ]);

            Log::info('Ejecución presupuestaria finalizada', [
                'requisicion_id' => $requisicion->id,
                'ejecucion_id' => $ejecucion->id
            ]);

            // Crear Acta de Entrega si no existe y enviar correo con PDF
            try {
                $actaExistente = ActaEntrega::where('idRequisicion', $requisicion->id)->first();

                if (!$actaExistente) {
                    $tipoActaFinal = TipoActaEntrega::where('tipo', 'Final')->first();
                    if ($tipoActaFinal) {
                        $ultimaActa = ActaEntrega::latest('id')->first();
                        $numeroCorrelativo = $ultimaActa ? ($ultimaActa->id + 1) : 1;
                        $correlativo = 'ACTA-' . str_pad($numeroCorrelativo, 6, '0', STR_PAD_LEFT) . '-' . date('Y');

                        $actaEntrega = ActaEntrega::create([
                            'correlativo' => $correlativo,
                            'fecha_extendida' => now(),
                            'idTipoActaEntrega' => $tipoActaFinal->id,
                            'idRequisicion' => $requisicion->id,
                            'idEjecucionPresupuestaria' => $ejecucion->id,
                            'created_by' => Auth::id(),
                        ]);

                        // Crear detalles del acta basados en las ejecuciones
                        $detallesRequisicion = DetalleRequisicion::where('idRequisicion', $requisicion->id)->get();
                        foreach ($detallesRequisicion as $detalleRequisicion) {
                            $detallesEjecucion = DetalleEjecucionPresupuestaria::where('idDetalleRequisicion', $detalleRequisicion->id)
                                ->where('idEjecucion', $ejecucion->id)
                                ->get();

                            foreach ($detallesEjecucion as $detalleEjecucion) {
                                DetalleActaEntrega::create([
                                    'log_cant_ejecutada' => $detalleEjecucion->cant_ejecutada,
                                    'log_monto_unitario_ejecutado' => $detalleEjecucion->monto_unitario_ejecutado,
                                    'log_fechaEjecucion' => $detalleEjecucion->fechaEjecucion,
                                    'idActaEntrega' => $actaEntrega->id,
                                    'idRequisicion' => $requisicion->id,
                                    'idDetalleRequisicion' => $detalleRequisicion->id,
                                    'idEjecucionPresupuestaria' => $ejecucion->id,
                                    'idDetalleEjecucionPresupuestaria' => $detalleEjecucion->id,
                                    'created_by' => Auth::id(),
                                ]);
                            }
                        }

                        // No enviar acta por correo (configuración actual)
                        Log::info('Acta de entrega creada pero no enviada por correo (configuración).', [
                            'requisicion_id' => $requisicion->id,
                            'acta_id' => $actaEntrega->id,
                        ]);
                    }
                } else {
                    // Acta ya existe; no reenviamos por correo según configuración
                    Log::info('Acta de entrega ya existe; no se reenviará por correo (configuración).', [
                        'requisicion_id' => $requisicion->id,
                        'acta_id' => $actaExistente->id,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Error creando/enviando acta en observer', [
                    'requisicion_id' => $requisicion->id,
                    'error' => $e->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error al finalizar ejecución presupuestaria', [
                'requisicion_id' => $requisicion->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
