<?php

namespace App\Models\Inventario;

use App\Models\Actas\ActaEntrega;
use App\Models\BaseModel;
use App\Models\Departamento\Departamento;
use App\Models\Empleados\Empleado;
use App\Models\Requisicion\Requisicion;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioSalida extends BaseModel
{
    protected $table = 'inventario_salidas';

    protected $fillable = [
        'numero_salida',
        'bodega_id',
        'acta_entrega_id',
        'requisicion_id',
        'tipo_salida',
        'motivo',
        'departamento_id',
        'empleado_recibe_id',
        'responsable_entrega_id',
        'usuario_id',
        'fecha_salida',
        'observacion',
        'estado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha_salida' => 'date',
    ];

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(InventarioBodega::class, 'bodega_id');
    }

    public function actaEntrega(): BelongsTo
    {
        return $this->belongsTo(ActaEntrega::class, 'acta_entrega_id');
    }

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class, 'requisicion_id');
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function empleadoRecibe(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_recibe_id');
    }

    public function responsableEntrega(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_entrega_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(InventarioSalidaDetalle::class, 'salida_id');
    }
}
