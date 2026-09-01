<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioEntradaDetalle extends Model
{
    protected $table = 'inventario_entrada_detalles';

    protected $fillable = [
        'entrada_id',
        'producto_id',
        'lote_id',
        'codigo_lote',
        'cantidad',
        'costo_unitario',
        'total',
        'fecha_vencimiento',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'total' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function entrada(): BelongsTo
    {
        return $this->belongsTo(InventarioEntrada::class, 'entrada_id');
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
