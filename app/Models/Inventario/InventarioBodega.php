<?php

namespace App\Models\Inventario;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioBodega extends BaseModel
{
    protected $table = 'inventario_bodegas';

    protected $fillable = [
        'nombre',
        'ubicacion',
        'responsable_id',
        'activo',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(InventarioExistencia::class, 'bodega_id');
    }
}
