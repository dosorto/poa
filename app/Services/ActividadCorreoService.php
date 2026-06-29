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

    public function enviarDictamenAlCreador(Actividad $actividad, User $usuarioDictamen, string $estado, ?string $comentario = null): void
    {
        try {
            $actividad->loadMissing(['creador', 'unidadEjecutora.asistenteEstrategico']);

            $creador = $actividad->creador;

            if (!$creador?->email) {
                Log::warning('No se envio correo de dictamen porque la actividad no tiene creador con email.', [
                    'actividad_id' => $actividad->id,
                    'created_by' => $actividad->created_by,
                    'estado' => $estado,
                ]);
                return;
            }

            $accion = $estado === 'APROBADO' ? 'aprobada' : 'rechazada';
            $detalle = $comentario
                ? 'Observaciones del dictamen: ' . $comentario
                : 'Dictamen emitido sin observaciones.';

            Mail::to($creador->email)->send(
                new ActividadGuardada($actividad, $usuarioDictamen, $accion, $detalle, $creador)
            );

            Log::info('Correo de dictamen enviado al creador de la actividad.', [
                'actividad_id' => $actividad->id,
                'creador_id' => $creador->id,
                'email' => $creador->email,
                'estado' => $estado,
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de dictamen al creador.', [
                'actividad_id' => $actividad->id,
                'estado' => $estado,
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
