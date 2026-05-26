<?php

namespace App\Services;

use App\Mail\RequisicionCreada;
use App\Mail\RequisicionEstadoActualizado;
use App\Models\Requisicion\Requisicion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RequisicionCorreoService
{
    public function enviarCreada(Requisicion $requisicion): void
    {
        try {
            $requisicion->loadMissing('creador');
            $email = $requisicion->creador->email ?? null;

            if (!$email) {
                Log::warning('No se envio correo de requisicion creada porque el creador no tiene email.', [
                    'requisicion_id' => $requisicion->id,
                ]);
                return;
            }

            Mail::to($email)->send(new RequisicionCreada($requisicion));

            Log::info('Correo de requisicion creada enviado.', [
                'requisicion_id' => $requisicion->id,
                'email' => $email,
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
            $requisicion->loadMissing('creador');
            $email = $requisicion->creador->email ?? null;

            if (!$email) {
                Log::warning('No se envio correo de estado actualizado porque el creador no tiene email.', [
                    'requisicion_id' => $requisicion->id,
                    'nuevo_estado' => $nuevoEstado,
                ]);
                return;
            }

            Mail::to($email)->send(new RequisicionEstadoActualizado($requisicion, $nuevoEstado));

            Log::info('Correo de estado actualizado enviado.', [
                'requisicion_id' => $requisicion->id,
                'nuevo_estado' => $nuevoEstado,
                'email' => $email,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de estado actualizado.', [
                'requisicion_id' => $requisicion->id,
                'nuevo_estado' => $nuevoEstado,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
