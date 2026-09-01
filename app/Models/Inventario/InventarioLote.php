<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioLote extends Model
{
    protected $table = 'inventario_lotes';

    protected $fillable = [
        'producto_id',
        'codigo_lote',
        'fecha_ingreso',
        'fecha_vencimiento',
        'ubicacion',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_vencimiento' => 'date',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(InventarioProducto::class, 'producto_id');
    }

    public function existencias(): HasMany
    {
        return $this->hasMany(InventarioExistencia::class, 'lote_id');
    }
}
