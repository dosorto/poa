<?php

namespace App\Mail;

use App\Models\Actividad\Actividad;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActividadGuardada extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Actividad $actividad,
        public User $usuario,
        public string $accion,
        public ?string $detalle = null,
        public ?User $destinatario = null
    ) {
        $this->actividad->loadMissing([
            'categoria',
            'departamento',
            'empleados.user',
            'indicadores.planificacions.mes.trimestre',
            'poa',
            'resultado',
            'tareas.presupuestos',
            'tipo',
            'unidadEjecutora.asistenteEstrategico',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Actividad ' . $this->accion . ' - ' . ($this->actividad->correlativo_formateado ?? $this->actividad->nombre),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.actividad-guardada',
            with: [
                'actividad' => $this->actividad,
                'usuario' => $this->usuario,
                'accion' => $this->accion,
                'detalle' => $this->detalle,
                'destinatario' => $this->destinatario ?? $this->usuario,
            ],
        );
    }
}
