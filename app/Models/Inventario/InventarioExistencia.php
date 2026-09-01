<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioExistencia extends Model
{
    protected $table = 'inventario_existencias';

    protected $fillable = [
        'bodega_id',
        'producto_id',
        'lote_id',
        'cantidad_disponible',
        'cantidad_reservada',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cantidad_disponible' => 'decimal:2',
        'cantidad_reservada' => 'decimal:2',
    ];

    public function bodega(): BelongsTo
    {
        return $this->belongsTo(InventarioBodega::class, 'bodega_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(InventarioProducto::class, 'producto_id');
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(InventarioLote::class, 'lote_id');
    }
}
