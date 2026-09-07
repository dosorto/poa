<?php

namespace App\Livewire\Requisicion;

use Livewire\Component;
use App\Models\Requisicion\Requisicion;
use App\Models\Requisicion\DetalleRequisicion;
use App\Models\EjecucionPresupuestaria\DetalleEjecucionPresupuestaria;
use App\Models\EjecucionPresupuestaria\EjecucionPresupuestaria;
use App\Models\EjecucionPresupuestaria\EjecucionPresupuestariaLog;
use App\Models\EjecucionPresupuestaria\EstadoEjecucionPresupuestaria;
use App\Models\Actas\ActaEntrega;
use App\Models\Actas\DetalleActaEntrega;
use App\Models\Actas\TipoActaEntrega;
use App\Services\RequisicionCorreoService;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class EntregaRecursos extends Component
{
    use WithFileUploads;

    public $requisicionId;
    public $requisicion; 
    public $detalleRequisicion = [];
    public $recursosParaEntregar = [];
    
    public $showEjecucionModal = false;
    public $recursoSeleccionado = [];
    public $detalleRecursoId;
    public $cantidadEjecutada;
    public $montoUnitarioEjecutado;
    public $factura;
    public $archivoFactura;
    public $rutaArchivoFacturaActual;
    public $observacionEjecucion;
    public $fechaEjecucion;

    public $showConfirmFinalizarModal = false;

    public $esSoloLectura = false;
    public $showObservacionEjecucionModal = false;
    public $observacionEjecucionPresupuestaria;
    public $showPdfModal = false;
    public $pdfUrl = '';
    public $pdfDownloadUrl = '';
    public $pdfTitle = '';

    public $successMessage = ''; // Success message
    public $errorMessage = ''; // Error message

    protected $rules = [
        'cantidadEjecutada' => 'required|numeric|min:0',
        'montoUnitarioEjecutado' => 'required|numeric|min:0',
        'factura' => 'nullable|string|max:255',
        'archivoFactura' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        'observacionEjecucion' => 'nullable|string|max:500',
        'fechaEjecucion' => 'required|date',
    ];

    protected $messages = [
        'cantidadEjecutada.required' => 'La cantidad ejecutada es obligatoria.',
        'cantidadEjecutada.numeric' => 'La cantidad debe ser un número.',
        'cantidadEjecutada.min' => 'La cantidad debe ser mayor o igual a 0.',
        'montoUnitarioEjecutado.required' => 'El monto unitario es obligatorio.',
        'montoUnitarioEjecutado.numeric' => 'El monto debe ser un número.',
        'montoUnitarioEjecutado.min' => 'El monto debe ser mayor o igual a 0.',
        'archivoFactura.file' => 'El archivo de factura debe ser un archivo válido.',
        'archivoFactura.mimes' => 'El archivo de factura debe ser PDF, JPG, JPEG o PNG.',
        'archivoFactura.max' => 'El archivo de factura no debe superar los 5 MB.',
        'fechaEjecucion.required' => 'La fecha de ejecución es obligatoria.',
        'fechaEjecucion.date' => 'La fecha debe ser válida.',
    ];

    public function mount($requisicionId)
    {
        $this->requisicionId = $requisicionId;
        $this->requisicion = Requisicion::with([
            'departamento',
            'estado',
            'creador.empleado',
            'detalleRequisiciones.presupuesto',
            'detalleRequisiciones.ejecuciones.createdBy',
        ])->findOrFail($this->requisicionId);
        
        $empleado = $this->requisicion->creador && $this->requisicion->creador->empleado ? $this->requisicion->creador->empleado : null;
        $empleadoNombreCompleto = $empleado ? trim($empleado->nombres . ' ' . $empleado->apellidos) : ($this->requisicion->creador->name ?? '-');
        
        // Obtener la ejecución presupuestaria de esta requisición
        $ejecucionPresupuestaria = EjecucionPresupuestaria::where('idRequisicion', $this->requisicionId)->first();
        
        $this->detalleRequisicion = [
            'correlativo' => $this->requisicion->correlativo,
            'departamento' => $this->requisicion->departamento->name ?? '-',
            'descripcion' => $this->requisicion->descripcion ?? '-',
            //'observacion_requisicion' => $this->requisicion->observacion ?? '-',
            'observacion_ejecucion' => $ejecucionPresupuestaria->observacion ?? '-',
            'creador' => $empleadoNombreCompleto,
            'estado' => $this->requisicion->estado->estado ?? '-',
            'fecha_presentado' => $this->requisicion->fechaSolicactitud ?? '-',
            'fecha_requerido' => $this->requisicion->fechaRequerido ?? '-',
        ];
        
        $this->recursosParaEntregar = $this->requisicion->detalleRequisiciones->map(function($detalle) {
            $presupuesto = $detalle->presupuesto;
            
            // Obtener todas las ejecuciones de este detalle
            $ejecuciones = $detalle->ejecuciones->sortByDesc('created_at')->values();
            
            // Calcular totales
            $totalEjecutado = $ejecuciones->sum('cant_ejecutada');
            $montoTotalEjecutado = $ejecuciones->sum('monto_total_ejecutado');
            
            // Obtener la última ejecución para mostrar sus datos
            $ultimaEjecucion = $ejecuciones->first();
            
            // Manejar la fecha de ejecución de forma segura
            $fechaEjecucion = '-';
            if ($ultimaEjecucion && $ultimaEjecucion->fechaEjecucion) {
                try {
                    if ($ultimaEjecucion->fechaEjecucion instanceof \Carbon\Carbon) {
                        $fechaEjecucion = $ultimaEjecucion->fechaEjecucion->format('Y-m-d');
                    } else {
                        $fechaEjecucion = \Carbon\Carbon::parse($ultimaEjecucion->fechaEjecucion)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al formatear fecha de ejecución', [
                        'detalle_id' => $detalle->id,
                        'error' => $e->getMessage()
                    ]);
                    $fechaEjecucion = '-';
                }
            }
            
            return [
                'id' => $detalle->id,
                'recurso' => $presupuesto->recurso ?? '-',
                'detalle_tecnico' => $presupuesto->detalle_tecnico ?? '-',
                'observacion' => $ultimaEjecucion->observacion ?? '-',
                'factura' => $ultimaEjecucion->referenciaActaEntrega ?? '-',
                'ruta_archivo_factura' => $ultimaEjecucion->ruta_archivo_factura ?? null,
                'fecha_ejecucion' => $fechaEjecucion,
                'cantidad' => $detalle->cantidad ?? '-',
                'monto_requerido' => ($detalle->cantidad ?? 0) * ($presupuesto->costounitario ?? 0),
                'entregado' => $totalEjecutado,
                'monto_ejecutado' => $montoTotalEjecutado,
                'historial_ejecuciones' => $ejecuciones->map(function ($ejecucion) {
                    return [
                        'id' => $ejecucion->id,
                        'observacion' => $ejecucion->observacion ?: '-',
                        'factura' => $ejecucion->referenciaActaEntrega ?: '-',
                        'ruta_archivo_factura' => $ejecucion->ruta_archivo_factura,
                        'cantidad' => (float) $ejecucion->cant_ejecutada,
                        'monto_unitario' => (float) $ejecucion->monto_unitario_ejecutado,
                        'monto_total' => (float) $ejecucion->monto_total_ejecutado,
                        'fecha_ejecucion' => $ejecucion->fechaEjecucion?->format('Y-m-d') ?? '-',
                        'registrado_por' => $ejecucion->createdBy?->name ?? '-',
                        'registrado_el' => $ejecucion->created_at?->format('Y-m-d H:i') ?? '-',
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    public function abrirModalEjecucion($detalleRecursoId)
    {
        $this->resetValidation();
        $this->detalleRecursoId = $detalleRecursoId;
        
        // Verificar si es solo lectura
        $this->esSoloLectura = ($this->requisicion->estado->estado ?? '') === 'Finalizado';
        
        $recurso = collect($this->recursosParaEntregar)->firstWhere('id', $detalleRecursoId);
        
        if ($recurso) {
            $this->recursoSeleccionado = $recurso;
            
            // Obtener la última ejecución para prellenar el formulario
            $ultimaEjecucion = DetalleEjecucionPresupuestaria::where('idDetalleRequisicion', $detalleRecursoId)
                ->orderBy('created_at', 'desc')
                ->first();

            $cantidadRequerida = (float) ($recurso['cantidad'] ?? 0);
            $cantidadYaEjecutada = (float) ($recurso['entregado'] ?? 0);
            $cantidadPendiente = max($cantidadRequerida - $cantidadYaEjecutada, 0);
            $montoUnitarioRequerido = $cantidadRequerida > 0
                ? round(($recurso['monto_requerido'] ?? 0) / $cantidadRequerida, 2)
                : 0;
            
            if ($ultimaEjecucion) {
                // Cada guardado registra una nueva ejecución, por eso se propone lo pendiente.
                $this->cantidadEjecutada = $cantidadPendiente;
                $this->montoUnitarioEjecutado = $ultimaEjecucion->monto_unitario_ejecutado ?? $montoUnitarioRequerido;
                $this->factura = '';
                $this->archivoFactura = null;
                $this->rutaArchivoFacturaActual = $ultimaEjecucion->ruta_archivo_factura ?? null;
                $this->observacionEjecucion = '';
                $this->fechaEjecucion = now()->format('Y-m-d');
            } else {
                // Si no hay ejecuciones previas, usar valores por defecto
                $this->cantidadEjecutada = $cantidadPendiente;
                $this->montoUnitarioEjecutado = $montoUnitarioRequerido;
                $this->factura = '';
                $this->archivoFactura = null;
                $this->rutaArchivoFacturaActual = null;
                $this->observacionEjecucion = '';
                $this->fechaEjecucion = now()->format('Y-m-d');
            }
            
            $this->showEjecucionModal = true;
        }
    }

    public function actualizarEjecucion()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $detalleRequisicion = DetalleRequisicion::findOrFail($this->detalleRecursoId);
            
            // Validar que no se ejecute más de lo requerido
            $totalEjecutadoAnterior = DetalleEjecucionPresupuestaria::where('idDetalleRequisicion', $this->detalleRecursoId)
                ->sum('cant_ejecutada');
            
            $totalNuevo = $totalEjecutadoAnterior + $this->cantidadEjecutada;
            
            if ($totalNuevo > $detalleRequisicion->cantidad) {
                DB::rollBack();
                $this->addError('cantidadEjecutada', 'La cantidad total ejecutada (' . number_format($totalNuevo, 2) . ') no puede ser mayor a la cantidad requerida (' . number_format($detalleRequisicion->cantidad, 2) . '). Ya se han ejecutado: ' . number_format($totalEjecutadoAnterior, 2));
                return;
            }

            if (!$detalleRequisicion->idPresupuesto) {
                throw new \Exception('El detalle de requisición no tiene un presupuesto asociado.');
            }
            $requisicion = Requisicion::findOrFail($this->requisicionId);

            $ejecucionCreada = false;
            $ejecucionPresupuestaria = EjecucionPresupuestaria::where('idRequisicion', $requisicion->id)->first();
            
            if (!$ejecucionPresupuestaria) {
                $ejecucionPresupuestaria = EjecucionPresupuestaria::create([
                    'observacion' => 'Ejecución iniciada al registrar entrega de recursos',
                    'fechaInicioEjecucion' => now(),
                    'idRequisicion' => $requisicion->id,
                    'idEstadoEjecucion' => 1, // Parcialmente ejecutado
                    'created_by' => Auth::id(),
                ]);
                $ejecucionCreada = true;
            }

            \Log::info('Ejecución presupuestaria encontrada/creada:', [
                'ejecucion_id' => $ejecucionPresupuestaria->id,
                'requisicion_id' => $requisicion->id,
                'creada' => $ejecucionCreada
            ]);

            // Si se creó la ejecución, crear el log inicial
            if ($ejecucionCreada) {
                try {
                    \App\Models\EjecucionPresupuestaria\EjecucionPresupuestariaLog::create([
                        'observacion' => 'Log generado por el sistema',
                        'log' => 'Ejecución creada al registrar primera entrega de recursos',
                        'idEjecucionPresupuestaria' => $ejecucionPresupuestaria->id,
                        'created_by' => Auth::id(),
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Error al crear log inicial de ejecución:', [
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Crear el detalle de ejecución presupuestaria
            $montoTotalEjecutado = $this->cantidadEjecutada * $this->montoUnitarioEjecutado;
            $rutaArchivoFactura = $this->archivoFactura
                ? $this->archivoFactura->store('facturas/ejecuciones', 'public')
                : null;
            
            $detalleEjecucion = DetalleEjecucionPresupuestaria::create([
                'observacion' => $this->observacionEjecucion,
                'referenciaActaEntrega' => $this->factura,
                'ruta_archivo_factura' => $rutaArchivoFactura,
                'cant_ejecutada' => $this->cantidadEjecutada,
                'monto_unitario_ejecutado' => $this->montoUnitarioEjecutado,
                'monto_total_ejecutado' => $montoTotalEjecutado,
                'fechaEjecucion' => $this->fechaEjecucion,
                'idPresupuesto' => $detalleRequisicion->idPresupuesto,
                'idDetalleRequisicion' => $this->detalleRecursoId,
                'idEjecucion' => $ejecucionPresupuestaria->id,
                'created_by' => Auth::id(),
            ]);

            \Log::info('Detalle ejecución creado:', ['id' => $detalleEjecucion->id]);

            // Actualizar el detalle de requisición con la última información
            $detalleRequisicion->update([
                'entregado' => $totalNuevo,
            ]);

            // Actualizar el costo de ejecución en el presupuesto (último monto unitario)
            if ($detalleRequisicion->presupuesto) {
                $detalleRequisicion->presupuesto->update([
                    'costoejecucion' => $this->montoUnitarioEjecutado,
                ]);
            }

            $this->verificarEjecucionCompleta($requisicion, $ejecucionPresupuestaria);

            DB::commit();

            // Recargar los datos
            $this->mount($this->requisicionId);
            
            $this->cerrarModal();
            
            $this->successMessage = 'Ejecución registrada correctamente.'; // Set success message
            
            // Dispatch evento para refrescar la página si es necesario
            $this->dispatch('ejecucion-guardada');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar ejecución:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->errorMessage = 'Error al registrar la ejecución: ' . $e->getMessage(); // Set error message
        }
    }

    public function abrirModalObservacionEjecucion()
    {
        \Log::info('Abriendo modal de observación'); // Temporal para debug
        
        // Obtener la ejecución presupuestaria
        $ejecucionPresupuestaria = EjecucionPresupuestaria::where('idRequisicion', $this->requisicionId)->first();
        
        \Log::info('Ejecución encontrada:', ['existe' => $ejecucionPresupuestaria !== null]); // Temporal
        
        if ($ejecucionPresupuestaria) {
            $this->observacionEjecucionPresupuestaria = $ejecucionPresupuestaria->observacion;
            $this->showObservacionEjecucionModal = true;
            \Log::info('Modal abierto', ['show' => $this->showObservacionEjecucionModal]); // Temporal
        } else {
            session()->flash('error', 'No se encontró la ejecución presupuestaria.');
        }
    }

    public function guardarObservacionEjecucion()
    {
        $this->validate([
            'observacionEjecucionPresupuestaria' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $ejecucionPresupuestaria = EjecucionPresupuestaria::where('idRequisicion', $this->requisicionId)->first();
            
            if (!$ejecucionPresupuestaria) {
                throw new \Exception('No se encontró la ejecución presupuestaria.');
            }

            // Actualizar la observación
            $ejecucionPresupuestaria->update([
                'observacion' => $this->observacionEjecucionPresupuestaria,
                'updated_by' => Auth::id(),
            ]);

            // Crear log
            EjecucionPresupuestariaLog::create([
                'observacion' => 'Sin observación',
                'log' => 'Observación de ejecución modificada',
                'idEjecucionPresupuestaria' => $ejecucionPresupuestaria->id,
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            // Recargar datos
            $this->mount($this->requisicionId);

            $this->cerrarModalObservacionEjecucion();

            $this->successMessage = 'Observación guardada correctamente.'; // Set success message

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al guardar observación:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->errorMessage = 'Error al guardar la observación: ' . $e->getMessage(); // Set error message
        }
    }

    public function cerrarModalObservacionEjecucion()
    {
        $this->showObservacionEjecucionModal = false;
        $this->resetValidation();
        $this->reset('observacionEjecucionPresupuestaria');
    }

    protected function verificarEjecucionCompleta($requisicion, $ejecucionPresupuestaria)
    {
        // Obtener todos los detalles de la requisición
        $detalles = $requisicion->detalleRequisiciones;
        
        $todosEjecutados = true;
        foreach ($detalles as $detalle) {
            $cantidadEjecutada = DetalleEjecucionPresupuestaria::where('idDetalleRequisicion', $detalle->id)
                ->sum('cant_ejecutada');

            if ($cantidadEjecutada < $detalle->cantidad) {
                $todosEjecutados = false;
                break;
            }
        }

        if ($todosEjecutados) {
            $ejecucionPresupuestaria->update([
                'idEstadoEjecucion' => 2, // Ejecutado
                'fechaFinEjecucion' => now(),
                // NO modificar la observación automáticamente
                'updated_by' => Auth::id(),
            ]);

            // Crear log
            try {
                \App\Models\EjecucionPresupuestaria\EjecucionPresupuestariaLog::create([
                    'observacion' => 'Sin observación',
                    'log' => 'Ejecución modificada - Estado cambiado a Ejecutado',
                    'idEjecucionPresupuestaria' => $ejecucionPresupuestaria->id,
                    'created_by' => Auth::id(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Error al crear log de ejecución completada:', [
                    'error' => $e->getMessage(),
                    'ejecucion_id' => $ejecucionPresupuestaria->id
                ]);
            }

            \Log::info('Ejecución marcada como completada', [
                'ejecucion_id' => $ejecucionPresupuestaria->id
            ]);
        }
    }

    public function cerrarModal()
    {
        $this->showEjecucionModal = false;
        $this->resetValidation();
        $this->reset([
            'recursoSeleccionado',
            'detalleRecursoId',
            'cantidadEjecutada',
            'montoUnitarioEjecutado',
            'factura',
            'archivoFactura',
            'rutaArchivoFacturaActual',
            'observacionEjecucion',
            'fechaEjecucion'
        ]);
    }

    public function abrirModalFinalizar()
    {
        $this->showConfirmFinalizarModal = true;
    }

    public function cerrarModalFinalizar()
    {
        $this->showConfirmFinalizarModal = false;
    }

    public function confirmarFinalizarRequisicion()
    {
        $this->finalizarRequisicion();
        $this->showConfirmFinalizarModal = false;
    }

    public function finalizarRequisicion()
    {
        $actaEntrega = null;

        try {
            DB::beginTransaction();

            $requisicion = Requisicion::findOrFail($this->requisicionId);

            // Verificar que el estado actual no sea "Finalizado"
            if ($requisicion->estado->estado === 'Finalizado') {
                DB::rollBack();
                session()->flash('error', 'Esta requisición ya está finalizada.');
                return;
            }

            // Buscar el estado "Finalizado"
            $estadoFinalizado = \App\Models\Requisicion\EstadoRequisicion::where('estado', 'Finalizado')->first();
            
            if (!$estadoFinalizado) {
                throw new \Exception('No se encontró el estado "Finalizado"');
            }

            // Actualizar la requisición
            $requisicion->update([
                'idEstado' => $estadoFinalizado->id,
            ]);

            // Crear log de cambio de estado
            \App\Models\Requisicion\EstadoRequisicionLog::create([
                'observacion' => 'Requisición finalizada desde módulo de entrega de recursos',
                'log' => 'Cambio a Finalizado',
                'idRequisicion' => $requisicion->id,
                'created_by' => Auth::id(),
            ]);

            // Actualizar la ejecución presupuestaria a "Ejecución finalizada"
            $ejecucionPresupuestaria = EjecucionPresupuestaria::where('idRequisicion', $requisicion->id)->first();
            
            if ($ejecucionPresupuestaria) {
                $ejecucionPresupuestaria->update([
                    'idEstadoEjecucion' => 4, // Ejecución finalizada
                    'fechaFinEjecucion' => now(),
                    'updated_by' => Auth::id(),
                ]);

                // Crear log de ejecución
                EjecucionPresupuestariaLog::create([
                    'observacion' => 'Log generado por el sistema',
                    'log' => 'Requisición finalizada manualmente',
                    'idEjecucionPresupuestaria' => $ejecucionPresupuestaria->id,
                    'created_by' => Auth::id(),
                ]);

                // Crear Acta de Entrega
                $actaEntrega = $this->crearActaEntrega($requisicion, $ejecucionPresupuestaria);
            }

            DB::commit();

            if ($actaEntrega) {
                \Log::info('Acta de entrega creada pero envío por correo deshabilitado.', [
                    'requisicion_id' => $requisicion->id,
                    'acta_id' => $actaEntrega->id,
                ]);
            }

            // Enviar también el PDF de la requisición con el estado actualizado
            try {
                app(RequisicionCorreoService::class)->enviarEstadoActualizado($requisicion, 'Finalizado');
            } catch (\Throwable $e) {
                \Log::error('Error al enviar correo de requisición finalizada:', [
                    'requisicion_id' => $requisicion->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Recargar los datos
            $this->mount($this->requisicionId);

            $this->successMessage = 'Requisición finalizada correctamente. Se ha generado el acta de entrega.'; // Set success message

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al finalizar requisición:', [
                'error' => $e->getMessage(),
                'requisicion_id' => $this->requisicionId,
                'trace' => $e->getTraceAsString()
            ]);
            $this->errorMessage = 'Error al finalizar la requisición: ' . $e->getMessage(); // Set error message
        }
    }

    protected function crearActaEntrega($requisicion, $ejecucionPresupuestaria)
    {
        try {
            // Verificar si ya existe un acta final. Una acta intermedia no debe bloquear la final.
            $tipoActaFinal = TipoActaEntrega::where('tipo', 'Final')->first();

            if (!$tipoActaFinal) {
                throw new \Exception('No se encontró el tipo de acta "Final"');
            }

            $actaExistente = ActaEntrega::where('idRequisicion', $requisicion->id)
                ->where('idTipoActaEntrega', $tipoActaFinal->id)
                ->latest('id')
                ->first();

            if ($actaExistente) {
                \Log::info('Ya existe un acta final para esta requisición', [
                    'requisicion_id' => $requisicion->id,
                    'acta_id' => $actaExistente->id
                ]);
                return $actaExistente;
            }

            // Generar correlativo para el acta
            $ultimaActa = ActaEntrega::latest('id')->first();
            $numeroCorrelativo = $ultimaActa ? ($ultimaActa->id + 1) : 1;
            $correlativo = 'ACTA-' . str_pad($numeroCorrelativo, 6, '0', STR_PAD_LEFT) . '-' . date('Y');

            // Crear el acta de entrega
            $actaEntrega = ActaEntrega::create([
                'correlativo' => $correlativo,
                'fecha_extendida' => now(),
                'idTipoActaEntrega' => $tipoActaFinal->id,
                'idRequisicion' => $requisicion->id,
                'idEjecucionPresupuestaria' => $ejecucionPresupuestaria->id,
                'created_by' => Auth::id(),
            ]);

            \Log::info('Acta de entrega creada', [
                'acta_id' => $actaEntrega->id,
                'correlativo' => $correlativo
            ]);

            $detallesRequisicion = DetalleRequisicion::where('idRequisicion', $requisicion->id)->get();
            $totalDetallesCreados = 0;
            
            foreach ($detallesRequisicion as $detalleRequisicion) {
                $detallesEjecucion = DetalleEjecucionPresupuestaria::where('idDetalleRequisicion', $detalleRequisicion->id)
                    ->where('idEjecucion', $ejecucionPresupuestaria->id)
                    ->get();
                
                foreach ($detallesEjecucion as $detalleEjecucion) {
                    DetalleActaEntrega::create([
                        'log_cant_ejecutada' => $detalleEjecucion->cant_ejecutada,
                        'log_monto_unitario_ejecutado' => $detalleEjecucion->monto_unitario_ejecutado,
                        'log_fechaEjecucion' => $detalleEjecucion->fechaEjecucion,
                        'idActaEntrega' => $actaEntrega->id,
                        'idRequisicion' => $requisicion->id,
                        'idDetalleRequisicion' => $detalleRequisicion->id,
                        'idEjecucionPresupuestaria' => $ejecucionPresupuestaria->id,
                        'idDetalleEjecucionPresupuestaria' => $detalleEjecucion->id,
                        'created_by' => Auth::id(),
                    ]);

                    $totalDetallesCreados++;
                }
            }

            Log::info('Detalles de acta creados', [
                'acta_id' => $actaEntrega->id,
                'total_detalles' => $totalDetallesCreados,
            ]);

            return $actaEntrega;

        } catch (\Exception $e) {
            Log::error('Error al crear acta de entrega final.', [
                'requisicion_id' => $requisicion->id ?? null,
                'ejecucion_presupuestaria_id' => $ejecucionPresupuestaria->id ?? null,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function abrirPdfModal($url, $downloadUrl, $titulo = 'Vista previa PDF')
    {
        $this->pdfUrl = $url;
        $this->pdfDownloadUrl = $downloadUrl;
        $this->pdfTitle = $titulo;
        $this->showPdfModal = true;
    }

    public function abrirActaIntermediaEjecucion(int $detalleEjecucionId): void
    {
        $detalleEjecucion = DetalleEjecucionPresupuestaria::where('id', $detalleEjecucionId)
            ->whereHas('detalleRequisicion', fn ($query) => $query->where('idRequisicion', $this->requisicionId))
            ->firstOrFail();

        $query = '?detalle_ejecucion_id=' . $detalleEjecucion->id;

        $this->abrirPdfModal(
            '/acta-entrega-intermedia/' . $this->requisicionId . '/descargar' . $query,
            '/acta-entrega-intermedia/' . $this->requisicionId . '/descargar/download' . $query,
            'Acta Intermedia - Ejecución #' . $detalleEjecucion->id
        );
    }

    public function cerrarPdfModal()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
        $this->pdfDownloadUrl = '';
        $this->pdfTitle = '';
    }


    public function render()
    {
        return view('livewire.seguimiento.Requisicion.entrega-recursos', [
            'detalleRequisicion' => $this->detalleRequisicion,
            'recursosParaEntregar' => $this->recursosParaEntregar,
        ]);
    }
}
