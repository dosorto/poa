<?php

namespace App\Mail;

use App\Models\Actas\ActaEntrega;
use App\Models\Requisicion\Requisicion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequisicionActaFinal extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Requisicion $requisicion,
        public ActaEntrega $actaEntrega
    ) {
        $this->requisicion->loadMissing([
            'departamento',
            'estado',
            'creador.empleado',
            'detalleRequisiciones.presupuesto.unidadMedida',
        ]);

        $this->actaEntrega->loadMissing([
            'tipoActaEntrega',
            'ejecucionPresupuestaria',
            'detalles.detalleRequisicion.presupuesto.unidadMedida',
            'detalles.detalleEjecucionPresupuestaria',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Acta final de entrega - ' . $this->requisicion->correlativo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.requisicion-acta-final',
            with: [
                'requisicion' => $this->requisicion,
                'acta' => $this->actaEntrega,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => Pdf::loadView('pdf.acta-entrega', $this->pdfData())
                    ->setPaper('letter', 'portrait')
                    ->output(),
                'acta_final_' . $this->requisicion->correlativo . '.pdf'
            )->withMime('application/pdf'),
        ];
    }

    private function pdfData(): array
    {
        return [
            'acta' => $this->actaEntrega,
            'requisicion' => $this->requisicion,
            'detalles' => $this->actaEntrega->detalles,
        ];
    }
}
