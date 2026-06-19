<?php

namespace App\Services;

use App\Mail\ActividadGuardada;
use App\Models\Actividad\Actividad;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ActividadCorreoService
{
    public function enviarGuardada(Actividad $actividad, ?User $usuario, string $accion, ?string $detalle = null): void
    {
        try {
            $actividad->loadMissing('unidadEjecutora.asistenteEstrategico');

            $destinatarios = $this->destinatariosActividad($actividad, $usuario);

            if (empty($destinatarios)) {
                Log::warning('No se envio correo de actividad porque el usuario no tiene email.', [
                    'actividad_id' => $actividad->id,
                    'user_id' => $usuario?->id,
                    'accion' => $accion,
                ]);
                return;
            }

            foreach ($destinatarios as $destinatario) {
                Mail::to($destinatario['email'])->send(
                    new ActividadGuardada($actividad, $usuario, $accion, $detalle, $destinatario['user'])
                );
            }

            Log::info('Correo de actividad enviado.', [
                'actividad_id' => $actividad->id,
                'user_id' => $usuario?->id,
                'emails' => collect($destinatarios)->pluck('email')->all(),
                'accion' => $accion,
                'detalle' => $detalle,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de actividad.', [
                'actividad_id' => $actividad->id,
                'user_id' => $usuario?->id,
                'accion' => $accion,
                'detalle' => $detalle,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function destinatariosActividad(Actividad $actividad, ?User $usuario): array
    {
        return collect([
            $usuario,
            $actividad->unidadEjecutora?->asistenteEstrategico,
        ])
            ->filter(fn ($destinatario) => $destinatario?->email)
            ->map(fn ($destinatario) => [
                'email' => strtolower(trim($destinatario->email)),
                'user' => $destinatario,
            ])
            ->unique('email')
            ->values()
            ->all();
    }
}
