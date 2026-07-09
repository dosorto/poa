<?php

namespace App\Http\Controllers\Revision;

use App\Http\Controllers\Controller;
use App\Models\Actividad\Actividad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RegresarActividadReformulacionController extends Controller
{
    private const ESTADOS_PERMITIDOS = ['REVISION', 'REFORMULACION', 'APROBADO', 'RECHAZADO'];

    public function __invoke(Actividad $actividad): RedirectResponse
    {
        try {
            DB::transaction(function () use ($actividad) {
                if (! in_array($actividad->estado, self::ESTADOS_PERMITIDOS, true)) {
                    throw new \RuntimeException('La actividad no se puede enviar a reformulación desde su estado actual.');
                }

                $actividad->update([
                    'estado' => 'REFORMULACION',
                ]);
            });

            return back()->with('message', 'La actividad fue enviada nuevamente a reformulación.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al regresar la actividad a reformulación: ' . $e->getMessage());
        }
    }
}
