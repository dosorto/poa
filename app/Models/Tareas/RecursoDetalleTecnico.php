<?php

namespace App\Models\Tareas;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tareas\TareaHistorico;

class RecursoDetalleTecnico extends Model
{
    protected $table = 'recurso_detalles_tecnicos';

    protected $fillable = [
        'id_tareas_historicos',
        'nombre',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con TareaHistorico
    public function recurso()
    {
        return $this->belongsTo(TareaHistorico::class, 'id_tareas_historicos');
    }
}
