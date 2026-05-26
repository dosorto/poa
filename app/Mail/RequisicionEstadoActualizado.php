<?php

namespace App\Mail;

use App\Models\Requisicion\Requisicion;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequisicionEstadoActualizado extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Requisicion $requisicion,
        public string $nuevoEstado
    ) {
        $this->requisicion->loadMissing([
            'departamento',
            'detalleRequisiciones.presupuesto.unidadMedida',
            'estado',
            'creador.empleado',
            'aprobadoPor.empleado',
            'logs',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Estado actualizado - ' . $this->requisicion->correlativo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.requisicion-estado-actualizado',
            with: [
                'requisicion' => $this->requisicion,
                'nuevoEstado' => $this->nuevoEstado,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Pdf::loadView('pdf.requisicion', $this->pdfData())->output(),
                'requisicion_' . $this->requisicion->correlativo . '_estado_actualizado.pdf'
            )->withMime('application/pdf'),
        ];
    }

    private function pdfData(): array
    {
        $logRecibido = $this->requisicion->logs->first(function ($log) {
            return stripos($log->log, 'recibido') !== false;
        });

        $recursos = [];
        $montoTotal = 0;

        foreach ($this->requisicion->detalleRequisiciones as $detalle) {
            $presupuesto = $detalle->presupuesto;
            $precioUnitario = $presupuesto->costounitario ?? 0;
            $total = ($detalle->cantidad ?? 0) * $precioUnitario;
            $montoTotal += $total;

            $recursos[] = [
                'cantidad' => $detalle->cantidad ?? '-',
                'unidad' => $presupuesto->unidadMedida->nombre ?? '-',
                'recurso' => $presupuesto->recurso ?? '-',
                'detalle_tecnico' => $presupuesto->detalle_tecnico ?? '-',
                'precio_unitario' => $precioUnitario,
                'total' => $total,
            ];
        }

        $fechaSolicitud = $this->requisicion->fechaSolicitud
            ? Carbon::parse($this->requisicion->fechaSolicitud)
            : null;
        $fechaRequerido = $this->requisicion->fechaRequerido
            ? Carbon::parse($this->requisicion->fechaRequerido)
            : null;

        return [
            'estado' => $this->nuevoEstado ?: ($this->requisicion->estado->estado ?? ''),
            'departamento' => $this->requisicion->departamento->name ?? '',
            'correlativo' => $this->requisicion->correlativo,
            'solicitante' => $this->requisicion->creador->name ?? '',
            'jefe_departamento' => $this->nombreUsuario($this->requisicion->creador),
            'proposito' => $this->requisicion->descripcion ?? '',
            'fecha_presentado' => $fechaSolicitud ? $fechaSolicitud->format('d/m/Y') : '',
            'fecha_presentado_dia' => $fechaSolicitud ? $fechaSolicitud->format('d') : '',
            'fecha_presentado_mes' => $fechaSolicitud ? $fechaSolicitud->format('m') : '',
            'fecha_presentado_anio' => $fechaSolicitud ? $fechaSolicitud->format('Y') : '',
            'fecha_requerido' => $fechaRequerido ? $fechaRequerido->format('d/m/Y') : '',
            'fecha_requerido_dia' => $fechaRequerido ? $fechaRequerido->format('d') : '',
            'fecha_requerido_mes' => $fechaRequerido ? $fechaRequerido->format('m') : '',
            'fecha_requerido_anio' => $fechaRequerido ? $fechaRequerido->format('Y') : '',
            'recibido_nombre' => $this->nombreUsuario($this->requisicion->aprobadoPor) ?: 'No recibido',
            'recibido_fecha' => $logRecibido && $logRecibido->created_at ? $logRecibido->created_at->format('d/m/Y') : 'No recibido',
            'recibido_hora' => $logRecibido && $logRecibido->created_at ? $logRecibido->created_at->format('H:i') : 'No recibido',
            'recursos' => $recursos,
            'monto_total' => $montoTotal,
            'observaciones' => $this->requisicion->observacion ?? '',
        ];
    }

    private function nombreUsuario($user): string
    {
        if (!$user) {
            return '';
        }

        $empleado = $user->empleado;

        if ($empleado) {
            return trim(($empleado->nombre ?? $empleado->nombres ?? '') . ' ' . ($empleado->apellido ?? $empleado->apellidos ?? ''));
        }

        return $user->name ?? '';
    }
}
