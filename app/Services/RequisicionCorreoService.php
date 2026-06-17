<?php

namespace App\Services;

use App\Mail\RequisicionCreada;
use App\Mail\RequisicionActaFinal;
use App\Mail\RequisicionEstadoActualizado;
use App\Models\Actas\ActaEntrega;
use App\Models\Requisicion\Requisicion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RequisicionCorreoService
{
    public function enviarCreada(Requisicion $requisicion): void
    {
        try {
            $emails = $this->destinatariosRequisicion($requisicion);

            if (empty($emails)) {
                Log::warning('No se envio correo de requisicion creada porque no hay destinatarios con email.', [
                    'requisicion_id' => $requisicion->id,
                ]);
                return;
            }

            foreach ($emails as $email) {
                Mail::to($email)->send(new RequisicionCreada($requisicion));
            }

            Log::info('Correo de requisicion creada enviado.', [
                'requisicion_id' => $requisicion->id,
                'emails' => $emails,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de requisicion creada.', [
                'requisicion_id' => $requisicion->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function enviarEstadoActualizado(Requisicion $requisicion, string $nuevoEstado): void
    {
        try {
            $emails = $this->destinatariosRequisicion($requisicion);

            if (empty($emails)) {
                Log::warning('No se envio correo de estado actualizado porque no hay destinatarios con email.', [
                    'requisicion_id' => $requisicion->id,
                    'nuevo_estado' => $nuevoEstado,
                ]);
                return;
            }

            foreach ($emails as $email) {
                Mail::to($email)->send(new RequisicionEstadoActualizado($requisicion, $nuevoEstado));
            }

            Log::info('Correo de estado actualizado enviado.', [
                'requisicion_id' => $requisicion->id,
                'nuevo_estado' => $nuevoEstado,
                'emails' => $emails,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de estado actualizado.', [
                'requisicion_id' => $requisicion->id,
                'nuevo_estado' => $nuevoEstado,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function enviarActaFinal(Requisicion $requisicion, ActaEntrega $actaEntrega): void
    {
        try {
            $emails = $this->destinatariosRequisicion($requisicion);

            if (empty($emails)) {
                Log::warning('No se envio correo de acta final porque no hay destinatarios con email.', [
                    'requisicion_id' => $requisicion->id,
                    'acta_id' => $actaEntrega->id,
                ]);
                return;
            }

            foreach ($emails as $email) {
                Mail::to($email)->send(new RequisicionActaFinal($requisicion, $actaEntrega));
            }

            Log::info('Correo de acta final enviado.', [
                'requisicion_id' => $requisicion->id,
                'acta_id' => $actaEntrega->id,
                'emails' => $emails,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de acta final.', [
                'requisicion_id' => $requisicion->id,
                'acta_id' => $actaEntrega->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function destinatariosRequisicion(Requisicion $requisicion): array
    {
        $requisicion->loadMissing([
            'creador',
            'departamento.unidadEjecutora.administrador',
        ]);

        return collect([
            $requisicion->creador?->email,
            $requisicion->departamento?->unidadEjecutora?->administrador?->email,
        ])
            ->filter()
            ->map(fn ($email) => strtolower(trim($email)))
            ->unique()
            ->values()
            ->all();
    }
}
